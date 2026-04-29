<?php

namespace App\Services;

use App\Models\Submission;
use App\Models\SubmissionResult;
use App\Models\TestExecution;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

        $componentsPath = $workDir.DIRECTORY_SEPARATOR.'components.json';
        $components = ['app', 'routes', 'resources'];
        File::put($componentsPath, json_encode(array_values($components), JSON_THROW_ON_ERROR));

        $command = [
            config('autograding.python_binary'),
            $mainPy,
            $zipPath,
            $studentName,
            '--result-json',
            $resultPath,
            '--components-json',
            $componentsPath,
        ];

        $proc = new SymfonyProcess($command, $root, null, null, (float) config('autograding.timeout'));
        $proc->run();

        $log = Str::limit(trim($proc->getOutput()."\n".$proc->getErrorOutput()), 60000);

        if (! is_file($resultPath)) {
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
            $payload = json_decode(File::get($resultPath), true, 512, JSON_THROW_ON_ERROR);
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
