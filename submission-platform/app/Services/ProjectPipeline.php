<?php

namespace App\Services;

use App\Support\ProcessDbRebuildStrategy;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process as SymfonyProcess;

/**
 * Encadeia comandos típicos de um projeto Laravel (composer/npm/migrate/seed/phpunit)
 * numa pasta de trabalho. Cada passo regista o stdout/stderr para fins de relatório.
 */
final class ProjectPipeline
{
    /** @var list<array{step:string, exit_code:int, output:string, success:bool, duration:float}> */
    private array $steps = [];

    public function __construct(
        public readonly string $workDir,
        public readonly int $timeoutSeconds = 1800,
    ) {
        File::ensureDirectoryExists($workDir);
    }

    /** @return list<array<string, mixed>> */
    public function steps(): array
    {
        return $this->steps;
    }

    public function lastError(): ?string
    {
        for ($i = count($this->steps) - 1; $i >= 0; $i--) {
            if (! $this->steps[$i]['success']) {
                $msg = trim((string) $this->steps[$i]['output']);
                $msg = $msg === '' ? 'Sem output.' : $msg;

                return $this->steps[$i]['step'].': '.\Illuminate\Support\Str::limit($msg, 3000);
            }
        }

        return null;
    }

    public function logsText(): string
    {
        $out = [];
        foreach ($this->steps as $s) {
            $out[] = '== '.$s['step'].' (exit='.$s['exit_code'].', '.number_format($s['duration'], 1).'s)';
            $body = trim((string) $s['output']);
            if ($body !== '') {
                $out[] = \Illuminate\Support\Str::limit($body, 8000);
            }
        }

        return implode("\n", $out);
    }

    public function composerUpdate(): bool
    {
        $composer = $this->resolveComposerBinary();
        if ($composer === null) {
            return $this->recordSkip('composer update', 'composer não encontrado no PATH');
        }
        $php = $this->resolvePhpBinary();

        // --no-scripts evita correr post-update-cmd (php artisan package:discover) com .env vazio.
        // O package:discover é executado mais tarde por this::artisanPackageDiscover().
        $args = ['update', '--no-interaction', '--prefer-dist', '--no-progress', '--no-scripts'];
        $cmd = $php ? array_merge([$php, $composer], $args) : array_merge([$composer], $args);

        return $this->runStep('composer update', $cmd);
    }

    /** Regenera o package manifest do Laravel — corre depois do .env e composer update. */
    public function artisanPackageDiscover(): bool
    {
        if (! is_file($this->workDir.DIRECTORY_SEPARATOR.'artisan')) {
            return $this->recordSkip('artisan package:discover', 'artisan ausente');
        }

        $php = $this->resolvePhpBinary() ?? 'php';

        return $this->runStep('artisan package:discover', [$php, 'artisan', 'package:discover', '--ansi']);
    }

    public function npmInstall(): bool
    {
        if (! is_file($this->workDir.DIRECTORY_SEPARATOR.'package.json')) {
            return $this->recordSkip('npm install', 'package.json ausente');
        }
        $npm = $this->resolveNpmBinary();
        if ($npm === null) {
            return $this->recordSkip('npm install', 'npm não encontrado no PATH');
        }

        $node = $this->resolveNodeBinary();
        if ($node === null) {
            return $this->recordSkip(
                'npm install',
                'Node.js >= 20 necessário (Tailwind 4). Define AUTOGRADING_NODE_BINARY ou instala em ~/.local/share/autograding/node-v20'
            );
        }

        $this->resetNpmArtifacts();

        return $this->runStep('npm install', [$npm, 'install', '--no-audit', '--no-fund']);
    }

    public function npmBuild(): bool
    {
        if (! is_file($this->workDir.DIRECTORY_SEPARATOR.'package.json')) {
            return $this->recordSkip('npm run build', 'package.json ausente');
        }
        $npm = $this->resolveNpmBinary();
        if ($npm === null) {
            return $this->recordSkip('npm run build', 'npm não encontrado no PATH');
        }

        if ($this->resolveNodeBinary() === null) {
            return $this->recordSkip(
                'npm run build',
                'Node.js >= 20 necessário (Tailwind 4). Define AUTOGRADING_NODE_BINARY ou instala em ~/.local/share/autograding/node-v20'
            );
        }

        if (! $this->runStep('npm run build', [$npm, 'run', 'build', '--if-present'])) {
            // npm pode falhar a instalar optionalDependencies nativos; tenta reinstalar limpo.
            if (! str_contains($this->lastStepOutput(), 'Cannot find native binding')) {
                return false;
            }

            $this->resetNpmArtifacts();

            if (! $this->runStep('npm install (retry)', [$npm, 'install', '--no-audit', '--no-fund'])) {
                return false;
            }

            return $this->runStep('npm run build (retry)', [$npm, 'run', 'build', '--if-present']);
        }

        return true;
    }

