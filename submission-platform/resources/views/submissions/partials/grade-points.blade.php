@php
    $points = $finalGradePoints ?? ($result?->final_grade !== null ? (float) $result->final_grade : null);
    $max = (int) ($maxGradePoints ?? 0);
    $canView = $canView ?? true;
    $showMax = $showMax ?? false;
    $unit = $unit ?? __('pts');
@endphp
@if (! $canView)
    —
@elseif ($points !== null)
    {{ number_format($points, 1) }}@if ($showMax && $max > 0) / {{ $max }}@endif {{ $unit }}
@elseif ($result && $result->report_sent)
    {{ __('A aguardar classificação estruturada') }}
@else
    —
@endif
