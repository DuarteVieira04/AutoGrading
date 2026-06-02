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
    <div class="space-y-1">
        <p class="text-sm font-semibold text-gray-700">{{ __('Estado') }}</p>
        <p class="text-sm text-gray-900">
            @include('submissions.partials.status-label', ['row' => $submission])
        </p>
    </div>
    <div class="space-y-1">
        <p class="text-sm font-semibold text-gray-700">{{ ($isEvaluation ?? true) ? __('Classificação') : __('Avaliação') }}</p>
        <p class="text-sm text-gray-900">
            @include('submissions.partials.grade-points', [
                'result' => $result,
                'finalGradePoints' => $finalGradePoints,
                'maxGradePoints' => $maxGradePoints,
                'displayFinalGrade' => $displayFinalGrade ?? null,
                'displayMaxGrade' => $displayMaxGrade ?? null,
                'displayGradeUnit' => $displayGradeUnit ?? __('pontos'),
                'isEvaluation' => $isEvaluation ?? null,
                'canView' => $canViewFinalGrade ?? false,
                'showMax' => true,
            ])
        </p>
        @if (($canViewFinalGrade ?? false) && $overallSuccessRate !== null)
            <p class="mt-1 text-xs text-gray-500">
                {{ __('Taxa global de testes') }}: {{ number_format((float) $overallSuccessRate, 1) }}%
            </p>
        @endif
    </div>
</div>
