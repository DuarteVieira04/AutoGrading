<?php

namespace App\Jobs;

use App\Models\Submission;
use App\Models\SubmissionResult;
use App\Services\AutoGradingRunner;
use App\Services\SubmissionGradingNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class GradeSubmissionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    /** Pipeline (composer update + npm install + build + tests) pode demorar minutos. */
    public int $timeout = 1900;

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

        $submission = $submission->fresh(['process.processTestGroups', 'processTestGroup', 'student', 'submissionResult']);

        // Liberta o lock SQLite da plataforma durante composer/npm/migrate (minutos).
        DB::disconnect();

        $runner->grade($submission);

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

        if (! $submission || $submission->status !== 'processing') {
            return;
        }

        $message = $exception?->getMessage() ?? 'Correção interrompida.';

        SubmissionResult::updateOrCreate(
            ['submissions_id' => $submission->id],
            [
                'final_grade' => null,
                'report_sent' => json_encode(['error' => $message], JSON_THROW_ON_ERROR),
                'notified_student' => false,
                'notified_teacher' => false,
                'created_at' => now(),
            ]
        );

        $submission->update(['status' => 'failed']);
    }
}
