<?php

namespace App\Console\Commands;

use App\Mail\SubmissionGradedMail;
use App\Models\Submission;
use App\Services\SubmissionGradingNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestSubmissionMailCommand extends Command
{
    protected $signature = 'autograding:test-mail
                            {submission : ID da submissão}
                            {--role=student : student ou teacher}
                            {--to= : Enviar para este email (ignora MAIL_OVERRIDE_TO)}
                            {--notify : Usar SubmissionGradingNotifier (respeita flags do processo)}';

    protected $description = 'Envia email de teste de submissão corrigida (útil com Mailpit ou MAIL_OVERRIDE_TO)';

    public function handle(SubmissionGradingNotifier $notifier): int
    {
        $submission = Submission::query()
            ->with(['process.teacher', 'processTestGroup', 'student', 'submissionResult'])
            ->find($this->argument('submission'));

        if (! $submission) {
            $this->error('Submissão não encontrada.');

            return self::FAILURE;
        }

        if ($this->option('notify')) {
            $notifier->notify($submission);
            $this->info('Notifier executado (ver Mailpit, log ou MAIL_OVERRIDE_TO).');

            return self::SUCCESS;
        }

        $role = $this->option('role');
        if (! in_array($role, ['student', 'teacher'], true)) {
            $this->error('--role deve ser student ou teacher');

            return self::FAILURE;
        }

        $intended = $role === 'teacher'
            ? ($submission->process?->teacher?->email ?? 'teacher@example.com')
            : ($submission->student?->email ?? 'student@example.com');

        $to = $this->option('to') ?: config('mail.override_to') ?: $intended;

        Mail::to($to)->send(new SubmissionGradedMail($submission, $role));

        $this->info("Email de teste enviado para {$to} (destinatário simulado: {$intended}, papel: {$role}).");
        $this->line('Com Mailpit: http://localhost:8025 — Com log: storage/logs/laravel.log (MAIL_MAILER=log)');

        return self::SUCCESS;
    }
}