    /**
     * Cria/atualiza o .env do projeto carregado para testes.
     * Não invoca artisan (não há vendor ainda).
     */
    public function configureEnvAndDb(string $dbRebuildStrategy = ProcessDbRebuildStrategy::DEFAULT): bool
    {
        $strategy = ProcessDbRebuildStrategy::normalize($dbRebuildStrategy);
        $preserveDatabase = $strategy === ProcessDbRebuildStrategy::NONE;

        $envExample = $this->workDir.DIRECTORY_SEPARATOR.'.env.example';
        $env = $this->workDir.DIRECTORY_SEPARATOR.'.env';
        if (! is_file($env)) {
            if (is_file($envExample)) {
                copy($envExample, $env);
            } else {
                File::put($env, "APP_NAME=\"AutoGrading Sandbox\"\nAPP_ENV=local\nAPP_DEBUG=true\n");
            }
        }

        $contents = (string) @file_get_contents($env);

        $sqliteFile = $this->workDir.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'test.sqlite';
        if (! $preserveDatabase) {
            File::ensureDirectoryExists(dirname($sqliteFile));
            @touch($sqliteFile);
            $this->configureSqlitePragmas($sqliteFile);
        }

        $overrides = [
            'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
            'APP_ENV' => 'local',
            'APP_DEBUG' => 'true',
            'APP_URL' => 'http://localhost',
            'QUEUE_CONNECTION' => 'sync',
            'MAIL_MAILER' => 'log',
            'CACHE_DRIVER' => 'array',
            'SESSION_DRIVER' => 'array',
            'BROADCAST_DRIVER' => 'log',
            'FILESYSTEM_DISK' => 'local',
        ];

        if (! $preserveDatabase) {
            $overrides['DB_CONNECTION'] = 'sqlite';
            $overrides['DB_DATABASE'] = $sqliteFile;
            $overrides['DB_HOST'] = '';
            $overrides['DB_PORT'] = '';
            $overrides['DB_USERNAME'] = '';
            $overrides['DB_PASSWORD'] = '';
        }

        foreach ($overrides as $key => $value) {
            $line = $key.'='.$value;
            if (preg_match('/^'.preg_quote($key, '/').'=.*$/m', $contents)) {
                $contents = preg_replace('/^'.preg_quote($key, '/').'=.*$/m', $line, $contents);
            } else {
                $contents .= (str_ends_with($contents, "\n") ? '' : "\n").$line."\n";
            }
        }

        File::put($env, $contents);

        $detail = $preserveDatabase
            ? 'OK — .env preparado (base de dados do projeto mantida; estratégia: não reconstruir)'
            : 'OK — .env preparado (SQLite efémero em '.$sqliteFile.')';

        $this->steps[] = [
            'step' => 'configure env',
            'exit_code' => 0,
            'output' => $detail,
            'success' => true,
            'duration' => 0.0,
        ];

        return true;
    }

    /**
     * Prepara a BD conforme a estratégia do processo (migrate/seed, cópia SQLite ou nada).
     */
    public function applyDbRebuildStrategy(
        string $strategy,
        ?string $baseSqliteSource = null,
        ?string $submissionSqliteSource = null
    ): bool {
        $strategy = ProcessDbRebuildStrategy::normalize($strategy);

        return match ($strategy) {
            ProcessDbRebuildStrategy::NONE => $this->recordSkip(
                'database rebuild',
                ProcessDbRebuildStrategy::label($strategy)
            ),
            ProcessDbRebuildStrategy::COPY_BASE_SQLITE => $this->copySqliteDatabase(
                $baseSqliteSource,
                'cópia SQLite do projeto base'
            ),
            ProcessDbRebuildStrategy::COPY_SUBMISSION_SQLITE => $this->copySqliteDatabase(
                $submissionSqliteSource,
                'cópia SQLite da submissão'
            ),
            default => $this->migrateFreshAndSeed(),
        };
    }

    private function migrateFreshAndSeed(): bool
    {
        if (! $this->migrateFresh()) {
            return false;
        }

        return $this->dbSeed();
    }

