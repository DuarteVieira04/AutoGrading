<?php

namespace App\Services;

use App\Mail\ProcessProjectStatusMail;
use App\Models\Process;
use App\Support\ProcessDbRebuildStrategy;
use App\Support\ProcessProjectPaths;
use App\Support\SuiteAutograding;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;

/**
 * Prepara a área de trabalho do processo a partir do ZIP carregado pelo docente:
 * extrai → copia para working/ → composer update → npm install → npm run build
 * → configura .env/sqlite → migrate:fresh → phpunit (smoke).
 */
final class ProcessProjectPreparer
{
    public function prepare(Process $process, bool $silentMail = false): bool
    {
        $process->loadMissing('teacher');
        $process->forceFill([
            'project_status' => Process::PROJECT_STATUS_PREPARING,
            'project_error' => null,
            'project_log' => null,
        ])->save();

        $zipPath = $process->project_zip_path;
        $zipAbs = $zipPath ? storage_path('app'.DIRECTORY_SEPARATOR.$zipPath) : null;
        if (! $zipAbs || ! is_file($zipAbs)) {
            return $this->fail($process, 'Não foi encontrado o ZIP do projeto associado ao processo.', '', $silentMail);
        }

        $base = ProcessProjectPaths::basePath($process);
        $working = ProcessProjectPaths::workingPath($process);

        if (! $this->safeReplaceDir($base) || ! $this->extractZip($zipAbs, $base)) {
            return $this->fail($process, 'Falhou a extração do ZIP do projeto.', '', $silentMail);
        }

        $base = $this->collapseSingleRootDir($base);

        if (! $this->validateLaravelProject($base)) {
            return $this->fail($process, 'O ZIP não parece ser um projeto Laravel (faltam composer.json e/ou artisan).', '', $silentMail);
        }

        if (! $this->safeReplaceDir($working) || ! $this->copyDir($base, $working)) {
            return $this->fail($process, 'Falhou a cópia do projeto para a pasta de validação (working).', '', $silentMail);
        }

        $dbRebuildStrategy = ProcessDbRebuildStrategy::normalize(
            data_get($process->config, 'db_rebuild_strategy')
        );
        $baseSqlite = ProcessDbRebuildStrategy::resolveSqliteInProject($base);

        $pipeline = new ProjectPipeline($working);

        $steps = [
            'configure env' => fn () => $pipeline->configureEnvAndDb($dbRebuildStrategy),
            'composer update' => fn () => $pipeline->composerUpdate(),
            'artisan package:discover' => fn () => $pipeline->artisanPackageDiscover(),
            'npm install' => fn () => $pipeline->npmInstall(),
            'npm run build' => fn () => $pipeline->npmBuild(),
            'database rebuild' => fn () => $pipeline->migrateFresh(),
            'phpunit' => fn () => $pipeline->phpunit(
                SuiteAutograding::discoverTestPathsFromProject($working),
                $working.DIRECTORY_SEPARATOR.'junit_autograding.xml'
            ),
        ];

        foreach ($steps as $label => $run) {
            if (! $run()) {
                return $this->fail(
                    $process,
                    'Passo "'.$label.'" falhou. Verifique o relatório abaixo.',
                    $pipeline->logsText(),
                    $silentMail
                );
            }
        }

        $process->forceFill([
            'project_status' => Process::PROJECT_STATUS_READY,
            'project_error' => null,
            'project_log' => $pipeline->logsText(),
            'project_base_path' => ProcessProjectPaths::relative($base),
            'project_working_path' => ProcessProjectPaths::relative($working),
            'project_prepared_at' => now(),
        ])->save();

        $this->notify($process, true, $pipeline->logsText(), $silentMail);

        return true;
    }

    private function fail(Process $process, string $error, string $log, bool $silentMail): bool
    {
        $process->forceFill([
            'project_status' => Process::PROJECT_STATUS_FAILED,
            'project_error' => $error,
            'project_log' => $log,
        ])->save();

        $this->notify($process, false, $log, $silentMail, $error);

        return false;
    }

    private function notify(Process $process, bool $ok, string $log, bool $silentMail, ?string $error = null): void
    {
        if ($silentMail) {
            return;
        }
        $email = $process->teacher?->email;
        if (! $email) {
            return;
        }
        try {
            Mail::to($email)->send(new ProcessProjectStatusMail($process, $ok, $error, $log));
        } catch (\Throwable $e) {
            \Log::warning('ProcessProjectStatusMail falhou: '.$e->getMessage());
        }
    }

    private function safeReplaceDir(string $path): bool
    {
        try {
            if (is_dir($path)) {
                File::deleteDirectory($path);
            }
            File::ensureDirectoryExists($path);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function extractZip(string $zip, string $destination): bool
    {
        $za = new \ZipArchive();
        if ($za->open($zip) !== true) {
            return false;
        }
        try {
            return (bool) $za->extractTo($destination);
        } finally {
            $za->close();
        }
    }

    private function collapseSingleRootDir(string $path): string
    {
        $entries = [];
        foreach (scandir($path) ?: [] as $e) {
            if ($e !== '.' && $e !== '..') {
                $entries[] = $e;
            }
        }
        if (count($entries) === 1) {
            $only = $path.DIRECTORY_SEPARATOR.$entries[0];
            if (is_dir($only) && ! is_file($only.DIRECTORY_SEPARATOR.'composer.json') && ! is_file($only.DIRECTORY_SEPARATOR.'artisan')) {
                return $path;
            }
            if (is_dir($only)) {
                $tmp = $path.'.unwrap-'.bin2hex(random_bytes(4));
                rename($only, $tmp);
                File::deleteDirectory($path);
                rename($tmp, $path);
            }
        }

        return $path;
    }

    private function validateLaravelProject(string $dir): bool
    {
        return is_file($dir.DIRECTORY_SEPARATOR.'composer.json')
            && is_file($dir.DIRECTORY_SEPARATOR.'artisan');
    }

    private function copyDir(string $source, string $destination): bool
    {
        try {
            return File::copyDirectory($source, $destination);
        } catch (\Throwable) {
            return false;
        }
    }
}
