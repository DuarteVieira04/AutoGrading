<?php

namespace App\Mail;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubmissionGradedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Submission $submission,
        public string $recipientRole,
    ) {
        $this->submission->loadMissing([
            'process',
            'processTestGroup',
            'student',
            'submissionResult',
        ]);
    }

    public function envelope(): Envelope
    {
        $processName = $this->submission->process?->process_name ?? __('Processo de avaliação');
        $status = $this->submission->status === 'graded'
            ? __('corrigida')
            : __('processada com erro');

        return new Envelope(
            subject: __('Submissão :status — :process', [
                'status' => $status,
                'process' => $processName,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.submission-graded',
            with: [
                'submission' => $this->submission,
                'recipientRole' => $this->recipientRole,
                'result' => $this->submission->submissionResult,
                'showUrl' => route('submissions.show', $this->submission),
            ],
        );
    }
}