    private function copySqliteDatabase(?string $source, string $label): bool
    {
        $dest = $this->workDir.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'test.sqlite';

        if ($source === null || ! is_file($source) || filesize($source) === 0) {
            $this->steps[] = [
                'step' => 'database rebuild',
                'exit_code' => 1,
                'output' => 'Falhou — '.$label.': ficheiro SQLite de origem não encontrado ou vazio.'
                    .($source !== null ? ' ('.$source.')' : ''),
                'success' => false,
                'duration' => 0.0,
            ];

            return false;
        }

        File::ensureDirectoryExists(dirname($dest));
        if (! @copy($source, $dest)) {
            $this->steps[] = [
                'step' => 'database rebuild',
                'exit_code' => 1,
                'output' => 'Falhou — '.$label.': não foi possível copiar '.$source.' para '.$dest,
                'success' => false,
                'duration' => 0.0,
            ];

            return false;
        }

        $this->configureSqlitePragmas($dest);
        $this->steps[] = [
            'step' => 'database rebuild',
            'exit_code' => 0,
            'output' => 'OK — '.$label.' ('.$source.' → '.$dest.', '
                .number_format(filesize($dest)).' bytes)',
            'success' => true,
            'duration' => 0.0,
        ];

        return true;
    }

    private function configureSqlitePragmas(string $sqliteFile): void
    {
        if (! class_exists(\PDO::class)) {
            return;
        }

        try {
            $pdo = new \PDO('sqlite:'.$sqliteFile);
            $pdo->exec('PRAGMA journal_mode=WAL');
            $pdo->exec('PRAGMA synchronous=NORMAL');
            $pdo->exec('PRAGMA busy_timeout=30000');
        } catch (\Throwable) {
            // não-fatal
        }
    }

    public function migrateFresh(): bool
    {
        $php = $this->resolvePhpBinary() ?? 'php';

        return $this->runStep('artisan migrate:fresh', [$php, 'artisan', 'migrate:fresh', '--force']);
    }

    public function dbSeed(): bool
    {
        $php = $this->resolvePhpBinary() ?? 'php';

        return $this->runStep('artisan db:seed', [$php, 'artisan', 'db:seed', '--force']);
    }

    /**
     * Corre o PHPUnit do projeto carregado. $testPaths podem ser pastas relativas
     * (ex.: "tests/tests1") — quando vazias, deixa o phpunit usar a suite default.
     *
     * @param  list<string>  $testPaths
     */
    public function phpunit(array $testPaths, ?string $junitOutput = null): bool
    {
        $php = $this->resolvePhpBinary() ?? 'php';
        $bin = $this->workDir.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'phpunit';

        if (! is_file($bin)) {
            return $this->recordSkip('phpunit', 'vendor/bin/phpunit ausente (composer update falhou?)');
        }

        $cmd = [$php, $bin];
        if ($junitOutput !== null) {
            $cmd[] = '--log-junit';
            $cmd[] = $junitOutput;
        }
        foreach ($testPaths as $p) {
            $cmd[] = $p;
        }

        return $this->runStep('phpunit', $cmd, allowExit1: true);
    }

    /**
     * @param  list<string>  $cmd
     */
    private function runStep(string $label, array $cmd, bool $allowExit1 = false): bool
    {
        $start = microtime(true);
        $proc = new SymfonyProcess($cmd, $this->workDir, $this->buildEnv(), null, (float) $this->timeoutSeconds);
        try {
            $proc->run();
        } catch (\Throwable $e) {
            $this->steps[] = [
                'step' => $label,
                'exit_code' => -1,
                'output' => $e->getMessage(),
                'success' => false,
                'duration' => microtime(true) - $start,
            ];

            return false;
        }

        $exit = (int) $proc->getExitCode();
        $output = trim($proc->getOutput()."\n".$proc->getErrorOutput());
        $success = $exit === 0 || ($allowExit1 && $exit === 1);

        $this->steps[] = [
            'step' => $label,
            'exit_code' => $exit,
            'output' => $output,
            'success' => $success,
            'duration' => microtime(true) - $start,
        ];

        return $success;
    }

    private function lastStepOutput(): string
    {
        if ($this->steps === []) {
            return '';
        }

        return (string) ($this->steps[array_key_last($this->steps)]['output'] ?? '');
    }

    private function resetNpmArtifacts(): void
    {
        $lock = $this->workDir.DIRECTORY_SEPARATOR.'package-lock.json';
        if (is_file($lock)) {
            @unlink($lock);
        }

        $nodeModules = $this->workDir.DIRECTORY_SEPARATOR.'node_modules';
        if (is_dir($nodeModules)) {
            File::deleteDirectory($nodeModules);
        }
    }

