<?php

namespace App\Services;

use App\Models\Process;
use App\Models\Submission;
use App\Models\SubmissionResult;
use App\Models\TestExecution;
use App\Support\ProcessDbRebuildStrategy;
use App\Support\ProcessProjectPaths;
use App\Support\SuiteAutograding;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use JsonException;
use Symfony\Component\Process\Process as SymfonyProcess;

class AutoGradingRunner
{
    public function grade(Submission $submission): void
    {
        if (! config('autograding.enabled')) {
            $this->markFailed($submission, ['error' => 'AUTOGRADING_ENABLED=false na configuração.']);

            return;
        }

        $zipPath = Storage::path($submission->zip_file_path);
        if (! is_file($zipPath)) {
            $this->markFailed($submission, ['error' => 'ZIP da submissão inexistente.', 'path' => $submission->zip_file_path]);

            return;
        }

        $submission->loadMissing(['process.processTestGroups', 'processTestGroup']);

        if (! $submission->process_test_group_id || ! $submission->processTestGroup) {
            $this->markFailed($submission, ['error' => 'Submissão sem grupo de testes (process_test_group_id).']);

            return;
        }

        $process = $submission->process;
        if (! $process) {
            $this->markFailed($submission, ['error' => 'Submissão sem processo.']);

            return;
        }

        $baseDir = ProcessProjectPaths::resolveBaseDirForProcess($process);
        if ($baseDir === null) {
            $this->markFailed($submission, [
                'error' => 'Nem o projeto carregado pelo docente nem o base-project global estão disponíveis para correr esta submissão.',
                'project_status' => $process->project_status,
                'project_base_path' => $process->project_base_path,
                'global_base_project' => ProcessProjectPaths::globalBaseProjectPath(),
            ]);

            return;
        }

        $usingFallback = $baseDir === ProcessProjectPaths::globalBaseProjectPath();

        $submission->update(['status' => 'processing']);

        $storageRoot = ProcessProjectPaths::ensureSubmissionArtifactsDir($submission);
        ProcessProjectPaths::ensureProcessStorageLayout($process);

        $lock = Cache::lock('autograding_process_'.$process->id, (int) config('autograding.timeout', 1900));

        if (! $lock->get()) {
            $this->markFailed($submission, [
                'error' => 'Outra submissão deste processo está a ser corrigida. Tenta novamente dentro de momentos.',
            ], $storageRoot);

            return;
        }

        try {
            $this->runGradingInProcessWorking(
                $submission,
                $process,
                $submission->processTestGroup,
                $zipPath,
                $baseDir,
                $usingFallback,
                $storageRoot
            );
        } finally {
            $lock->release();
        }
    }

