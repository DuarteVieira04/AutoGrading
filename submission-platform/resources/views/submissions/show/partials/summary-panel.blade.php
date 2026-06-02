<div class="space-y-4 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
    <p class="text-sm font-semibold text-gray-700">{{ __('Resumo da correção') }}</p>

    @if (! ($canViewFinalGrade ?? false))
        <p class="text-sm text-gray-600">{{ __('Não tem acesso ao resumo da correção.') }}</p>
    @elseif ($result && ! empty($result->report_sent) && ! $hasSummary)
        <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-900">
            <p class="font-semibold">{{ __('Resultado recebido') }}</p>
            <p class="mt-1">{{ __('Não foram disponibilizados metadados de teste no resultado da correção.') }}</p>
        </div>
    @elseif ($hasSummary)
        <div class="space-y-2">
            <div class="grid grid-cols-2 gap-2">
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
                    <p>{{ $overallSuccessRate !== null ? number_format((float) $overallSuccessRate, 1) . '%' : '—' }}</p>
                </div>
            </div>
            <div class="w-full rounded-lg border border-gray-200 bg-slate-50 p-4 text-sm text-gray-900">
                <p class="font-semibold text-gray-700">
                    {{ ($isEvaluation ?? true) ? __('Nota final') : __('Avaliação') }}
                </p>
                <p class="mt-1 text-xl font-semibold text-gray-900">
                    @include('submissions.partials.grade-points', [
                        'result' => $result,
                        'finalGradePoints' => $finalGradePoints,
                        'maxGradePoints' => $maxGradePoints,
                        'displayFinalGrade' => $displayFinalGrade ?? null,
                        'displayMaxGrade' => $displayMaxGrade ?? null,
                        'displayGradeUnit' => $displayGradeUnit ?? __('pontos'),
                        'isEvaluation' => $isEvaluation ?? null,
                        'canView' => true,
                        'showMax' => true,
                    ])
                </p>
                @if (! ($isEvaluation ?? true))
                    <p class="mt-1 text-xs text-gray-500">{{ __('Este processo não conta para avaliação; os testes servem apenas como feedback formativo.') }}</p>
                @endif
            </div>
        </div>
        @if (! $hasTestRows)
            <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-900">
                <p class="font-semibold">{{ __('Apenas resumo do resultado') }}</p>
                <p class="mt-1">{{ __('Não há detalhes de cada teste disponíveis.') }}</p>
            </div>
        @endif
        @if ($isEvaluation ?? true)
            <div class="flex flex-wrap items-center gap-2">
                @include('submissions.partials.evaluation-badge', ['passed' => $passed])
            </div>
        @endif
    @else
        <p class="text-sm text-gray-600">{{ __('Não há informação estruturada de correção disponível para esta submissão.') }}</p>
    @endif
</div>
