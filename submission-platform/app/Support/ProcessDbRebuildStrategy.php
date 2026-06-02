<?php

namespace App\Support;

/**
 * Estratégia de preparação da base de dados no projeto de trabalho (processes/{id}/working/).
 */
final class ProcessDbRebuildStrategy
{
    public const MIGRATE_FRESH_SEED = 'migrate_fresh_seed';

    public const COPY_BASE_SQLITE = 'copy_base_sqlite';

    public const COPY_SUBMISSION_SQLITE = 'copy_submission_sqlite';

    public const NONE = 'none';

    public const DEFAULT = self::MIGRATE_FRESH_SEED;

    /** @return list<string> */
    public static function values(): array
    {
        return [
            self::MIGRATE_FRESH_SEED,
            self::COPY_BASE_SQLITE,
            self::COPY_SUBMISSION_SQLITE,
            self::NONE,
        ];
    }

    public static function normalize(mixed $value): string
    {
        $value = is_string($value) ? trim($value) : '';

        return in_array($value, self::values(), true) ? $value : self::DEFAULT;
    }

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            self::MIGRATE_FRESH_SEED => 'Migrate fresh e seed do projeto base',
            self::COPY_BASE_SQLITE => 'Cópia da base de dados SQLite do projeto base',
            self::COPY_SUBMISSION_SQLITE => 'Cópia da base de dados SQLite da submissão',
            self::NONE => 'Nada (não reconstruir)',
        ];
    }

    public static function label(string $strategy): string
    {
        return self::labels()[self::normalize($strategy)] ?? self::labels()[self::DEFAULT];
    }

    /**
     * Procura database.sqlite (ou test.sqlite) no projeto base / working do docente.
     */
    public static function resolveSqliteInProject(string $projectDir): ?string
    {
        $projectDir = rtrim($projectDir, DIRECTORY_SEPARATOR);
        $candidates = [
            $projectDir.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'database.sqlite',
            $projectDir.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'test.sqlite',
            $projectDir.DIRECTORY_SEPARATOR.'database.sqlite',
        ];

        foreach ($candidates as $path) {
            if (is_file($path) && filesize($path) > 0) {
                return $path;
            }
        }

        return null;
    }

    /**
     * SQLite incluída no ZIP do aluno (antes de apagar o extract temporário).
     */
    public static function resolveSqliteInStudentRoot(string $studentRoot): ?string
    {
        return self::resolveSqliteInProject($studentRoot);
    }
}
