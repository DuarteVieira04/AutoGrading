<?php

/**
 * Nome da ligação Laravel (mysql|pgsql|sqlite|sqlsrv), não o nome do serviço Render (ex. autograding-db).
 */
return function (): string {
    $allowed = ['mysql', 'pgsql', 'sqlite', 'sqlsrv'];
    $connection = (string) (env('DB_CONNECTION') ?: '');
    if (in_array($connection, $allowed, true)) {
        return $connection;
    }

    $url = (string) (env('DATABASE_URL') ?: '');
    if (preg_match('#^postgres(ql)?://#i', $url)) {
        return 'pgsql';
    }
    if (preg_match('#^mysql://#i', $url)) {
        return 'mysql';
    }

    return 'pgsql';
};
