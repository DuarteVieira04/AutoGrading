<?php

namespace App\Jobs;

use App\Models\ProjectSubmissions;
use App\Services\AutoGradingRunner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GradeProjectSubmissionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $projectSubmissionId) {}

    public function handle(AutoGradingRunner $runner): void
    {
        $submission = ProjectSubmissions::query()->find($this->projectSubmissionId);
        if (! $submission) {
            return;
        }

        $runner->grade($submission);
    }

    public function failed(?Throwable $exception): void
    {
        $submission = ProjectSubmissions::query()->find($this->projectSubmissionId);
        if ($submission && $submission->status === 'processing') {
            $submission->update([
                'status' => 'failed',
                'feedback' => [
                    'error' => 'Fila de correção falhou.',
                    'detail' => $exception?->getMessage(),
                ],
            ]);
        }
    }
}
