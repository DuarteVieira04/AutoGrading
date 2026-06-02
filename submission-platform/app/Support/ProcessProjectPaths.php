<?php

namespace App\Support;

use App\Models\Process;
use App\Models\Submission;
use Illuminate\Support\Facades\File;

/**
 * Caminhos absolutos para os artefactos do projeto carregado pelo docente
 * e das submissões dos alunos.
 *
 * Estrutura no disco (relativo a storage/app/):
 *   processes/{processId}/project.zip      ZIP original carregado pelo docente
 *   processes/{processId}/base/            extracção do ZIP do docente
 *   processes/{processId}/working/         projeto base + ficheiros do aluno (correção)
 *   processes/{processId}/.grading_extract/  extract temporário do ZIP do aluno
 *   processes/{processId}/submissions/{submissionId}/
 *       submission.zip                     ZIP submetido pelo aluno
 *       process-config.json, components.json, report.xml, result.json
 */
final class ProcessProjectPaths
{
    public static function processRoot(int|Process $process): string
    {
        $id = is_int($process) ? $process : $process->id;

        return storage_path('app'.DIRECTORY_SEPARATOR.'processes'.DIRECTORY_SEPARATOR.$id);
    }

    public static function zipPath(Process $process): string
    {
        return self::processRoot($process).DIRECTORY_SEPARATOR.'project.zip';
    }

    public static function basePath(Process $process): string
    {
        return self::processRoot($process).DIRECTORY_SEPARATOR.'base';
    }

    public static function workingPath(Process $process): string
    {
        return self::processRoot($process).DIRECTORY_SEPARATOR.'working';
    }

    /**
     * Extract temporário do ZIP do aluno durante a correção (não fica na pasta da submissão).
     */
    public static function gradingExtractPath(Process $process): string
    {
        return self::processRoot($process).DIRECTORY_SEPARATOR.'.grading_extract';
    }

    /**
     * Pasta dos artefactos persistentes desta submissão (apenas ZIP + relatórios JSON/XML).
     */
    public static function submissionArtifactsDir(Submission $submission): string
    {
        $processId = (int) $submission->evaluation_process_id;
        $subId = (int) $submission->id;

        return storage_path('app'.DIRECTORY_SEPARATOR.'processes'.DIRECTORY_SEPARATOR.$processId
            .DIRECTORY_SEPARATOR.'submissions'.DIRECTORY_SEPARATOR.$subId);
    }

    /**
     * Caminho relativo (estilo Storage) para `submission.zip` desta submissão.
     */
    public static function submissionZipRelative(Submission $submission): string
    {
        return 'processes/'.((int) $submission->evaluation_process_id)
            .'/submissions/'.((int) $submission->id).'/submission.zip';
    }

    /**
     * Garante a árvore de pastas do processo no disco, mesmo sem ZIP do docente.
     */
    public static function ensureProcessStorageLayout(Process $process): void
    {
        File::ensureDirectoryExists(self::processRoot($process));
        File::ensureDirectoryExists(self::basePath($process));
        File::ensureDirectoryExists(self::workingPath($process));
        File::ensureDirectoryExists(self::processRoot($process).DIRECTORY_SEPARATOR.'submissions');
    }

    /**
     * Repõe working/ a partir do projeto base (base/ do processo ou base-project global).
     */
    public static function resetWorkingFromBase(Process $process, string $baseDir): bool
    {
        $working = self::workingPath($process);

        try {
            if (is_dir($working)) {
                File::deleteDirectory($working);
            }
            File::ensureDirectoryExists(self::processRoot($process));

            return File::copyDirectory($baseDir, $working);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Garante a pasta de artefactos de uma submissão.
     */
    public static function ensureSubmissionArtifactsDir(Submission $submission): string
    {
        $dir = self::submissionArtifactsDir($submission);
        File::ensureDirectoryExists($dir);

        return $dir;
    }

    /**
     * Caminho relativo ao disco 'local' (utilizado em Storage::path / Storage::delete).
     */
    public static function relative(string $absolutePath): string
    {
        $root = storage_path('app').DIRECTORY_SEPARATOR;
        if (str_starts_with($absolutePath, $root)) {
            return str_replace(DIRECTORY_SEPARATOR, '/', substr($absolutePath, strlen($root)));
        }

        return $absolutePath;
    }

    /**
     * Caminho absoluto para o base-project global (fallback quando o processo não
     * tem projeto carregado pelo docente). Vive na mesma árvore do submission-platform.
     */
    public static function globalBaseProjectPath(): string
    {
        $root = rtrim((string) config('autograding.project_root'), DIRECTORY_SEPARATOR);

        return $root.DIRECTORY_SEPARATOR.'base-project';
    }

    public static function hasGlobalBaseProject(): bool
    {
        $dir = self::globalBaseProjectPath();

        return is_dir($dir) && is_file($dir.DIRECTORY_SEPARATOR.'composer.json');
    }

    /**
     * Devolve o caminho absoluto a usar como base do projeto para uma submissão.
     * Preferência: pasta carregada pelo docente (Process::project_base_path quando o
     * processo está pronto). Senão, base-project global (se existir).
     */
    public static function resolveBaseDirForProcess(Process $process): ?string
    {
        if ($process->project_status === Process::PROJECT_STATUS_READY
            && $process->project_base_path) {
            $dir = storage_path('app'.DIRECTORY_SEPARATOR.$process->project_base_path);
            if (is_dir($dir)) {
                return $dir;
            }
        }

        return self::hasGlobalBaseProject() ? self::globalBaseProjectPath() : null;
    }

    public static function processHasUsableBaseProject(Process $process): bool
    {
        return self::resolveBaseDirForProcess($process) !== null;
    }
}
