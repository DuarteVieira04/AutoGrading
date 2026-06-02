<?php

/**
 * Gera submission-platform/.env a partir das variáveis do Render (evita mysql/forge por defeito).
 */
declare(strict_types=1);

$appDir = getenv('LARAVEL_APP_DIR') ?: '/app/submission-platform';
$envFile = rtrim($appDir, '/').'/.env';

$databaseUrl = (string) (getenv('DATABASE_URL') ?: '');
$dbConnection = (string) (getenv('DB_CONNECTION') ?: '');

// Render PostgreSQL: URL manda; evita DB_CONNECTION=mysql por engano no painel.
if ($databaseUrl !== '' && preg_match('#^postgres(ql)?://#i', $databaseUrl)) {
    $dbConnection = 'pgsql';
} elseif ($dbConnection === '' && $databaseUrl !== '') {
    $dbConnection = preg_match('#^mysql://#i', $databaseUrl) ? 'mysql' : 'pgsql';
} elseif ($dbConnection === '') {
    $dbConnection = 'pgsql';
}

$vars = [
    'APP_NAME' => getenv('APP_NAME') ?: 'AutoGrading',
    'APP_ENV' => getenv('APP_ENV') ?: 'production',
    'APP_KEY' => getenv('APP_KEY') ?: '',
    'APP_DEBUG' => getenv('APP_DEBUG') ?: 'false',
    'APP_URL' => getenv('APP_URL') ?: 'http://localhost',
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
];

$lines = [];
foreach ($vars as $key => $value) {
    if ($key === 'APP_KEY' && $value === '') {
        fwrite(STDERR, "ERRO: APP_KEY em falta no Render.\n");
        exit(1);
    }
    if ($key === 'DATABASE_URL' && $value === '' && $dbConnection === 'pgsql') {
        fwrite(STDERR, "ERRO: DATABASE_URL em falta. Liga a BD PostgreSQL ao serviço no Render.\n");
        exit(1);
    }
    $lines[] = $key.'='.escapeEnvValue((string) $value);
}

file_put_contents($envFile, implode("\n", $lines)."\n");
fwrite(STDOUT, "OK: .env gerado (DB_CONNECTION={$dbConnection})\n");

function escapeEnvValue(string $value): string
{
    if ($value === '' || preg_match('/[\s#="\\\']/', $value)) {
        return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
    }

    return $value;
}
