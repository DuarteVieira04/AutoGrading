@php
    $processName = $submission->process?->process_name ?? __('Processo');
    $groupName = $submission->processTestGroup?->name ?? '—';
    $studentName = $submission->student?->name ?? __('Aluno');
@endphp

<x-mail::message>
@if ($recipientRole === 'teacher')
# {{ __('Nova correção de submissão') }}

{{ __('O aluno :name submeteu trabalho no processo **:process** (grupo :group).', [
    'name' => $studentName,
    'process' => $processName,
    'group' => $groupName,
]) }}
@else
# {{ __('A sua submissão foi corrigida') }}

{{ __('O processo **:process** (grupo :group) foi avaliado automaticamente.', [
    'process' => $processName,
    'group' => $groupName,
]) }}
@endif

**{{ __('Estado') }}:** {{ ucfirst($submission->status) }}

@if ($submission->status === 'graded')
    @if (! ($isEvaluation ?? true))
**{{ __('Avaliação') }}:** {{ __('Este processo não conta para avaliação (apenas formativo).') }}
    @elseif (($displayFinalGrade ?? null) !== null)
@php
    $maxLabel = ($evaluationMaxGrade ?? null) !== null
        ? rtrim(rtrim(number_format((float) $displayMaxGrade, 2, '.', ''), '0'), '.')
        : null;
@endphp
**{{ __('Classificação') }}:** {{ number_format((float) $displayFinalGrade, 1) }}@if ($maxLabel) / {{ $maxLabel }}@endif {{ $displayGradeUnit ?? __('pontos') }}
    @endif
@endif

@if ($submission->status === 'failed')
{{ __('A correção automática não concluiu com sucesso. Consulte os detalhes na plataforma.') }}
@endif

<x-mail::button :url="$showUrl">
{{ __('Ver resultado') }}
</x-mail::button>

{{ __('Obrigado') }},<br>
{{ config('app.name') }}
</x-mail::message>
