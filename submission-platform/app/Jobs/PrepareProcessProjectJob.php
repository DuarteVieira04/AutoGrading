<?php

namespace App\Jobs;

use App\Models\Process;
use App\Services\ProcessProjectPreparer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PrepareProcessProjectJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1900;

    public int $tries = 1;

    public function __construct(public int $processId)
    {
    }

    public function handle(ProcessProjectPreparer $preparer): void
    {
        $process = Process::find($this->processId);
        if (! $process) {
            return;
        }
        $preparer->prepare($process);
    }

    public function failed(\Throwable $e): void
    {
        $process = Process::find($this->processId);
        if (! $process) {
            return;
        }
        $process->forceFill([
            'project_status' => Process::PROJECT_STATUS_FAILED,
            'project_error' => 'Job de preparação abortou: '.$e->getMessage(),
        ])->save();
    }
}
