<?php

namespace App\Jobs;

use App\Models\Submission;
use App\Services\AutoGradingRunner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GradeSubmissionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $submissionId) {}

    public function handle(AutoGradingRunner $runner): void
    {
        $submission = Submission::query()
            ->with(['process', 'processTestGroup'])
            ->find($this->submissionId);
        if (! $submission) {
            return;
        }

        $runner->grade($submission);
    }

    public function failed(?Throwable $exception): void
    {
        $submission = Submission::query()->find($this->submissionId);

        if ($submission && $submission->status === 'processing') {
            $submission->update([
                'status' => 'failed',
            ]);
        }
    }
}
