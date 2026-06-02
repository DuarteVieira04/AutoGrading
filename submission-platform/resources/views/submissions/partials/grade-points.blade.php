@php
    $canView = $canView ?? true;
    $showMax = $showMax ?? false;
    $isEvaluation = $isEvaluation ?? null;

    $displayPoints = $displayFinalGrade
        ?? $finalGradePoints
        ?? ($result?->final_grade !== null ? (float) $result->final_grade : null);
    $displayMax = isset($displayMaxGrade)
        ? (float) $displayMaxGrade
        : (float) ($maxGradePoints ?? 0);
    $unit = $unit ?? $displayGradeUnit ?? __('pts');
@endphp
@if (! $canView)
    —
@elseif ($isEvaluation === false)
    <span class="text-gray-500">{{ __('Não avaliado') }}</span>
@elseif ($displayPoints !== null)
    {{ number_format((float) $displayPoints, 1) }}@if ($showMax && $displayMax > 0) / {{ rtrim(rtrim(number_format($displayMax, 2, '.', ''), '0'), '.') }}@endif {{ $unit }}
@elseif ($result && $result->report_sent)
    {{ __('A aguardar classificação estruturada') }}
@else
    —
@endif
