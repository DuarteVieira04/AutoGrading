<?php

namespace App\Support;

use App\Models\Submission;

final class SubmissionRowPresenter
{
    public const PASS_THRESHOLD_PERCENT = 50.0;

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => __('Pendente'),
            'processing' => __('Em correção'),
            'graded' => __('Corrigida'),
            'failed' => __('Falhou'),
            default => ucfirst($status),
        };
    }

    public static function testStatusLabel(string $status): string
    {
        return match ($status) {
            'passed' => __('Aprovado'),
            'failed' => __('Reprovado'),
            'skipped' => __('Ignorado'),
            'unknown' => __('Desconhecido'),
            default => ucfirst($status),
        };
    }

    /**
     * Aprovado na avaliação quando a nota em pontos (ou taxa global) é ≥ 50%.
     */
    public static function evaluationPassed(
        string $status,
        ?float $finalGradePoints,
        int $maxGradePoints,
        ?float $successRatePercent,
        bool $hasSummary = true
    ): bool {
        if ($status !== 'graded' || ! $hasSummary) {
            return false;
        }

        if ($finalGradePoints !== null && $maxGradePoints > 0) {
            return ($finalGradePoints / $maxGradePoints) * 100 >= self::PASS_THRESHOLD_PERCENT;
        }

        if ($successRatePercent !== null) {
            return (float) $successRatePercent >= self::PASS_THRESHOLD_PERCENT;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>|null  $config
     */
    public static function processIsEvaluation(?array $config): bool
    {
        if (! is_array($config)) {
            return true;
        }
        if (array_key_exists('is_evaluation', $config)) {
            return (bool) $config['is_evaluation'];
        }

        return ($config['results_criteria'] ?? 'final_grade') === 'final_grade';
    }

    /**
     * @param  array<string, mixed>|null  $config
     */
    public static function processEvaluationMaxGrade(?array $config): ?float
    {
        $value = is_array($config) ? ($config['evaluation_max_grade'] ?? null) : null;

        return is_numeric($value) && (float) $value > 0 ? (float) $value : null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function forSubmission(Submission $submission): array
    {
        $submission->loadMissing([
            'process.processTestGroups',
            'processTestGroup',
            'submissionResult.testExecutions',
        ]);

        $row = $submission;
        $result = $row->submissionResult;
        $summary = data_get($result?->report_sent_payload, 'results.summary', []);
        $tests = $result?->testExecutions ?? collect();
        $payloadTests = collect(data_get($result?->report_sent_payload, 'results.tests', []));

        if ($tests->isEmpty() && $payloadTests->isNotEmpty()) {
            $tests = $payloadTests->map(function ($test) {
                return (object) [
                    'test_name' => $test['name'] ?? __('Teste sem nome'),
                    'status' => $test['status'] ?? 'unknown',
                    'error_message' => $test['message'] ?? null,
                ];
            });
        }

        $hasTests = $tests->isNotEmpty();
        $totalTests = $hasTests ? $tests->count() : ($summary['total_tests'] ?? 0);
        $passedCount = $hasTests
            ? $tests->where('status', 'passed')->count()
            : ($summary['successful'] ?? ($summary['passed'] ?? 0));
        $failedCount = $hasTests ? $totalTests - $passedCount : ($summary['failed'] ?? null);
        $hasSummary = $hasTests || ! empty($summary);

        $rowPayload = $result?->report_sent_payload ?? [];
        $rowPayload = is_array($rowPayload) ? $rowPayload : [];
        $rowTestsByGroup = SuiteAutograding::enrichTestsByGroupWithAccess(
            SuiteAutograding::groupTestsByProcessTestGroups(
                $row->process?->processTestGroups ?? [],
                SuiteAutograding::collectTestsForDisplay($result, $rowPayload),
                $rowPayload
            ),
            $rowPayload,
            true,
            false
        );
        $canViewRowDetails = SuiteAutograding::canViewAnySuiteDetails($rowTestsByGroup, $rowPayload, true, false);

        $maxGradePoints = SuiteAutograding::totalMaxPoints($rowTestsByGroup);
        $finalGradePoints = $result?->final_grade !== null ? (float) $result->final_grade : null;
        $successRatePercent = $result?->success_rate_percent
            ?? data_get($summary, 'success_rate_percent')
            ?? data_get($summary, 'success_rate');
        $successRatePercent = $successRatePercent !== null ? (float) $successRatePercent : null;

        $processConfig = $row->process?->config ?? null;
        $processConfig = is_array($processConfig) ? $processConfig : null;
        $isEvaluation = self::processIsEvaluation($processConfig);
        $evaluationMaxGrade = self::processEvaluationMaxGrade($processConfig);

        if ($isEvaluation && $evaluationMaxGrade !== null && $finalGradePoints !== null && $maxGradePoints > 0) {
            $displayFinalGrade = round($finalGradePoints / $maxGradePoints * $evaluationMaxGrade, 2);
            $displayMaxGrade = $evaluationMaxGrade;
            $displayGradeUnit = __('valores');
        } else {
            $displayFinalGrade = $finalGradePoints;
            $displayMaxGrade = (float) $maxGradePoints;
            $displayGradeUnit = __('pontos');
        }

        $passed = self::evaluationPassed(
            (string) $row->status,
            $finalGradePoints,
            $maxGradePoints,
            $successRatePercent,
            $hasSummary
        );

        return compact(
            'row',
            'result',
            'hasSummary',
            'passed',
            'passedCount',
            'totalTests',
            'canViewRowDetails',
            'maxGradePoints',
            'finalGradePoints',
            'successRatePercent',
            'isEvaluation',
            'evaluationMaxGrade',
            'displayFinalGrade',
            'displayMaxGrade',
            'displayGradeUnit',
        );
    }

    /**
     * Dados para submissions.show e partials associados.
     *
     * @param  list<array<string, mixed>>  $testsByGroup
     * @param  list<object|array<string, mixed>>  $displayTests
     * @return array<string, mixed>
     */
    public static function forShowPage(
        Submission $submission,
        array $testsByGroup,
        array $displayTests,
        ?float $overallSuccessRate,
        bool $canViewFinalGrade,
    ): array {
        $base = self::forSubmission($submission);
        $tests = collect($displayTests);
        $summary = data_get($base['result']?->report_sent_payload, 'results.summary', []);
        $hasTestRows = $tests->isNotEmpty();
        $hasSummary = $base['hasSummary'];
        $totalTests = $base['totalTests'];
        $passedTests = $base['passedCount'];
        $failedTests = $hasTestRows
            ? $totalTests - $passedTests
            : ($summary['failed'] ?? null);

        $finalGradePoints = $base['finalGradePoints'];
        $maxGradePoints = $base['maxGradePoints'];
        $rate = $overallSuccessRate ?? $base['successRatePercent'];

        $passed = self::evaluationPassed(
            (string) $submission->status,
            $finalGradePoints,
            $maxGradePoints,
            $rate,
            $hasSummary
        );

        return array_merge($base, [
            'submission' => $submission,
            'testsByGroup' => $testsByGroup,
            'tests' => $tests,
            'hasTestRows' => $hasTestRows,
            'passedTests' => $passedTests,
            'failedTests' => $failedTests,
            'overallSuccessRate' => $rate,
            'canViewFinalGrade' => $canViewFinalGrade,
            'passed' => $passed,
            'finalGradePoints' => $finalGradePoints,
            'maxGradePoints' => $maxGradePoints,
            'isEvaluation' => $base['isEvaluation'],
            'evaluationMaxGrade' => $base['evaluationMaxGrade'],
            'displayFinalGrade' => $base['displayFinalGrade'],
            'displayMaxGrade' => $base['displayMaxGrade'],
            'displayGradeUnit' => $base['displayGradeUnit'],
            'logsTests' => $tests->filter(fn ($test) => ! empty($test->execution_logs)),
        ]);
    }
}
