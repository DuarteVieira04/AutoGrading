<?php

namespace App\Services;

use App\Mail\SubmissionGradedMail;
use App\Models\Submission;
use App\Models\SubmissionResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SubmissionGradingNotifier
{
    public function notify(Submission $submission): void
    {
        if (! config('autograding.notify_on_complete', true)) {
            return;
        }

        $submission->loadMissing([
            'process.teacher',
            'student',
            'submissionResult',
        ]);

        $process = $submission->process;
        if (! $process?->email_notification) {
            Log::info('Submission grading email skipped: email_notification disabled on process', [
                'submission_id' => $submission->id,
                'process_id' => $process?->id,
            ]);

            return;
        }

        $result = $submission->submissionResult;
        if (! $result) {
            return;
        }

        if (! $result->notified_student) {
            $this->sendToStudent($submission, $result);
        }

        if (! $result->notified_teacher) {
            $this->sendToTeacher($submission, $result);
        }
    }

    protected function sendToStudent(Submission $submission, SubmissionResult $result): void
    {
        $student = $submission->student;
        if (! $student?->email) {
            return;
        }

        if (! $this->shouldNotifyStudent($submission)) {
            $result->update(['notified_student' => true]);

            return;
        }

        $to = $this->resolveRecipient($student->email);

        try {
            Mail::to($to)->send(new SubmissionGradedMail($submission, 'student'));
            $result->update(['notified_student' => true]);
        } catch (\Throwable $e) {
            Log::error('Failed to send submission graded email to student', [
                'submission_id' => $submission->id,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function sendToTeacher(Submission $submission, SubmissionResult $result): void
    {
        $teacher = $submission->process?->teacher;
        if (! $teacher?->email) {
            return;
        }

        $to = $this->resolveRecipient($teacher->email);

        try {
            Mail::to($to)->send(new SubmissionGradedMail($submission, 'teacher'));
            $result->update(['notified_teacher' => true]);
        } catch (\Throwable $e) {
            Log::error('Failed to send submission graded email to teacher', [
                'submission_id' => $submission->id,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function shouldNotifyStudent(Submission $submission): bool
    {
        $visibility = data_get($submission->process?->config, 'results_visibility', 'student');

        return in_array($visibility, ['student', 'both'], true);
    }

    protected function resolveRecipient(string $intendedEmail): string
    {
        $override = config('mail.override_to');

        return is_string($override) && trim($override) !== ''
            ? trim($override)
            : $intendedEmail;
    }
}
