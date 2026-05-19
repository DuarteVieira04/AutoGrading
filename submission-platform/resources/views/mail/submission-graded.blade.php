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

@if ($submission->status === 'graded' && $result?->final_grade !== null)
**{{ __('Classificação') }}:** {{ number_format((float) $result->final_grade, 1) }} {{ __('pontos') }}
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
