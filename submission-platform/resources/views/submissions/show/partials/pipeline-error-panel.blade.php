@php
    $payload = $result?->report_sent_payload;
    $payload = is_array($payload) ? $payload : [];
    $pipelineError = $payload['error'] ?? null;
    $pipelineLog = $payload['pipeline_log'] ?? null;
    $usedGlobalBase = $payload['used_global_base_project'] ?? null;
    $hasFailureInfo = ($submission->status ?? '') === 'failed' && ($pipelineError !== null || $pipelineLog !== null);
@endphp

@if ($hasFailureInfo)
    <div class="lg:col-span-2 w-full rounded-lg border border-red-200 bg-red-50 p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-red-900">{{ __('Detalhes da falha de correção') }}</h2>
            @if ($usedGlobalBase !== null)
                <span class="text-xs {{ $usedGlobalBase ? 'text-indigo-700' : 'text-gray-600' }}">
                    {{ $usedGlobalBase ? __('Base-project partilhado') : __('Projeto do processo') }}
                </span>
            @endif
        </div>

        @if ($pipelineError)
            <p class="mt-3 whitespace-pre-wrap font-mono text-xs text-red-900">{{ \Illuminate\Support\Str::limit($pipelineError, 2000) }}</p>
        @endif

        @if ($pipelineLog)
            <details class="mt-4" @if (! $pipelineError) open @endif>
                <summary class="cursor-pointer text-xs font-semibold text-red-800 hover:text-red-900">{{ __('Ver relatório do pipeline (composer/npm/migrate/seed/phpunit)') }}</summary>
                <pre class="mt-2 max-h-96 overflow-auto rounded bg-white p-3 font-mono text-[11px] leading-snug text-gray-800">{{ \Illuminate\Support\Str::limit($pipelineLog, 12000) }}</pre>
            </details>
        @endif
    </div>
@endif
