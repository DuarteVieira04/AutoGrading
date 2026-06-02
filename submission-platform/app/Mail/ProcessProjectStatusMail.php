<?php

namespace App\Mail;

use App\Models\Process;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProcessProjectStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Process $process,
        public bool $ok,
        public ?string $error,
        public string $log,
    ) {
        $this->process->loadMissing('teacher');
    }

    public function envelope(): Envelope
    {
        $name = $this->process->process_name ?: __('Processo de avaliação');
        $subject = $this->ok
            ? __('Projeto preparado com sucesso — :p', ['p' => $name])
            : __('Projeto com erro — :p', ['p' => $name]);

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.process-project-status',
            with: [
                'process' => $this->process,
                'ok' => $this->ok,
                'error' => $this->error,
                'log' => \Illuminate\Support\Str::limit($this->log, 6000),
            ],
        );
    }
}
