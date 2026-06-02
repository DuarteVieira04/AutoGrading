<?php

namespace App\Providers;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(CommandStarting::class, function (CommandStarting $event): void {
            if ($event->command !== 'serve' || ! $this->app->runningInConsole()) {
                return;
            }
            $postMax = self::iniSizeToBytes(ini_get('post_max_size') ?: '8M');
            if ($postMax >= 64 * 1024 * 1024) {
                return;
            }
            fwrite(STDERR, PHP_EOL.'[AutoGrading] post_max_size='.ini_get('post_max_size')
                .' — insuficiente para o ZIP do projeto docente (~65 MB).'.PHP_EOL);
            fwrite(STDERR, '  Arranca o servidor com:  ./serve   ou   composer run serve'.PHP_EOL);
            fwrite(STDERR, '  (evita «php artisan serve» sem php.ini do projeto)'.PHP_EOL.PHP_EOL);
            exit(1);
        });

        // Para SQLite (BD principal), ativa WAL + busy_timeout curto em cada conexão.
        // WAL elimina o lock global entre readers/writers, o que evita que o queue worker
        // (a fazer escritas frequentes na BD durante o grading) bloqueie os pedidos HTTP
        // do utilizador (upload/poll). Sem WAL, o "busy_timeout=60000" do Laravel 11 faz
        // o utilizador esperar até 60s pelo lock.
        Event::listen(function (ConnectionEstablished $event) {
            $connection = $event->connection;
            if ($connection->getDriverName() !== 'sqlite') {
                return;
            }
            try {
                $connection->statement('PRAGMA journal_mode=WAL');
                $connection->statement('PRAGMA synchronous=NORMAL');
                $connection->statement('PRAGMA busy_timeout=10000');
            } catch (\Throwable) {
                // ignorar falhas pontuais (PRAGMA não fatal)
            }
        });
    }

    private static function iniSizeToBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return PHP_INT_MAX;
        }
        $unit = strtolower(substr($value, -1));
        $number = (float) $value;
        return match ($unit) {
            'g' => (int) ($number * 1024 * 1024 * 1024),
            'm' => (int) ($number * 1024 * 1024),
            'k' => (int) ($number * 1024),
            default => (int) $number,
        };
    }
}
