<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * Lê `autograding.json` junto a cada pasta de testes em base-project (template de correção).
 *
 * Esquema sugerido:
 * - weight: float (peso relativo do conjunto de testes, ex. 0.0–1.0)
 * - visibility: "student"|"teacher"|"both" — quem vê estes resultados na UI
 * - purpose: "formative"|"summative" — formativa (feedback/verificação) vs sumativa (avaliação final)
 */
final class SuiteAutograding
{
    public static function jsonAbsolutePath(string $pathPattern): string
    {
        $root = rtrim((string) config('autograding.project_root'), DIRECTORY_SEPARATOR);
        $rel = trim(str_replace('\\', '/', $pathPattern), '/');

        return $root.DIRECTORY_SEPARATOR.'base-project'.DIRECTORY_SEPARATOR
            .str_replace('/', DIRECTORY_SEPARATOR, $rel).DIRECTORY_SEPARATOR.'autograding.json';
    }

    /**
     * @return array{weight?: float, visibility?: string, purpose?: string}|null
     */
    public static function read(?string $pathPattern): ?array
    {
        if ($pathPattern === null || trim($pathPattern) === '') {
            return null;
        }

        $path = self::jsonAbsolutePath($pathPattern);
        if (! File::isFile($path)) {
            return null;
        }

        try {
            $data = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }

        if (! is_array($data)) {
            return null;
        }

        return self::normalize($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{weight?: float, visibility?: string, purpose?: string}
     */
    public static function normalize(array $data): array
    {
        $out = [];

        if (array_key_exists('weight', $data) && is_numeric($data['weight'])) {
            $out['weight'] = (float) $data['weight'];
        }

        $vis = $data['visibility'] ?? null;
        if (in_array($vis, ['student', 'teacher', 'both'], true)) {
            $out['visibility'] = $vis;
        }

        $purpose = $data['purpose'] ?? null;
        if (in_array($purpose, ['formative', 'summative'], true)) {
            $out['purpose'] = $purpose;
        }

        return $out;
    }
}