    private function runGradingInProcessWorking(
        Submission $submission,
        Process $process,
        $group,
        string $zipPath,
        string $baseDir,
        bool $usingFallback,
        string $storageRoot
    ): void {
        $workDir = ProcessProjectPaths::workingPath($process);

        if (! ProcessProjectPaths::resetWorkingFromBase($process, $baseDir)) {
            $this->markFailed($submission, [
                'error' => 'Falhou a preparar a pasta working/ do processo a partir do projeto base.',
                'base_dir' => $baseDir,
            ], $storageRoot, $workDir);

            return;
        }

        $extractTmp = ProcessProjectPaths::gradingExtractPath($process);
        $this->resetDirectory($extractTmp);
        if (! $this->extractZip($zipPath, $extractTmp)) {
            $this->cleanupGradingTemp($process);
            $this->markFailed($submission, ['error' => 'Falhou a extração do ZIP do aluno.'], $storageRoot, $workDir);

            return;
        }

        $componentsToReplace = ['app', 'routes', 'resources'];
        $studentRoot = $this->findStudentProjectRoot($extractTmp) ?? $extractTmp;
        $replaced = $this->replaceStudentFolders($studentRoot, $workDir, $componentsToReplace);
        $this->mergeStudentComposerRequirements($studentRoot, $workDir);
        $this->mergeStudentFrontendManifest($studentRoot, $workDir);
        $submissionSqlite = ProcessDbRebuildStrategy::resolveSqliteInStudentRoot($studentRoot);
        File::deleteDirectory($extractTmp);
        if ($replaced === []) {
            $this->cleanupGradingTemp($process);
            $this->markFailed($submission, [
                'error' => 'O ZIP do aluno não contém nenhuma das pastas obrigatórias (app, routes, resources).',
                'inspected_root' => $studentRoot,
            ], $storageRoot, $workDir);

            return;
        }

        $this->writeComponentsJson($storageRoot, $replaced);

        try {
            $processConfigPath = $this->writeProcessConfigJson($storageRoot, $submission, $process, $group, $workDir);
        } catch (JsonException $e) {
            $this->markFailed($submission, [
                'error' => 'Falha ao gravar process-config.json',
                'detail' => $e->getMessage(),
            ], $storageRoot, $workDir);

            return;
        }

        $dbRebuildStrategy = ProcessDbRebuildStrategy::normalize(
            data_get($process->config, 'db_rebuild_strategy')
        );
        $baseSqlite = ProcessDbRebuildStrategy::resolveSqliteInProject($baseDir);

        $pipeline = new ProjectPipeline($workDir);

        $pipelineSteps = [
            'configure env' => fn () => $pipeline->configureEnvAndDb($dbRebuildStrategy),
            'composer update' => fn () => $pipeline->composerUpdate(),
            'artisan package:discover' => fn () => $pipeline->artisanPackageDiscover(),
            'npm install' => fn () => $pipeline->npmInstall(),
            'npm run build' => fn () => $pipeline->npmBuild(),
            'database rebuild' => fn () => $pipeline->applyDbRebuildStrategy(
                $dbRebuildStrategy,
                $baseSqlite,
                $submissionSqlite
            ),
        ];

        foreach ($pipelineSteps as $label => $run) {
            if (! $run()) {
                $this->markFailed($submission, [
                    'error' => 'Passo "'.$label.'" falhou durante a preparação da submissão.',
                    'pipeline_log' => $pipeline->logsText(),
                ], $storageRoot, $workDir);

                return;
            }
        }

        $root = config('autograding.project_root');
        $mainPy = $root.DIRECTORY_SEPARATOR.'main.py';
        if (! is_file($mainPy)) {
            $this->markFailed($submission, [
                'error' => 'main.py não encontrado.',
                'main_py' => $mainPy,
                'pipeline_log' => $pipeline->logsText(),
            ], $storageRoot, $workDir);

            return;
        }

        $resultPath = $storageRoot.DIRECTORY_SEPARATOR.'result.json';
        if (is_file($resultPath)) {
            @unlink($resultPath);
        }

        $command = [
            config('autograding.python_binary'),
            $mainPy,
            $zipPath,
            $submission->student?->name ?? 'Anonymous',
            '--result-json', $resultPath,
            '--storage-work-dir', $storageRoot,
            '--process-config', $processConfigPath,
            '--working-dir', $workDir,
            '--skip-setup',
        ];

        $proc = new SymfonyProcess($command, $root, null, null, (float) config('autograding.timeout'));
        $proc->run();

        $this->persistReportXml($storageRoot, $workDir);

        $log = Str::limit(trim($proc->getOutput()."\n".$proc->getErrorOutput()), 60000);

        $resultFilePath = null;
        foreach (explode("\n", $log) as $line) {
            if (str_starts_with($line, 'AUTOGRADING_RESULT_JSON=')) {
                $resultFilePath = substr($line, strlen('AUTOGRADING_RESULT_JSON='));
                break;
            }
        }

        if (! $resultFilePath || ! is_file($resultFilePath)) {
            $this->markFailed($submission, [
                'error' => 'Result file não criado pelo main.py',
                'exit_code' => $proc->getExitCode(),
                'output' => $proc->getOutput(),
                'error_output' => $proc->getErrorOutput(),
                'pipeline_log' => $pipeline->logsText(),
            ], $storageRoot, $workDir);

            return;
        }

        try {
            $payload = json_decode(File::get($resultFilePath), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->markFailed($submission, [
                'error' => 'JSON inválido devolvido pelo main.py',
                'detail' => $e->getMessage(),
                'pipeline_log' => $pipeline->logsText(),
            ], $storageRoot, $workDir);

            return;
        }

        $payload = is_array($payload) ? $payload : [];
        $payload['pipeline_log'] = $pipeline->logsText();
        $payload['used_global_base_project'] = $usingFallback;

        $submission->loadMissing(['process.processTestGroups', 'processTestGroup']);

        $results = $payload['results'] ?? null;
        $summary = is_array($results) ? ($results['summary'] ?? []) : [];
        $hasStructuredResults = is_array($results) && isset($summary['total_tests']);

        $displayTests = SuiteAutograding::collectTestsForDisplay(null, $payload);
        $processGroups = $submission->process?->processTestGroups ?? [];

        $overallSuccessRate = isset($summary['success_rate'])
            ? round((float) $summary['success_rate'], 2)
            : SuiteAutograding::suiteSuccessRatePercent($displayTests);

        $grade = SuiteAutograding::computeFinalGrade($processGroups, $displayTests, $payload);

        $testsByGroup = SuiteAutograding::enrichTestsByGroupWithAccess(
            SuiteAutograding::groupTestsByProcessTestGroups($processGroups, $displayTests, $payload),
            $payload,
            false,
            true
        );

        if (is_array($results)) {
            if (! isset($payload['results']) || ! is_array($payload['results'])) {
                $payload['results'] = $results;
            }
            if (! isset($payload['results']['summary']) || ! is_array($payload['results']['summary'])) {
                $payload['results']['summary'] = $summary;
            }
            $payload['results']['summary']['success_rate_percent'] = $overallSuccessRate;
            $payload['results']['summary']['weighted_grade_breakdown'] = SuiteAutograding::weightedGradeBreakdown($testsByGroup);
            if ($grade !== null) {
                $payload['results']['summary']['weighted_final_grade'] = $grade;
            }
        }

        $submissionResult = SubmissionResult::updateOrCreate(
            ['submissions_id' => $submission->id],
            [
                'final_grade' => $grade,
                'success_rate_percent' => $overallSuccessRate,
                'report_sent' => json_encode($payload, JSON_THROW_ON_ERROR),
                'notified_student' => false,
                'notified_teacher' => false,
                'created_at' => now(),
            ]
        );

        if (is_array($results) && isset($results['tests']) && is_array($results['tests'])) {
            TestExecution::where('submission_result_id', $submissionResult->id)->delete();

            foreach ($results['tests'] as $test) {
                TestExecution::create([
                    'submission_result_id' => $submissionResult->id,
                    'test_name' => $test['name'] ?? 'unknown',
                    'status' => $test['status'] ?? 'unknown',
                    'error_message' => $test['message'] ?? null,
                    'execution_logs' => isset($test['logs']) ? json_encode($test['logs']) : null,
                ]);
            }
        }

        $totalTests = (int) ($summary['total_tests'] ?? 0);
        $submission->update([
            'status' => ($hasStructuredResults && $totalTests > 0) ? 'graded' : 'failed',
        ]);

        $this->cleanupGradingTemp($process);
    }

    private function cleanupGradingTemp(Process $process): void
    {
        if (! config('autograding.cleanup_submission_workdir', true)) {
            return;
        }

        $extract = ProcessProjectPaths::gradingExtractPath($process);
        if (is_dir($extract)) {
            File::deleteDirectory($extract);
        }
    }

    private function markFailed(Submission $submission, array $payload, ?string $storageRoot = null, ?string $workDir = null): void
    {
        if ($storageRoot !== null && $workDir !== null) {
            $this->persistReportXml($storageRoot, $workDir);
        }

        SubmissionResult::updateOrCreate(
            ['submissions_id' => $submission->id],
            [
                'final_grade' => null,
                'report_sent' => json_encode($payload, JSON_THROW_ON_ERROR),
                'notified_student' => false,
                'notified_teacher' => false,
                'created_at' => now(),
            ]
        );
        $submission->update(['status' => 'failed']);

        $submission->loadMissing('process');
        if ($submission->process) {
            $this->cleanupGradingTemp($submission->process);
        }
    }

    private function resetDirectory(string $path): void
    {
        if (is_dir($path)) {
            File::deleteDirectory($path);
        }
        File::ensureDirectoryExists($path);
    }

    private function extractZip(string $zip, string $destination): bool
    {
        $za = new \ZipArchive();
        if ($za->open($zip) !== true) {
            return false;
        }
        try {
            return (bool) $za->extractTo($destination);
        } finally {
            $za->close();
        }
    }

    private function findStudentProjectRoot(string $dir, int $maxDepth = 4): ?string
    {
        if ($maxDepth < 0) {
            return null;
        }
        if (is_file($dir.DIRECTORY_SEPARATOR.'composer.json') || is_file($dir.DIRECTORY_SEPARATOR.'artisan')) {
            return $dir;
        }
        foreach (scandir($dir) ?: [] as $e) {
            if ($e === '.' || $e === '..') {
                continue;
            }
            $child = $dir.DIRECTORY_SEPARATOR.$e;
            if (is_dir($child)) {
                if (is_dir($child.DIRECTORY_SEPARATOR.'app')
                    || is_file($child.DIRECTORY_SEPARATOR.'composer.json')
                    || is_file($child.DIRECTORY_SEPARATOR.'artisan')) {
                    return $child;
                }
            }
        }
        foreach (scandir($dir) ?: [] as $e) {
            if ($e === '.' || $e === '..') {
                continue;
            }
            $child = $dir.DIRECTORY_SEPARATOR.$e;
            if (is_dir($child)) {
                $found = $this->findStudentProjectRoot($child, $maxDepth - 1);
                if ($found) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $folders
     * @return list<string>
     */
    private function replaceStudentFolders(string $studentRoot, string $workDir, array $folders): array
    {
        $applied = [];
        foreach ($folders as $folder) {
            $src = $studentRoot.DIRECTORY_SEPARATOR.$folder;
            if (! is_dir($src)) {
                continue;
            }
            $dst = $workDir.DIRECTORY_SEPARATOR.$folder;
            File::ensureDirectoryExists($dst);
            if ($this->mergeDirectory($src, $dst)) {
                $applied[] = $folder;
            }
        }

        return $applied;
    }

    private function mergeDirectory(string $src, string $dst): bool
    {
        if (! is_dir($src)) {
            return false;
        }
        File::ensureDirectoryExists($dst);
        $items = scandir($src) ?: [];
        foreach ($items as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $from = $src.DIRECTORY_SEPARATOR.$entry;
            $to = $dst.DIRECTORY_SEPARATOR.$entry;
            if (is_dir($from)) {
                $this->mergeDirectory($from, $to);

                continue;
            }
            if (filesize($from) === 0 && is_file($to) && filesize($to) > 0) {
                continue;
            }
            @copy($from, $to);
        }

        return true;
    }

    private function mergeStudentComposerRequirements(string $studentRoot, string $workDir): void
    {
        $studentComposerPath = $studentRoot.DIRECTORY_SEPARATOR.'composer.json';
        $workComposerPath = $workDir.DIRECTORY_SEPARATOR.'composer.json';

        if (! is_file($studentComposerPath) || ! is_file($workComposerPath)) {
            return;
        }

        try {
            $work = json_decode((string) file_get_contents($workComposerPath), true, 512, JSON_THROW_ON_ERROR);
            $student = json_decode((string) file_get_contents($studentComposerPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return;
        }

        if (! is_array($work) || ! is_array($student)) {
            return;
        }

        $preserveKeys = ['php', 'laravel/framework'];

        if (! empty($student['require']) && is_array($student['require'])) {
            if (! isset($work['require']) || ! is_array($work['require'])) {
                $work['require'] = [];
            }
            foreach ($student['require'] as $package => $constraint) {
                if (in_array($package, $preserveKeys, true)) {
                    continue;
                }
                $work['require'][$package] = $constraint;
            }
        }

        File::put(
            $workComposerPath,
            json_encode($work, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
        );

        $lockPath = $workDir.DIRECTORY_SEPARATOR.'composer.lock';
        if (is_file($lockPath)) {
            @unlink($lockPath);
        }
    }

    private function mergeStudentFrontendManifest(string $studentRoot, string $workDir): void
    {
        $studentPackagePath = $studentRoot.DIRECTORY_SEPARATOR.'package.json';
        $workPackagePath = $workDir.DIRECTORY_SEPARATOR.'package.json';

        if (is_file($studentPackagePath) && is_file($workPackagePath)) {
            try {
                $work = json_decode((string) file_get_contents($workPackagePath), true, 512, JSON_THROW_ON_ERROR);
                $student = json_decode((string) file_get_contents($studentPackagePath), true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                $work = null;
                $student = null;
            }

            if (is_array($work) && is_array($student)) {
                foreach (['dependencies', 'devDependencies', 'optionalDependencies'] as $section) {
                    if (empty($student[$section]) || ! is_array($student[$section])) {
                        continue;
                    }
                    if (! isset($work[$section]) || ! is_array($work[$section])) {
                        $work[$section] = [];
                    }
                    foreach ($student[$section] as $package => $constraint) {
                        $work[$section][$package] = $constraint;
                    }
                }

                File::put(
                    $workPackagePath,
                    json_encode($work, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
                );
            }
        }

        foreach (['vite.config.js', 'vite.config.ts', 'postcss.config.js', 'tailwind.config.js'] as $configFile) {
            $from = $studentRoot.DIRECTORY_SEPARATOR.$configFile;
            $to = $workDir.DIRECTORY_SEPARATOR.$configFile;
            if (is_file($from) && filesize($from) > 0) {
                @copy($from, $to);
            }
        }

        $lockPath = $workDir.DIRECTORY_SEPARATOR.'package-lock.json';
        if (is_file($lockPath)) {
            @unlink($lockPath);
        }

        $nodeModules = $workDir.DIRECTORY_SEPARATOR.'node_modules';
        if (is_dir($nodeModules)) {
            File::deleteDirectory($nodeModules);
        }
    }

    /**
     * @param  list<string>  $replaced
     */
    private function writeComponentsJson(string $storageRoot, array $replaced): void
    {
        try {
            File::put(
                $storageRoot.DIRECTORY_SEPARATOR.'components.json',
                json_encode($replaced, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
            );
        } catch (JsonException) {
            // não-fatal
        }
    }

    private function writeProcessConfigJson(
        string $storageRoot,
        Submission $submission,
        Process $process,
        $group,
        string $workDir
    ): string {
        $processConfigPath = $storageRoot.DIRECTORY_SEPARATOR.'process-config.json';

        $rawPattern = trim((string) $group->path_pattern);
        $segments = $rawPattern !== ''
            ? preg_split('/[\s,]+/', $rawPattern, -1, PREG_SPLIT_NO_EMPTY)
            : [];
        $testPaths = array_values(array_filter(array_map(
            fn (string $p) => trim(str_replace('\\', '/', $p), '/'),
            $segments
        )));
        if ($testPaths === []) {
            $testPaths = ['tests/tests'];
        }

        $allTestPaths = [];
        foreach ($process->processTestGroups ?? [] as $tg) {
            $rawAll = trim((string) $tg->path_pattern);
            $segmentsAll = $rawAll !== ''
                ? preg_split('/[\s,]+/', $rawAll, -1, PREG_SPLIT_NO_EMPTY)
                : [];
            foreach ($segmentsAll as $seg) {
                $norm = trim(str_replace('\\', '/', (string) $seg), '/');
                if ($norm !== '') {
                    $allTestPaths[] = $norm;
                }
            }
        }

        $discovered = SuiteAutograding::discoverTestPathsFromProject($workDir);
        if ($discovered !== []) {
            // Projeto do docente: só pastas reais em working/ (ignora padrões legados do base-project).
            $allTestPaths = $discovered;
        } else {
            $allTestPaths = array_values(array_unique($allTestPaths));
            if ($allTestPaths === []) {
                $allTestPaths = $testPaths;
            }
        }

        $allTestPaths = SuiteAutograding::filterActivePaths($allTestPaths, $workDir);
        $testPaths = SuiteAutograding::filterActivePaths($testPaths, $workDir);
        if ($testPaths === []) {
            $testPaths = $allTestPaths;
        }

        if ($allTestPaths === []) {
            throw new JsonException('Nenhuma pasta de testes ativa (autograding.json com "active": true).');
        }

        $configPayload = [
            'version' => 1,
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'path_pattern' => $group->path_pattern,
            ],
            'results_visibility' => data_get($process->config, 'results_visibility', 'student'),
            'results_criteria' => data_get($process->config, 'results_criteria', 'final_grade'),
            'db_rebuild_strategy' => ProcessDbRebuildStrategy::normalize(
                data_get($process->config, 'db_rebuild_strategy')
            ),
            'all_test_paths' => $allTestPaths,
            'test_paths' => $allTestPaths,
            'weights' => [
                'process_percent' => config('autograding.process_weight_percent'),
            ],
            'visibility' => $group->visibility,
        ];

        File::put(
            $processConfigPath,
            json_encode($configPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
        );

        return $processConfigPath;
    }

    private function persistReportXml(string $storageRoot, string $workDir): void
    {
        $dest = $storageRoot.DIRECTORY_SEPARATOR.'report.xml';
        $sources = [
            $storageRoot.DIRECTORY_SEPARATOR.'report.xml',
            $workDir.DIRECTORY_SEPARATOR.'junit_autograding.xml',
        ];

        foreach ($sources as $src) {
            if (is_file($src) && filesize($src) > 0) {
                if (realpath($src) !== realpath($dest)) {
                    @copy($src, $dest);
                }

                return;
            }
        }
    }
}
