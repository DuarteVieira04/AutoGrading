<?php

namespace App\Jobs;

use App\Models\Submission;
use App\Services\AutoGradingRunner;
use App\Services\SubmissionGradingNotifier;
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

    public function __construct(public int $submissionId)
    {
        $queue = config('autograding.queue');
        if (is_string($queue) && $queue !== '') {
            $this->onQueue($queue);
        }
    }

    public function handle(AutoGradingRunner $runner, SubmissionGradingNotifier $notifier): void
    {
        $submission = Submission::query()
            ->with(['process.teacher', 'processTestGroup', 'student', 'submissionResult'])
            ->find($this->submissionId);
        if (! $submission) {
            return;
        }

        $runner->grade($submission->fresh(['process.processTestGroups', 'processTestGroup', 'student', 'submissionResult']));

        $notifier->notify($submission->fresh([
            'process.teacher',
            'processTestGroup',
            'student',
            'submissionResult',
        ]));
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