    private function recordSkip(string $label, string $reason): bool
    {
        $this->steps[] = [
            'step' => $label,
            'exit_code' => 0,
            'output' => 'SKIP — '.$reason,
            'success' => true,
            'duration' => 0.0,
        ];

        return true;
    }

    /** @return array<string,string|bool>|null */
    private function buildEnv(): ?array
    {
        $env = [];
        foreach ($_SERVER as $k => $v) {
            if (is_string($v)) {
                $env[$k] = $v;
            }
        }
        if (! isset($env['HOME']) || $env['HOME'] === '') {
            $env['HOME'] = '/tmp';
        }
        if (! isset($env['COMPOSER_HOME'])) {
            $env['COMPOSER_HOME'] = rtrim($env['HOME'], '/').'/.composer';
        }

        // O Symfony Process funde o ambiente do processo pai (getenv). Variáveis DB_*
        // da plataforma têm prioridade sobre o .env do projeto em working/ e fariam
        // migrate:fresh apagar a BD SQLite da submission-platform.
        foreach ([
            'DB_CONNECTION',
            'DB_DATABASE',
            'DB_HOST',
            'DB_PORT',
            'DB_USERNAME',
            'DB_PASSWORD',
            'DATABASE_URL',
        ] as $dbKey) {
            $env[$dbKey] = false;
        }

        $node = $this->resolveNodeBinary();
        if ($node !== null) {
            $binDir = dirname($node);
            $path = $env['PATH'] ?? getenv('PATH') ?: '';
            if (! is_string($path)) {
                $path = '';
            }
            if (! str_starts_with($path, $binDir)) {
                $env['PATH'] = $binDir.($path !== '' ? ':'.$path : '');
            }
        }

        return $env ?: null;
    }

    private function resolvePhpBinary(): ?string
    {
        $env = getenv('AUTOGRADING_PHP_BINARY');
        if (is_string($env) && trim($env) !== '') {
            return trim($env);
        }

        return self::which('php');
    }

    private function resolveComposerBinary(): ?string
    {
        $env = getenv('AUTOGRADING_COMPOSER_BINARY');
        if (is_string($env) && trim($env) !== '') {
            return trim($env);
        }
        $local = $this->workDir.DIRECTORY_SEPARATOR.'composer.phar';
        if (is_file($local)) {
            return $local;
        }

        return self::which('composer');
    }

    private function resolveNpmBinary(): ?string
    {
        $configured = config('autograding.npm_binary');
        if (is_string($configured) && trim($configured) !== '' && is_file(trim($configured))) {
            return trim($configured);
        }

        $env = getenv('AUTOGRADING_NPM_BINARY');
        if (is_string($env) && trim($env) !== '' && is_file(trim($env))) {
            return trim($env);
        }

        $node = $this->resolveNodeBinary();
        if ($node !== null) {
            $npm = dirname($node).DIRECTORY_SEPARATOR.'npm';
            if (is_file($npm)) {
                return $npm;
            }
        }

        return self::which('npm');
    }

    private function resolveNodeBinary(): ?string
    {
        $configured = config('autograding.node_binary');
        if (is_string($configured) && trim($configured) !== '' && is_executable(trim($configured))) {
            return trim($configured);
        }

        $env = getenv('AUTOGRADING_NODE_BINARY');
        if (is_string($env) && trim($env) !== '' && is_executable(trim($env))) {
            return trim($env);
        }

        $home = getenv('HOME') ?: ($_SERVER['HOME'] ?? '');
        if (is_string($home) && $home !== '') {
            $local = rtrim($home, '/').'/.local/share/autograding/node-v20/bin/node';
            if (is_executable($local)) {
                return $local;
            }
        }

        $fallback = self::which('node');
        if ($fallback !== null && self::nodeMajorVersion($fallback) >= 20) {
            return $fallback;
        }

        return null;
    }

    private static function nodeMajorVersion(string $nodeBinary): int
    {
        $out = @shell_exec(escapeshellarg($nodeBinary).' --version 2>/dev/null');
        if (! is_string($out) || ! preg_match('/v?(\d+)/', trim($out), $m)) {
            return 0;
        }

        return (int) $m[1];
    }

    private static function which(string $name): ?string
    {
        $cmd = PHP_OS_FAMILY === 'Windows' ? "where {$name}" : "command -v {$name}";
        $out = @shell_exec($cmd);
        if (! is_string($out)) {
            return null;
        }
        $first = trim(strtok($out, "\n") ?: '');

        return $first !== '' ? $first : null;
    }
}
