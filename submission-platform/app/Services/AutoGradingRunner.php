<?php

namespace App\Services;

use App\Models\GradingProcess;
use App\Models\ProjectSubmissions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class AutoGradingRunner
{
    public function grade(ProjectSubmissions $submission): void
    {
        if (! config('autograding.enabled')) {
            $submission->update([
                'status' => 'failed',
                'feedback' => ['error' => 'Correção automática desativada (AUTOGRADING_ENABLED=false).'],
            ]);

            return;
        }

        $zipPath = Storage::disk('public')->path($submission->file_path);
        if (! is_file($zipPath)) {
            $submission->update([
                'status' => 'failed',
                'feedback' => ['error' => 'Ficheiro da submissão não encontrado no servidor.'],
            ]);

            return;
        }

        $submission->update(['status' => 'processing']);

        $root = config('autograding.project_root');
        $mainPy = $root.DIRECTORY_SEPARATOR.'main.py';

        if (! is_file($mainPy)) {
            $submission->update([
                'status' => 'failed',
                'feedback' => ['error' => "main.py não encontrado. Configure AUTOGRADING_PROJECT_ROOT (atual: {$root})."],
            ]);

            return;
        }

        $studentName = $submission->student?->user?->name ?? 'Anonymous';

        $workDir = storage_path('app/autograding/submission-'.$submission->id);
        File::ensureDirectoryExists($workDir);

        $resultPath = $workDir.DIRECTORY_SEPARATOR.'result.json';
        if (is_file($resultPath)) {
            @unlink($resultPath);
        }

        $componentsPath = $workDir.DIRECTORY_SEPARATOR.'components.json';
        $processModel = $submission->gradingProcess ?? GradingProcess::active();
        $components = $processModel?->components ?? ['app', 'routes', 'resources'];
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

        $proc = new Process($command, $root, null, null, (float) config('autograding.timeout'));
        $proc->run();

        $log = Str::limit(trim($proc->getOutput()."\n".$proc->getErrorOutput()), 60000);

        if (! is_file($resultPath)) {
            $submission->update([
                'status' => 'failed',
                'grading_log' => $log,
                'feedback' => [
                    'error' => 'O script Python não criou o ficheiro de resultados.',
                    'exit_code' => $proc->getExitCode(),
                ],
            ]);

            return;
        }

        try {
            $payload = json_decode(File::get($resultPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $submission->update([
                'status' => 'failed',
                'grading_log' => $log,
                'feedback' => ['error' => 'JSON inválido devolvido pelo autograding.', 'detail' => $e->getMessage()],
            ]);

            return;
        }

        $results = $payload['results'] ?? null;
        $summary = is_array($results) ? ($results['summary'] ?? []) : [];
        $grade = isset($summary['success_rate']) ? round((float) $summary['success_rate'], 2) : null;

        $hasStructuredResults = is_array($results) && isset($summary['total_tests']);

        $submission->update([
            'status' => $hasStructuredResults ? 'graded' : 'failed',
            'feedback' => $payload,
            'grade' => $grade,
            'grading_log' => $log,
        ]);
    }
}
