<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * Lê `autograding.json` junto a cada pasta de testes em base-project (template de correção).
 *
 * Esquema sugerido:
 * - weight: int (pontos máximos da pasta na nota final, ex. 10, 30 — nota = taxa_% × weight / 100)
 * - visibility: "student"|"teacher"|"both" — quem vê estes resultados na UI
 * - purpose: "formative"|"summative" — formativa (feedback/verificação) vs sumativa (avaliação final)
 */
final class SuiteAutograding
{
    public static function jsonAbsolutePath(string $pathPattern): string
    {
        $root = rtrim((string) config('autograding.project_root'), DIRECTORY_SEPARATOR);
        $rel = trim(str_replace('\\', '/', $pathPattern), '/');

        return $root.DIRECTORY_SEPARATOR.'base-project'.DIRECTORY_SEPARATOR
            .str_replace('/', DIRECTORY_SEPARATOR, $rel).DIRECTORY_SEPARATOR.'autograding.json';
    }

    /**
     * @return array{weight?: int, visibility?: string, purpose?: string}|null
     */
    public static function read(?string $pathPattern): ?array
    {
        if ($pathPattern === null || trim($pathPattern) === '') {
            return null;
        }

        $path = self::jsonAbsolutePath($pathPattern);
        if (! File::isFile($path)) {
            return null;
        }

        try {
            $data = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }

        if (! is_array($data)) {
            return null;
        }

        return self::normalize($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{weight?: int, visibility?: string, purpose?: string}
     */
    public static function normalize(array $data): array
    {
        $out = [];

        if (array_key_exists('weight', $data) && is_numeric($data['weight'])) {
            $normalized = self::normalizeWeightValue($data['weight']);
            if ($normalized !== null) {
                $out['weight'] = $normalized;
            }
        }

        $vis = $data['visibility'] ?? null;
        if (in_array($vis, ['student', 'teacher', 'both'], true)) {
            $out['visibility'] = $vis;
        }

        $purpose = $data['purpose'] ?? null;
        if (in_array($purpose, ['formative', 'summative'], true)) {
            $out['purpose'] = $purpose;
        }

        return $out;
    }

    /**
     * Peso inteiro (pontos). Aceita legado 0.2 → 20 quando o valor é ≤ 1.
     */
    public static function normalizeWeightValue(mixed $weight): ?int
    {
        if (! is_numeric($weight)) {
            return null;
        }
        $w = (float) $weight;
        if ($w <= 0) {
            return null;
        }
        if ($w > 0 && $w <= 1) {
            return (int) round($w * 100);
        }

        return (int) round($w);
    }

    /**
     * @param  list<object|array<string, mixed>>  $tests
     */
    public static function suiteSuccessRatePercent(array $tests): ?float
    {
        if ($tests === []) {
            return null;
        }
        $passed = 0;
        foreach ($tests as $test) {
            $status = is_array($test) ? ($test['status'] ?? '') : ($test->status ?? '');
            if ($status === 'passed') {
                $passed++;
            }
        }

        return round($passed / count($tests) * 100, 2);
    }

    /**
     * Nota final = Σ (taxa de sucesso da pasta × peso da pasta / 100).
     *
     * @param  list<array<string, mixed>>  $testsByGroup
     */
    public static function calculateWeightedFinalGrade(array $testsByGroup): ?float
    {
        $total = 0.0;
        $hasWeight = false;

        foreach ($testsByGroup as $block) {
            $weight = (int) ($block['weight'] ?? 0);
            if ($weight <= 0) {
                continue;
            }
            $hasWeight = true;
            if (isset($block['weighted_points']) && $block['weighted_points'] !== null) {
                $total += (float) $block['weighted_points'];
                continue;
            }
            $rate = $block['success_rate_percent'] ?? null;
            if ($rate !== null) {
                $total += round((float) $rate * $weight / 100, 2);
            }
        }

        return $hasWeight ? round($total, 2) : null;
    }

    /**
     * Calcula a nota final em pontos (soma ponderada por pasta) para gravar em final_grade.
     *
     * @param  iterable<\App\Models\ProcessTestGroup>  $groups
     * @param  list<object{test_name: string, status: string, error_message: ?string, execution_logs: mixed, file: ?string}>  $tests
     * @param  array<string, mixed>  $payload
     */
    public static function computeFinalGrade(iterable $groups, array $tests, array $payload): ?float
    {
        $testsByGroup = self::enrichTestsByGroupWithAccess(
            self::groupTestsByProcessTestGroups($groups, $tests, $payload),
            $payload,
            false,
            true
        );

        return self::calculateWeightedFinalGrade($testsByGroup);
    }

    /**
     * @deprecated Use final_grade na base de dados. Mantido para vistas compiladas em cache.
     */
    public static function resolveFinalGradePoints(array $testsByGroup, ?float $storedGrade = null): ?float
    {
        return $storedGrade;
    }

    public static function totalMaxPoints(array $testsByGroup): int
    {
        $sum = 0;
        foreach ($testsByGroup as $block) {
            $sum += (int) ($block['weight'] ?? 0);
        }

        return $sum;
    }

    /**
     * @param  list<array<string, mixed>>  $testsByGroup
     * @return list<array{path: string, weight: int, success_rate_percent: ?float, weighted_points: ?float}>
     */
    public static function weightedGradeBreakdown(array $testsByGroup): array
    {
        $rows = [];
        foreach ($testsByGroup as $block) {
            $path = (string) ($block['path_pattern'] ?? '');
            if ($path === '') {
                continue;
            }
            $rows[] = [
                'path' => $path,
                'name' => (string) ($block['name'] ?? $path),
                'weight' => (int) ($block['weight'] ?? 0),
                'success_rate_percent' => $block['success_rate_percent'] ?? null,
                'weighted_points' => $block['weighted_points'] ?? null,
            ];
        }

        return $rows;
    }

    /**
     * Visibilidade efetiva da pasta de testes (autograding.json), a partir do resultado da correção ou do template em base-project.
     *
     * @param  array<string, mixed>  $reportPayload  Payload JSON guardado em submission_results.report_sent
     */
    public static function effectiveVisibility(?string $pathPattern, array $reportPayload): string
    {
        $suiteCfgs = data_get($reportPayload, 'autograding_process_config.suite_configs', []);
        $segments = $pathPattern !== null && trim((string) $pathPattern) !== ''
            ? preg_split('/[\s,]+/', trim((string) $pathPattern), -1, PREG_SPLIT_NO_EMPTY)
            : [];

        foreach ($segments as $seg) {
            $seg = trim(str_replace('\\', '/', (string) $seg), '/');
            if ($seg === '') {
                continue;
            }
            if (isset($suiteCfgs[$seg]) && is_array($suiteCfgs[$seg])) {
                $n = self::normalize($suiteCfgs[$seg]);
                if (! empty($n['visibility'])) {
                    return $n['visibility'];
                }
            }
        }

        foreach ($segments as $seg) {
            $s = trim(str_replace('\\', '/', (string) $seg), '/');
            if ($s === '') {
                continue;
            }
            $read = self::read($s);
            if (! empty($read['visibility'])) {
                return $read['visibility'];
            }
            break;
        }

        return 'both';
    }

    public static function effectiveVisibilityFromSubmission(\App\Models\Submission $submission): string
    {
        $submission->loadMissing(['processTestGroup', 'submissionResult']);
        $payload = $submission->submissionResult?->report_sent_payload;

        return self::effectiveVisibility(
            $submission->processTestGroup?->path_pattern,
            is_array($payload) ? $payload : []
        );
    }

    /**
     * @param  array<string, mixed>  $reportPayload
     * @return array{weight?: int, visibility?: string, purpose?: string}
     */
    public static function suiteConfigForSegment(string $segment, array $reportPayload): array
    {
        $segment = trim(str_replace('\\', '/', $segment), '/');
        $suiteCfgs = data_get($reportPayload, 'autograding_process_config.suite_configs', []);
        if ($segment !== '' && isset($suiteCfgs[$segment]) && is_array($suiteCfgs[$segment])) {
            return self::normalize($suiteCfgs[$segment]);
        }

        return self::read($segment !== '' ? $segment : null) ?? [];
    }

    /**
     * @param  array<string, mixed>  $reportPayload
     */
    public static function visibilityForSegment(string $segment, array $reportPayload): string
    {
        $cfg = self::suiteConfigForSegment($segment, $reportPayload);

        return $cfg['visibility'] ?? 'both';
    }

    /**
     * @param  array<string, mixed>  $reportPayload
     */
    public static function purposeForSegment(string $segment, array $reportPayload): ?string
    {
        $cfg = self::suiteConfigForSegment($segment, $reportPayload);

        return $cfg['purpose'] ?? null;
    }

    public static function canViewFinalGrade(bool $viewerOwnsSubmission, bool $viewerIsProcessTeacher): bool
    {
        return $viewerOwnsSubmission || $viewerIsProcessTeacher;
    }

    public static function purposeAllowsDetails(?string $purpose): bool
    {
        return $purpose !== 'summative';
    }

    public static function canViewSuiteDetails(
        string $visibility,
        ?string $purpose,
        bool $viewerOwnsSubmission,
        bool $viewerIsProcessTeacher
    ): bool {
        if (! self::mayViewByVisibility($visibility, $viewerOwnsSubmission, $viewerIsProcessTeacher)) {
            return false;
        }

        return self::purposeAllowsDetails($purpose);
    }

    /**
     * @param  list<array<string, mixed>>  $testsByGroup
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    public static function enrichTestsByGroupWithAccess(
        array $testsByGroup,
        array $payload,
        bool $viewerOwnsSubmission,
        bool $viewerIsProcessTeacher
    ): array {
        return array_map(function (array $block) use ($payload, $viewerOwnsSubmission, $viewerIsProcessTeacher) {
            $seg = (string) ($block['path_pattern'] ?? '');
            $cfg = $seg !== '' ? self::suiteConfigForSegment($seg, $payload) : [];
            $weight = (int) ($cfg['weight'] ?? 0);
            $visibility = $seg !== '' ? self::visibilityForSegment($seg, $payload) : 'both';
            $purpose = $seg !== '' ? self::purposeForSegment($seg, $payload) : null;
            $suiteTests = is_array($block['tests'] ?? null) ? $block['tests'] : [];
            $successRate = self::suiteSuccessRatePercent($suiteTests);
            $rateForPoints = $successRate ?? 0.0;
            $weightedPoints = $weight > 0
                ? round($rateForPoints * $weight / 100, 2)
                : null;

            $block['weight'] = $weight;
            $block['success_rate_percent'] = $successRate;
            $block['weighted_points'] = $weightedPoints;
            $block['visibility'] = $visibility;
            $block['purpose'] = $purpose;
            $block['can_view_details'] = self::canViewSuiteDetails(
                $visibility,
                $purpose,
                $viewerOwnsSubmission,
                $viewerIsProcessTeacher
            );

            return $block;
        }, $testsByGroup);
    }

    public static function canViewAnySuiteDetails(
        array $testsByGroup,
        array $payload,
        bool $viewerOwnsSubmission,
        bool $viewerIsProcessTeacher
    ): bool {
        foreach ($testsByGroup as $block) {
            $seg = (string) ($block['path_pattern'] ?? '');
            if ($seg === '') {
                continue;
            }
            if (self::canViewSuiteDetails(
                self::visibilityForSegment($seg, $payload),
                self::purposeForSegment($seg, $payload),
                $viewerOwnsSubmission,
                $viewerIsProcessTeacher
            )) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<object{test_name: string, status: string, error_message: ?string, execution_logs: mixed, file: ?string}>
     */
    public static function collectTestsForDisplay(?\App\Models\SubmissionResult $result, array $payload): array
    {
        $payloadTests = collect(data_get($payload, 'results.tests', []));
        $executions = $result?->testExecutions ?? collect();

        if ($executions->isNotEmpty()) {
            $fileByName = $payloadTests->mapWithKeys(function ($test) {
                $name = (string) ($test['name'] ?? '');

                return $name !== '' ? [$name => (string) ($test['file'] ?? '')] : [];
            });

            return $executions->map(function ($ex) use ($fileByName) {
                $name = (string) ($ex->test_name ?? '');
                $file = $fileByName->get($name, '');

                return (object) [
                    'test_name' => $name !== '' ? $name : __('Teste sem nome'),
                    'status' => (string) ($ex->status ?? 'unknown'),
                    'error_message' => $ex->error_message,
                    'execution_logs' => $ex->execution_logs,
                    'file' => $file !== '' ? $file : null,
                ];
            })->all();
        }

        if ($payloadTests->isNotEmpty()) {
            return $payloadTests->map(function ($test) {
                return (object) [
                    'test_name' => $test['name'] ?? __('Teste sem nome'),
                    'status' => $test['status'] ?? 'unknown',
                    'error_message' => $test['message'] ?? null,
                    'execution_logs' => isset($test['logs'])
                        ? (is_string($test['logs'])
                            ? $test['logs']
                            : json_encode($test['logs'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                        : null,
                    'file' => ! empty($test['file']) ? (string) $test['file'] : null,
                ];
            })->all();
        }

        return [];
    }

    /**
     * Pastas de testes no base-project (tests/tests, tests/tests1, …).
     *
     * @return list<string>
     */
    public static function discoverTestPathsFromBaseProject(): array
    {
        $testsRoot = rtrim((string) config('autograding.project_root'), DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.'base-project'
            .DIRECTORY_SEPARATOR.'tests';

        if (! is_dir($testsRoot)) {
            return [];
        }

        $paths = [];
        foreach (scandir($testsRoot) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $dir = $testsRoot.DIRECTORY_SEPARATOR.$entry;
            if (! is_dir($dir)) {
                continue;
            }
            if (is_file($dir.DIRECTORY_SEPARATOR.'autograding.json')) {
                $paths[] = 'tests/'.$entry;
            }
        }

        sort($paths);

        return $paths;
    }

    /**
     * Todas as pastas de testes relevantes (grupos do processo + correção + base-project).
     *
     * @param  iterable<\App\Models\ProcessTestGroup>  $groups
     * @return list<string>
     */
    public static function resolveAllTestFolderPaths(iterable $groups, array $payload = []): array
    {
        $paths = [];
        foreach ($groups as $group) {
            foreach (self::pathPatternSegments($group->path_pattern) as $seg) {
                $paths[] = $seg;
            }
        }

        $cfg = data_get($payload, 'autograding_process_config', []);
        if (is_array($cfg)) {
            foreach (['all_test_paths', 'test_paths'] as $key) {
                $raw = $cfg[$key] ?? null;
                if (is_array($raw)) {
                    foreach ($raw as $p) {
                        $norm = trim(str_replace('\\', '/', (string) $p), '/');
                        if ($norm !== '') {
                            $paths[] = $norm;
                        }
                    }
                }
            }
            $suiteCfgs = $cfg['suite_configs'] ?? null;
            if (is_array($suiteCfgs)) {
                foreach (array_keys($suiteCfgs) as $seg) {
                    $norm = trim(str_replace('\\', '/', (string) $seg), '/');
                    if ($norm !== '') {
                        $paths[] = $norm;
                    }
                }
            }
        }

        foreach (self::discoverTestPathsFromBaseProject() as $p) {
            $paths[] = $p;
        }

        $merged = [];
        foreach ($paths as $p) {
            if (! in_array($p, $merged, true)) {
                $merged[] = $p;
            }
        }

        usort($merged, fn (string $a, string $b) => strlen($b) <=> strlen($a));

        return $merged;
    }

    public static function labelForTestPath(string $path, ?string $groupName = null): string
    {
        if ($groupName !== null && trim($groupName) !== '') {
            return $groupName;
        }

        $path = trim(str_replace('\\', '/', $path), '/');

        return $path !== '' ? basename($path) : __('Testes');
    }

    /**
     * Agrupa testes por pasta (cada ProcessTestGroup + pastas da correção); sem secção «Outros».
     *
     * @param  iterable<\App\Models\ProcessTestGroup>  $groups
     * @param  list<object{test_name: string, status: string, error_message: ?string, execution_logs: mixed, file: ?string}>  $tests
     * @param  array<string, mixed>  $payload
     * @return list<array{name: string, path_pattern: string, tests: list<object>, passed: int, total: int}>
     */
    public static function groupTestsByProcessTestGroups(iterable $groups, array $tests, array $payload = []): array
    {
        $groupList = collect($groups)->values();

        $segmentToMeta = [];
        foreach ($groupList as $group) {
            foreach (self::pathPatternSegments($group->path_pattern) as $seg) {
                if (! isset($segmentToMeta[$seg])) {
                    $segmentToMeta[$seg] = [
                        'name' => $group->name,
                        'path_pattern' => $seg,
                    ];
                }
            }
        }

        foreach (self::resolveAllTestFolderPaths($groups, $payload) as $seg) {
            if (! isset($segmentToMeta[$seg])) {
                $segmentToMeta[$seg] = [
                    'name' => self::labelForTestPath($seg),
                    'path_pattern' => $seg,
                ];
            }
        }

        if ($segmentToMeta === []) {
            if ($tests === []) {
                return [];
            }

            return [[
                'name' => __('Testes'),
                'path_pattern' => '',
                'tests' => $tests,
                'passed' => self::countPassed($tests),
                'total' => count($tests),
            ]];
        }

        $segmentsByLength = array_keys($segmentToMeta);
        usort($segmentsByLength, fn (string $a, string $b) => strlen($b) <=> strlen($a));

        $buckets = array_fill_keys(array_keys($segmentToMeta), []);
        $unassigned = [];

        foreach ($tests as $test) {
            $segment = self::resolveTestSegment($test, $segmentsByLength);
            if ($segment !== null && isset($buckets[$segment])) {
                $buckets[$segment][] = $test;
            } else {
                $unassigned[] = $test;
            }
        }

        foreach ($unassigned as $test) {
            $segment = self::inferTestFolderFromFile($test)
                ?? self::inferTestFolderFromName($test, array_keys($buckets))
                ?? self::resolveTestSegment($test, $segmentsByLength);
            if ($segment === null) {
                continue;
            }
            if (! isset($segmentToMeta[$segment])) {
                $segmentToMeta[$segment] = [
                    'name' => self::labelForTestPath($segment),
                    'path_pattern' => $segment,
                ];
                $buckets[$segment] = [];
                $segmentsByLength[] = $segment;
                usort($segmentsByLength, fn (string $a, string $b) => strlen($b) <=> strlen($a));
            }
            $buckets[$segment][] = $test;
        }

        $order = [];
        foreach ($groupList as $group) {
            foreach (self::pathPatternSegments($group->path_pattern) as $seg) {
                if (isset($segmentToMeta[$seg]) && ! in_array($seg, $order, true)) {
                    $order[] = $seg;
                }
            }
        }
        foreach (array_keys($segmentToMeta) as $seg) {
            if (! in_array($seg, $order, true)) {
                $order[] = $seg;
            }
        }

        $out = [];
        foreach ($order as $seg) {
            $meta = $segmentToMeta[$seg];
            $groupTests = $buckets[$seg] ?? [];
            $out[] = [
                'name' => $meta['name'],
                'path_pattern' => $meta['path_pattern'],
                'tests' => $groupTests,
                'passed' => self::countPassed($groupTests),
                'total' => count($groupTests),
            ];
        }

        return $out;
    }

    /**
     * @param  list<string>  $segmentsByLength  segmentos ordenados do mais longo ao mais curto
     */
    public static function resolveTestSegment(array|object $test, array $segmentsByLength): ?string
    {
        foreach ($segmentsByLength as $seg) {
            if (self::testBelongsToPathSegment($test, $seg)) {
                return $seg;
            }
        }

        $inferred = self::inferTestFolderFromFile($test);
        if ($inferred !== null && in_array($inferred, $segmentsByLength, true)) {
            return $inferred;
        }

        $fromName = self::inferTestFolderFromName($test, $segmentsByLength);
        if ($fromName !== null && in_array($fromName, $segmentsByLength, true)) {
            return $fromName;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|object  $test
     */
    public static function inferTestFolderFromFile(array|object $test): ?string
    {
        $file = '';
        if (is_array($test)) {
            $file = (string) ($test['file'] ?? '');
        } elseif (is_object($test)) {
            $file = (string) ($test->file ?? '');
        }
        $file = str_replace('\\', '/', trim($file, '/'));
        if ($file !== '' && preg_match('#^(tests/[^/]+)#', $file, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * @param  list<string>  $candidateSegments
     */
    public static function inferTestFolderFromName(array|object $test, array $candidateSegments): ?string
    {
        $name = '';
        if (is_array($test)) {
            $name = (string) ($test['name'] ?? $test['test_name'] ?? '');
        } elseif (is_object($test)) {
            $name = (string) ($test->test_name ?? $test->name ?? '');
        }
        $name = str_replace('\\', '/', trim($name, '/'));
        if ($name === '' || $candidateSegments === []) {
            return null;
        }

        $ordered = $candidateSegments;
        usort($ordered, fn (string $a, string $b) => strlen($b) <=> strlen($a));

        foreach ($ordered as $segment) {
            $segment = trim(str_replace('\\', '/', $segment), '/');
            if ($segment === '') {
                continue;
            }
            if (stripos($name, $segment) !== false) {
                return $segment;
            }
            $leaf = basename($segment);
            if ($leaf !== '' && $leaf !== 'tests' && stripos($name, $leaf) !== false) {
                return $segment;
            }
            $suiteToken = str_replace(['tests', '_', '-'], '', $leaf);
            if ($suiteToken !== '' && stripos($name, $suiteToken) !== false) {
                return $segment;
            }
        }

        if (preg_match('#(^|/)Tests(/|$)#i', $name)) {
            foreach ($ordered as $segment) {
                if (basename(trim(str_replace('\\', '/', $segment), '/')) === 'tests') {
                    return trim(str_replace('\\', '/', $segment), '/');
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|object  $test
     */
    public static function testBelongsToPathSegment(array|object $test, string $segment): bool
    {
        $segment = trim(str_replace('\\', '/', $segment), '/');
        if ($segment === '') {
            return false;
        }

        $file = '';
        if (is_array($test)) {
            $file = (string) ($test['file'] ?? '');
        } elseif (is_object($test)) {
            $file = (string) ($test->file ?? '');
        }
        $file = str_replace('\\', '/', trim($file, '/'));

        if ($file !== '' && ($file === $segment || str_starts_with($file, $segment.'/'))) {
            return true;
        }

        $name = '';
        if (is_array($test)) {
            $name = (string) ($test['name'] ?? $test['test_name'] ?? '');
        } elseif (is_object($test)) {
            $name = (string) ($test->test_name ?? $test->name ?? '');
        }
        $name = str_replace('\\', '/', $name);

        if ($name === '') {
            return false;
        }

        $segmentSlash = str_replace('\\', '/', $segment);
        if (stripos($name, $segmentSlash) !== false) {
            return true;
        }

        $folder = basename($segment);
        if ($folder === '' || $folder === 'tests') {
            return false;
        }

        if (stripos($name, $folder) !== false) {
            return true;
        }

        $suiteToken = str_replace(['tests', '_', '-'], '', $folder);
        if ($suiteToken !== '' && stripos($name, $suiteToken) !== false) {
            return true;
        }

        return false;
    }

    /**
     * @param  list<object|array<string, mixed>>  $tests
     */
    public static function countPassed(array $tests): int
    {
        $n = 0;
        foreach ($tests as $test) {
            $status = is_array($test) ? ($test['status'] ?? '') : ($test->status ?? '');
            if ($status === 'passed') {
                $n++;
            }
        }

        return $n;
    }

    /**
     * @param  array<int, array<string, mixed>|object>  $tests
     * @return array<int, array<string, mixed>|object>
     */
    public static function filterTestsForPathPattern(array $tests, ?string $pathPattern): array
    {
        $segments = self::pathPatternSegments($pathPattern);
        if ($segments === []) {
            return $tests;
        }

        return array_values(array_filter($tests, function ($test) use ($segments) {
            $file = '';
            if (is_array($test)) {
                $file = (string) ($test['file'] ?? '');
            } elseif (is_object($test)) {
                $file = (string) ($test->file ?? '');
            }
            $file = str_replace('\\', '/', trim($file, '/'));

            $name = '';
            if (is_array($test)) {
                $name = (string) ($test['name'] ?? $test['test_name'] ?? '');
            } elseif (is_object($test)) {
                $name = (string) ($test->test_name ?? $test->name ?? '');
            }
            $name = str_replace('\\', '/', $name);

            foreach ($segments as $seg) {
                if ($file !== '' && ($file === $seg || str_starts_with($file, $seg.'/'))) {
                    return true;
                }
                if ($name !== '' && (str_contains($name, '/'.$seg.'/') || str_starts_with($name, $seg.'/'))) {
                    return true;
                }
            }

            return false;
        }));
    }

    /**
     * @return list<string>
     */
    public static function pathPatternSegments(?string $pathPattern): array
    {
        if ($pathPattern === null || trim($pathPattern) === '') {
            return [];
        }

        $segments = preg_split('/[\s,]+/', trim($pathPattern), -1, PREG_SPLIT_NO_EMPTY);
        $out = [];
        foreach ($segments ?: [] as $seg) {
            $norm = trim(str_replace('\\', '/', (string) $seg), '/');
            if ($norm !== '') {
                $out[] = $norm;
            }
        }

        return $out;
    }

    /**
     * @param  string  $visibility  student|teacher|both
     */
    public static function mayViewByVisibility(string $visibility, bool $viewerOwnsSubmission, bool $viewerIsProcessTeacher): bool
    {
        return match ($visibility) {
            'student' => $viewerOwnsSubmission && ! $viewerIsProcessTeacher,
            'teacher' => $viewerIsProcessTeacher,
            'both' => $viewerOwnsSubmission || $viewerIsProcessTeacher,
            default => $viewerOwnsSubmission || $viewerIsProcessTeacher,
        };
    }

    /** @deprecated Use {@see canViewSuiteDetails} (purpose + visibility por pasta). */
    public static function mayViewGradingDetails(string $visibility, bool $viewerOwnsSubmission, bool $viewerIsProcessTeacher): bool
    {
        return self::mayViewByVisibility($visibility, $viewerOwnsSubmission, $viewerIsProcessTeacher);
    }
}
