<?php

namespace App\Services;

use App\Models\Submission;
use App\Models\SubmissionResult;
use App\Models\TestExecution;
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
            $submission->update([
                'status' => 'failed',
            ]);

            return;
        }

        $zipPath = Storage::path($submission->zip_file_path);
        if (! is_file($zipPath)) {
            $submission->update([
                'status' => 'failed',
            ]);

            return;
        }

        $submission->loadMissing(['process', 'processTestGroup']);

        if (! $submission->process_test_group_id || ! $submission->processTestGroup) {
            SubmissionResult::updateOrCreate(
                ['submissions_id' => $submission->id],
                [
                    'final_grade' => null,
                    'report_sent' => json_encode([
                        'error' => 'Submissão sem grupo de testes (process_test_group_id). Volte a submeter escolhendo um grupo.',
                    ], JSON_THROW_ON_ERROR),
                    'notified_student' => false,
                    'notified_teacher' => false,
                    'created_at' => now(),
                ]
            );
            $submission->update(['status' => 'failed']);

            return;
        }

        $submission->update(['status' => 'processing']);

        $root = config('autograding.project_root');
        $mainPy = $root.DIRECTORY_SEPARATOR.'main.py';

        if (! is_file($mainPy)) {
            SubmissionResult::updateOrCreate(
                ['submissions_id' => $submission->id],
                [
                    'final_grade' => null,
                    'report_sent' => json_encode([
                        'error' => 'Autograding main.py not found',
                        'project_root' => $root,
                        'main_py' => $mainPy,
                    ], JSON_THROW_ON_ERROR),
                    'notified_student' => false,
                    'notified_teacher' => false,
                    'created_at' => now(),
                ]
            );

            $submission->update([
                'status' => 'failed',
            ]);

            return;
        }

        $studentName = $submission->student?->name ?? 'Anonymous';

        $workDir = storage_path('app/autograding/submission-'.$submission->id);
        File::ensureDirectoryExists($workDir);

        $resultPath = $workDir.DIRECTORY_SEPARATOR.'result.json';
        if (is_file($resultPath)) {
            @unlink($resultPath);
        }

        $group = $submission->processTestGroup;
        $process = $submission->process;
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

        $processConfigPath = $workDir.DIRECTORY_SEPARATOR.'process-config.json';
        $configPayload = [
            'version' => 1,
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'path_pattern' => $group->path_pattern,
            ],
            'results_visibility' => data_get($process?->config, 'results_visibility', 'student'),
            'results_criteria' => data_get($process?->config, 'results_criteria', 'final_grade'),
            'test_paths' => $testPaths,
            'weights' => [
                'process_percent' => config('autograding.process_weight_percent'),
            ],
            'visibility' => $group->visibility,
        ];

        try {
            File::put(
                $processConfigPath,
                json_encode($configPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
            );
        } catch (JsonException $e) {
            SubmissionResult::updateOrCreate(
                ['submissions_id' => $submission->id],
                [
                    'final_grade' => null,
                    'report_sent' => json_encode([
                        'error' => 'Falha ao gravar process-config.json',
                        'detail' => $e->getMessage(),
                    ], JSON_THROW_ON_ERROR),
                    'notified_student' => false,
                    'notified_teacher' => false,
                    'created_at' => now(),
                ]
            );
            $submission->update(['status' => 'failed']);

            return;
        }

        $componentsPath = $workDir.DIRECTORY_SEPARATOR.'components.json';
        $components = ['app', 'routes', 'resources'];
        File::put($componentsPath, json_encode(array_values($components), JSON_THROW_ON_ERROR));

        $archivedZip = $workDir.DIRECTORY_SEPARATOR.'submission.zip';

        $command = [
            config('autograding.python_binary'),
            $mainPy,
            $zipPath,
            $studentName,
            '--result-json',
            $resultPath,
            '--storage-work-dir',
            $workDir,
            '--archive-submitted-zip',
            $archivedZip,
            '--components-json',
            $componentsPath,
            '--process-config',
            $processConfigPath,
        ];

        $proc = new SymfonyProcess($command, $root, null, null, (float) config('autograding.timeout'));
        $proc->run();

        $log = Str::limit(trim($proc->getOutput()."\n".$proc->getErrorOutput()), 60000);

        $resultFilePath = null;
        foreach (explode("\n", $log) as $line) {
            if (str_starts_with($line, 'AUTOGRADING_RESULT_JSON=')) {
                $resultFilePath = substr($line, strlen('AUTOGRADING_RESULT_JSON='));
                break;
            }
        }

        if (! $resultFilePath || ! is_file($resultFilePath)) {
            SubmissionResult::updateOrCreate(
                ['submissions_id' => $submission->id],
                [
                    'final_grade' => null,
                    'report_sent' => json_encode(['error' => 'Result file not created', 'exit_code' => $proc->getExitCode(), 'output' => $proc->getOutput(), 'error_output' => $proc->getErrorOutput()]),
                    'notified_student' => false,
                    'notified_teacher' => false,
                    'created_at' => now(),
                ]
            );

            $submission->update(['status' => 'failed']);

            return;
        }

        try {
            $payload = json_decode(File::get($resultFilePath), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            SubmissionResult::updateOrCreate(
                ['submissions_id' => $submission->id],
                [
                    'final_grade' => null,
                    'report_sent' => json_encode(['error' => 'Invalid JSON returned by autograding.', 'detail' => $e->getMessage(), 'output' => $proc->getOutput(), 'error_output' => $proc->getErrorOutput()]),
                    'notified_student' => false,
                    'notified_teacher' => false,
                    'created_at' => now(),
                ]
            );

            $submission->update(['status' => 'failed']);

            return;
        }

        $results = $payload['results'] ?? null;
        $summary = is_array($results) ? ($results['summary'] ?? []) : [];
        $grade = isset($summary['success_rate']) ? round((float) $summary['success_rate'], 2) : null;
        $hasStructuredResults = is_array($results) && isset($summary['total_tests']);

        $submissionResult = SubmissionResult::updateOrCreate(
            ['submissions_id' => $submission->id],
            [
                'final_grade' => $grade,
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

        $submission->update(['status' => $hasStructuredResults ? 'graded' : 'failed']);
    }
}
