<?php

/**
 * Gera submission-platform/.env a partir das variáveis do Render.
 */
declare(strict_types=1);

$appDir = getenv('LARAVEL_APP_DIR') ?: '/app/submission-platform';
$envFile = rtrim($appDir, '/').'/.env';

[$databaseUrl, $dbExtras] = resolveDatabaseConfig();
$dbConnection = normalizeDbConnection((string) sanitizeEnv(getenv('DB_CONNECTION') ?: ''), $databaseUrl);

$vars = array_merge([
    'APP_NAME' => sanitizeEnv(getenv('APP_NAME') ?: 'AutoGrading'),
    'APP_ENV' => sanitizeEnv(getenv('APP_ENV') ?: 'production'),
    'APP_KEY' => sanitizeEnv(getenv('APP_KEY') ?: ''),
    'APP_DEBUG' => sanitizeEnv(getenv('APP_DEBUG') ?: 'false'),
    'APP_URL' => sanitizeEnv(getenv('APP_URL') ?: 'http://localhost'),
    'LOG_CHANNEL' => getenv('LOG_CHANNEL') ?: 'stderr',
    'DB_CONNECTION' => $dbConnection,
    'DATABASE_URL' => $databaseUrl,
    'QUEUE_CONNECTION' => getenv('QUEUE_CONNECTION') ?: 'database',
    'SESSION_DRIVER' => getenv('SESSION_DRIVER') ?: 'file',
    'CACHE_DRIVER' => getenv('CACHE_DRIVER') ?: 'file',
    'FILESYSTEM_DISK' => getenv('FILESYSTEM_DISK') ?: 'local',
    'AUTOGRADING_PROJECT_ROOT' => getenv('AUTOGRADING_PROJECT_ROOT') ?: '/app',
    'AUTOGRADING_PYTHON' => getenv('AUTOGRADING_PYTHON') ?: 'python3',
    'AUTOGRADING_NODE_BINARY' => getenv('AUTOGRADING_NODE_BINARY') ?: '/usr/bin/node',
    'AUTOGRADING_NPM_BINARY' => getenv('AUTOGRADING_NPM_BINARY') ?: '/usr/bin/npm',
    'AUTOGRADING_ENABLED' => getenv('AUTOGRADING_ENABLED') ?: 'true',
    'AUTOGRADING_RUN_SYNC' => getenv('AUTOGRADING_RUN_SYNC') ?: 'false',
    'AUTOGRADING_TIMEOUT' => getenv('AUTOGRADING_TIMEOUT') ?: '1900',
], $dbExtras);

// Com DATABASE_URL, o Laravel não precisa de DB_HOST/DB_* (evita host partido em duas linhas no painel).
if ($databaseUrl !== '') {
    unset($vars['DB_HOST'], $vars['DB_PORT'], $vars['DB_DATABASE'], $vars['DB_USERNAME'], $vars['DB_PASSWORD']);
}

$lines = [];
foreach ($vars as $key => $value) {
    if ($key === 'APP_KEY' && $value === '') {
        fwrite(STDERR, "ERRO: APP_KEY em falta no Render.\n");
        exit(1);
    }
    if ($value === '') {
        continue;
    }
    $lines[] = $key.'='.escapeEnvValue((string) $value);
}

if ($dbConnection === 'pgsql' && $databaseUrl === '') {
    printDatabaseHelp();
    exit(1);
}

$parsedHost = $databaseUrl !== '' ? parse_url($databaseUrl, PHP_URL_HOST) : false;
if ($databaseUrl !== '' && (! is_string($parsedHost) || $parsedHost === '' || preg_match('/\s/', $parsedHost))) {
    fwrite(STDERR, "ERRO: DATABASE_URL com hostname inválido. No Render, apaga DATABASE_URL e volta a colar a Internal Database URL numa única linha.\n");
    exit(1);
}

file_put_contents($envFile, implode("\n", $lines)."\n");

$hostForLog = parse_url($databaseUrl, PHP_URL_HOST) ?: '(sem host)';
fwrite(STDOUT, "OK: .env gerado (DB_CONNECTION={$dbConnection}, host={$hostForLog})\n");

/**
 * @return array{0: string, 1: array<string, string>}
 */
function resolveDatabaseConfig(): array
{
    foreach ([
        'DATABASE_EXTERNAL_URL',
        'DATABASE_URL',
        'DATABASE_URL_INTERNAL',
        'DATABASE_INTERNAL_URL',
        'DB_URL',
        'POSTGRES_URL',
        'POSTGRESQL_URL',
        'RENDER_DATABASE_URL',
    ] as $key) {
        $url = sanitizeDatabaseUrl((string) (getenv($key) ?: ''));
        if ($url !== '' && looksLikeDatabaseUrl($url)) {
            return [normalizeRenderPostgresUrl($url), []];
        }
    }

    $host = sanitizeHost(firstEnv(['DB_HOST', 'PGHOST', 'POSTGRES_HOST']));
    $port = sanitizeEnv(firstEnv(['DB_PORT', 'PGPORT', 'POSTGRES_PORT']) ?: '5432');
    $database = sanitizeEnv(firstEnv(['DB_DATABASE', 'PGDATABASE', 'POSTGRES_DB', 'POSTGRES_DATABASE']));
    $username = sanitizeEnv(firstEnv(['DB_USERNAME', 'PGUSER', 'POSTGRES_USER']));
    $password = firstEnv(['DB_PASSWORD', 'PGPASSWORD', 'POSTGRES_PASSWORD']);
    $password = str_replace(["\r", "\n", "\t"], '', $password);

    if ($host !== '' && $database !== '' && $username !== '') {
        $url = sprintf(
            'postgresql://%s:%s@%s:%s/%s',
            rawurlencode($username),
            rawurlencode($password),
            $host,
            $port,
            $database
        );

        return [normalizeRenderPostgresUrl(sanitizeDatabaseUrl($url)), []];
    }

    return ['', []];
}

