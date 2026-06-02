<?php

/**
 * Emite "export VAR=..." para o shell — sobrescreve DATABASE_URL corrompida do Render.
 */
declare(strict_types=1);

$envFile = getenv('LARAVEL_ENV_FILE') ?: '/app/submission-platform/.env';
if (! is_file($envFile)) {
    fwrite(STDERR, "ERRO: {$envFile} não existe.\n");
    exit(1);
}

$vars = ['DATABASE_URL', 'DB_CONNECTION'];
$parsed = [];

foreach (file($envFile, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) {
        continue;
    }
    if (! str_contains($line, '=')) {
        continue;
    }
    [$key, $value] = explode('=', $line, 2);
    $key = trim($key);
    $value = trim($value);
    if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
        $value = stripcslashes(substr($value, 1, -1));
    }
    $parsed[$key] = $value;
}

foreach ($vars as $key) {
    if (! isset($parsed[$key]) || $parsed[$key] === '') {
        continue;
    }
    echo 'export '.$key.'='.escapeshellarg($parsed[$key])."\n";
}
