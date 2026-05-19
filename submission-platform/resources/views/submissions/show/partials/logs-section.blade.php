<div class="lg:col-span-2 w-full rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900">{{ __('Logs de execução') }}</h2>
        <span class="text-sm text-gray-500">{{ __('Detalhes adicionais por teste') }}</span>
    </div>
    <div class="mt-4 space-y-4 text-sm text-gray-800">
        @foreach ($logsTests as $logTest)
            @php
                try {
                    $decodedLogs = json_decode($logTest->execution_logs, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $exception) {
                    $decodedLogs = $logTest->execution_logs;
                }
            @endphp
            <div class="rounded-lg border border-gray-200 bg-slate-50 p-4">
                <div class="flex items-center justify-between gap-4">
                    <p class="font-semibold text-gray-700">{{ $logTest->test_name ?? __('Teste sem nome') }}</p>
                    <span class="text-xs text-gray-500">{{ \App\Support\SubmissionRowPresenter::testStatusLabel((string) ($logTest->status ?? 'unknown')) }}</span>
                </div>
                <div class="mt-3 space-y-2 text-xs text-gray-700">
                    @if (is_array($decodedLogs))
                        @foreach ($decodedLogs as $logEntry)
                            <pre class="whitespace-pre-wrap break-words rounded bg-white p-3 text-xs text-gray-800">{{ is_string($logEntry) ? $logEntry : json_encode($logEntry, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        @endforeach
                    @else
                        <pre class="whitespace-pre-wrap break-words rounded bg-white p-3 text-xs text-gray-800">{{ $decodedLogs }}</pre>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