/**
 * Host interno Render (dpg-xxx-a) → FQDN resolvível; evita "Name or service not known".
 */
function normalizeRenderPostgresUrl(string $url): string
{
    $parts = parse_url($url);
    if ($parts === false || empty($parts['host'])) {
        return $url;
    }

    $host = sanitizeHost((string) $parts['host']);
    if (str_contains($host, '.')) {
        return rebuildPostgresUrl($parts, $host);
    }

    if (! preg_match('/^dpg-[a-z0-9]+-a$/i', $host)) {
        return rebuildPostgresUrl($parts, $host);
    }

    $region = sanitizeEnv(
        getenv('RENDER_POSTGRES_REGION')
        ?: getenv('RENDER_REGION')
        ?: 'frankfurt'
    );
    $fqdn = $host.'.'.$region.'-postgres.render.com';
    fwrite(STDERR, "INFO: hostname PostgreSQL expandido para {$fqdn}\n");

    return rebuildPostgresUrl($parts, $fqdn);
}

/**
 * @param  array<string, mixed>  $parts
 */
function rebuildPostgresUrl(array $parts, string $host): string
{
    $user = isset($parts['user']) ? rawurldecode((string) $parts['user']) : '';
    $pass = isset($parts['pass']) ? rawurldecode((string) $parts['pass']) : '';
    $port = isset($parts['port']) ? (int) $parts['port'] : 5432;
    $db = trim((string) ($parts['path'] ?? ''), '/');

    $auth = '';
    if ($user !== '') {
        $auth = rawurlencode($user);
        if ($pass !== '') {
            $auth .= ':'.rawurlencode($pass);
        }
        $auth .= '@';
    }

    $url = "postgresql://{$auth}{$host}:{$port}";
    if ($db !== '') {
        $url .= '/'.$db;
    }

    return $url;
}

function firstEnv(array $keys): string
{
    foreach ($keys as $key) {
        $v = sanitizeEnv((string) (getenv($key) ?: ''));
        if ($v !== '') {
            return $v;
        }
    }

    return '';
}

function sanitizeEnv(string $value): string
{
    return trim(str_replace(["\r", "\n", "\t"], '', $value));
}

function sanitizeDatabaseUrl(string $url): string
{
    return trim(str_replace(["\r", "\n", "\t"], '', $url));
}

function sanitizeHost(string $host): string
{
    return trim(str_replace(["\r", "\n", "\t", ' '], '', $host));
}

function looksLikeDatabaseUrl(string $url): bool
{
    return (bool) preg_match('#^(postgres(ql)?|mysql)://#i', $url);
}

function normalizeDbConnection(string $connection, string $databaseUrl): string
{
    $allowed = ['mysql', 'pgsql', 'sqlite', 'sqlsrv'];
    if (in_array($connection, $allowed, true)) {
        return $connection;
    }

    if ($databaseUrl !== '' && preg_match('#^postgres(ql)?://#i', $databaseUrl)) {
        return 'pgsql';
    }
    if ($databaseUrl !== '' && preg_match('#^mysql://#i', $databaseUrl)) {
        return 'mysql';
    }

    if ($connection !== '' && $connection !== 'autograding-db') {
        fwrite(STDERR, "AVISO: DB_CONNECTION=\"{$connection}\" ignorado (use pgsql).\n");
    }

    return 'pgsql';
}

function printDatabaseHelp(): void
{
    fwrite(STDERR, "\nERRO: ligação PostgreSQL em falta (DATABASE_URL).\n\n");
    fwrite(STDERR, "No Render:\n");
    fwrite(STDERR, "  1. Cria a base PostgreSQL (ex. autograding-db) na mesma região que o web.\n");
    fwrite(STDERR, "  2. No serviço autograding-web → Environment → Add variable.\n");
    fwrite(STDERR, "  3. Escolhe \"Add from database\" → autograding-db → Internal Database URL.\n");
    fwrite(STDERR, "  4. Nome da variável: DATABASE_URL\n");
    fwrite(STDERR, "  5. Guarda e faz Redeploy.\n\n");
    fwrite(STDERR, "Ou aplica o render.yaml (Blueprint) que liga a BD automaticamente.\n\n");
}

function escapeEnvValue(string $value): string
{
    if ($value === '' || preg_match('/[\s#="\\\']/', $value)) {
        return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
    }

    return $value;
}
