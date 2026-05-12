@extends('layouts.app')

@section('title', __('Detalhes da submissão'))

@section('content')
    <div class="space-y-6">
        <div class="flex items-start justify-between gap-4 border-b border-gray-300 pb-5">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ __('Detalhes da submissão') }}</h1>
                <p class="mt-2 text-sm text-gray-600">{{ __('Relatório completo de correção desta submissão.') }}</p>
            </div>
            <div class="shrink-0">
                @php
                    $backRoute = route('submissions.index');
                    if (auth()->check() && auth()->user()->hasRole('teacher') && $submission->process) {
                        $backRoute = route('processes.submissions', $submission->process);
                    }
                @endphp
                <a href="{{ $backRoute }}" class="inline-flex items-center rounded border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-50">
                    {{ __('Voltar às submissões') }}
                </a>
            </div>
        </div>

        @php
            $result = $submission->submissionResult;
            $payload = $result?->report_sent_payload ?? [];
            $summary = data_get($payload, 'results.summary', []);
            $tests = $result?->testExecutions ?? collect();
            $payloadTests = collect(data_get($payload, 'results.tests', []));

            if ($tests->isEmpty() && $payloadTests->isNotEmpty()) {
                $tests = $payloadTests->map(function ($test) {
                    return (object) [
                        'test_name' => $test['name'] ?? __('Teste sem nome'),
                        'status' => $test['status'] ?? 'unknown',
                        'error_message' => $test['message'] ?? null,
                        'execution_logs' => isset($test['logs'])
                            ? (is_string($test['logs'])
                                ? $test['logs']
                                : json_encode($test['logs'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                            : null,
                    ];
                });
            }

            $hasTestRows = $tests->isNotEmpty();
            $hasSummary = $hasTestRows || ! empty($summary);
            $totalTests = $hasTestRows ? $tests->count() : ($summary['total_tests'] ?? 0);
            $passedTests = $hasTestRows
                ? $tests->where('status', 'passed')->count()
                : ($summary['successful'] ?? ($summary['passed'] ?? 0));
            $failedTests = $hasTestRows
                ? $totalTests - $passedTests
                : ($summary['failed'] ?? null);
            $successRate = $result?->final_grade ?? ($summary['success_rate'] ?? null);
            $passed = $hasSummary && $failedTests === 0 && $submission->status === 'graded';
            $scriptOutput = data_get($payload, 'output') ?? data_get($payload, 'stdout');
            $scriptErrorOutput = data_get($payload, 'error_output') ?? data_get($payload, 'stderr');
            $scriptRawReport = $result?->report_sent;
            $scriptOutputAvailable = ! empty($scriptOutput) || ! empty($scriptErrorOutput) || ! empty($scriptRawReport);
            $logsTests = $tests->filter(fn($test) => ! empty($test->execution_logs));
        @endphp

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-gray-700">{{ __('Submetido por') }}</p>
                    <p class="text-sm text-gray-900">{{ $submission->student->name ?? __('Anónimo') }}</p>
                </div>
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-gray-700">{{ __('Submetido em') }}</p>
                    <p class="text-sm text-gray-900">{{ $submission->created_at->format('Y-m-d H:i:s') }}</p>
                </div>
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-gray-700">{{ __('Ficheiro') }}</p>
                    <p class="text-sm text-gray-900">{{ basename($submission->zip_file_path) }}</p>
                </div>
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-gray-700">{{ __('Processo de correção') }}</p>
                    <p class="text-sm text-gray-900">{{ $submission->process->process_name ?? '—' }}</p>
                </div>
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-gray-700">{{ __('Grupo de testes') }}</p>
                    <p class="text-sm text-gray-900">
                        @if ($submission->processTestGroup)
                            {{ $submission->processTestGroup->name }} ({{ $submission->processTestGroup->path_pattern }})
                        @else
                            —
                        @endif
                    </p>
                </div>
                @php
                    $payloadCfg = $submission->submissionResult?->report_sent_payload;
                    $suiteCfgs = data_get($payloadCfg, 'autograding_process_config.suite_configs', []);
                    $suiteDetail = null;
                    $patShow = $submission->processTestGroup->path_pattern ?? '';
                    foreach (preg_split('/[\s,]+/', trim($patShow), -1, PREG_SPLIT_NO_EMPTY) as $seg) {
                        $seg = trim(str_replace('\\', '/', $seg), '/');
                        if ($seg !== '' && isset($suiteCfgs[$seg]) && is_array($suiteCfgs[$seg])) {
                            $suiteDetail = \App\Support\SuiteAutograding::normalize($suiteCfgs[$seg]);
                            break;
                        }
                    }
                    if ($suiteDetail === null && $patShow !== '') {
                        $suiteDetail = \App\Support\SuiteAutograding::read($patShow);
                    }
                @endphp
                @if ($suiteDetail && array_filter($suiteDetail))
                    @if (isset($suiteDetail['weight']))
                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-700">{{ __('Peso') }}</p>
                            <p class="text-sm text-gray-900">{{ $suiteDetail['weight'] }}</p>
                        </div>
                    @endif
                    @if (! empty($suiteDetail['visibility']))
                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-700">{{ __('Visibilidade') }}</p>
                            <p class="text-sm text-gray-900">
                                @switch($suiteDetail['visibility'])
                                    @case('student') {{ __('Aluno') }} @break
                                    @case('teacher') {{ __('Professor') }} @break
                                    @case('both') {{ __('Ambos') }} @break
                                    @default {{ $suiteDetail['visibility'] }}
                                @endswitch
                            </p>
                        </div>
                    @endif
                    @if (! empty($suiteDetail['purpose']))
                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-700">{{ __('Finalidade') }}</p>
                            <p class="text-sm text-gray-900">
                                @switch($suiteDetail['purpose'])
                                    @case('formative') {{ __('Formativa') }} @break
                                    @case('summative') {{ __('Sumativa') }} @break
                                    @default {{ $suiteDetail['purpose'] }}
                                @endswitch
                            </p>
                        </div>
                    @endif
                @endif
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-gray-700">{{ __('Estado') }}</p>
                    <p class="text-sm text-gray-900">{{ ucfirst($submission->status) }}</p>
                </div>
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-gray-700">{{ __('Classificação') }}</p>
                    <p class="text-sm text-gray-900">
                        @if ($result && $result->final_grade !== null)
                            {{ number_format((float) $result->final_grade, 1) }}%
                        @else
                            —
                        @endif
                    </p>
                </div>
            </div>

            <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-gray-700">{{ __('Resumo da correção') }}</p>

                    @if ($result && ! empty($result->report_sent) && ! $hasSummary)
                        <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-900">
                            <p class="font-semibold">{{ __('Resultado recebido') }}</p>
                            <p class="mt-1">{{ __('Não foram disponibilizados metadados de teste no resultado da correção.') }}</p>
                        </div>
                    @elseif ($hasSummary)
                        <div class="grid gap-2 sm:grid-cols-2">
                            <div class="rounded-lg border border-gray-200 bg-slate-50 p-3 text-sm text-gray-900">
                                <p class="font-semibold text-gray-700">{{ __('Total de testes') }}</p>
                                <p>{{ $totalTests }}</p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-slate-50 p-3 text-sm text-gray-900">
                                <p class="font-semibold text-gray-700">{{ __('Aprovados') }}</p>
                                <p>{{ $passedTests }}</p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-slate-50 p-3 text-sm text-gray-900">
                                <p class="font-semibold text-gray-700">{{ __('Reprovados') }}</p>
                                <p>{{ $failedTests ?? ($totalTests - $passedTests) }}</p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-slate-50 p-3 text-sm text-gray-900">
                                <p class="font-semibold text-gray-700">{{ __('Taxa de sucesso') }}</p>
                                <p>{{ $successRate !== null ? $successRate . '%' : '—' }}</p>
                            </div>
                        </div>
                                @if (! $hasTestRows)
                            <div class="mt-4 rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-900">
                                <p class="font-semibold">{{ __('Apenas resumo do resultado') }}</p>
                                <p class="mt-1">{{ __('Não há detalhes de cada teste disponíveis.') }}</p>
                            </div>
                        @endif
                        <div class="mt-4 flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $passed ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $passed ? __('Passed') : __('Failed') }}
                            </span>
                        </div>
                    @else
                        <p class="text-sm text-gray-600">{{ __('Não há informação estruturada de correção disponível para esta submissão.') }}</p>
                        @if ($result && ! empty($result->report_sent))
                            <p class="mt-3 text-sm text-gray-700 font-semibold">{{ __('Relatório bruto de correção') }}</p>
                            <p class="mt-1 text-sm text-gray-600">{{ \Illuminate\Support\Str::limit($result->report_sent, 300) }}</p>
                        @endif
                    @endif


                </div>
            </div>
        </div>

        @if (auth()->check() && auth()->user()->hasRole('teacher') && ($scriptOutputAvailable || $logsTests->isNotEmpty()))
            <div class="space-y-4">
                @if ($scriptOutputAvailable)
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900">{{ __('Saída do script') }}</h2>
                            <span class="text-sm text-gray-500">{{ __('Output do autograder') }}</span>
                        </div>

                        <div class="mt-4 space-y-4 text-sm text-gray-800">
                            @if (! empty($scriptOutput))
                                <div>
                                    <p class="font-semibold text-gray-700">{{ __('Saída padrão') }}</p>
                                    <pre class="whitespace-pre-wrap break-words rounded bg-slate-50 p-3 text-xs text-gray-800">{{ $scriptOutput }}</pre>
                                </div>
                            @endif

                            @if (! empty($scriptErrorOutput))
                                <div>
                                    <p class="font-semibold text-gray-700">{{ __('Saída de erro') }}</p>
                                    <pre class="whitespace-pre-wrap break-words rounded bg-slate-50 p-3 text-xs text-gray-800">{{ $scriptErrorOutput }}</pre>
                                </div>
                            @endif

                            @if (empty($scriptOutput) && empty($scriptErrorOutput) && ! empty($scriptRawReport))
                                <div>
                                    <p class="font-semibold text-gray-700">{{ __('Relatório bruto de correção') }}</p>
                                    <pre class="whitespace-pre-wrap break-words rounded bg-slate-50 p-3 text-xs text-gray-800">{{ $scriptRawReport }}</pre>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                @if ($logsTests->isNotEmpty())
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900">{{ __('Logs de execução') }}</h2>
                            <span class="text-sm text-gray-500">{{ __('Detalhes adicionais por teste') }}</span>
                        </div>

                        <div class="mt-4 space-y-4 text-sm text-gray-800">
                            @foreach ($logsTests as $logTest)
                                @php
                                    $decodedLogs = null;

                                    try {
                                        $decodedLogs = json_decode($logTest->execution_logs, true, 512, JSON_THROW_ON_ERROR);
                                    } catch (\JsonException $exception) {
                                        $decodedLogs = $logTest->execution_logs;
                                    }
                                @endphp

                                <div class="rounded-lg border border-gray-200 bg-slate-50 p-4">
                                    <div class="flex items-center justify-between gap-4">
                                        <p class="font-semibold text-gray-700">{{ $logTest->test_name ?? __('Teste sem nome') }}</p>
                                        <span class="text-xs text-gray-500">{{ ucfirst($logTest->status ?? __('desconhecido')) }}</span>
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
                @endif
            </div>
        @endif

        @if ($hasSummary && $tests->isNotEmpty())
            <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">{{ __('Resultados dos testes') }}</h2>
                    <span class="text-sm text-gray-500">{{ __('Estado detalhado do teste') }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50 text-gray-700">
                            <tr>
                                <th class="px-4 py-3 font-medium">{{ __('Teste') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('Estado') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('Mensagem') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($tests as $test)
                                <tr>
                                    <td class="px-4 py-3 text-gray-900">{{ $test->test_name ?? __('Teste sem nome') }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ ($test->status ?? '') === 'passed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ ucfirst($test->status ?? __('desconhecido')) }}
                                        </span>
                                    </td>
                                    <td class="max-w-3xl px-4 py-3 align-top text-gray-700">
                                        @if (! empty($test->error_message))
                                            <pre class="m-0 max-h-96 overflow-auto whitespace-pre-wrap break-words rounded border border-slate-200 bg-slate-50 p-3 font-mono text-xs leading-relaxed text-gray-800">{{ $test->error_message }}</pre>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection
