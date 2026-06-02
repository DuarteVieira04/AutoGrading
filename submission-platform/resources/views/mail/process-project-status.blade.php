@php
    $processName = $process->process_name ?: __('Processo de avaliação');
@endphp

<x-mail::message>
# @if ($ok)
{{ __('Projeto preparado com sucesso') }}
@else
{{ __('Projeto com erro') }}
@endif

{{ __('Processo: :p', ['p' => $processName]) }}

@if ($ok)
{{ __('O ZIP carregado foi extraído e o pipeline (composer update, npm install, npm run build, migrate, db:seed e phpunit) terminou com sucesso. O processo está pronto a receber submissões.') }}
@else
{{ __('Ocorreu um erro durante a preparação do projeto carregado. O processo NÃO está pronto a receber submissões.') }}

@if ($error)
**{{ __('Erro') }}:**

{{ $error }}
@endif
@endif

@if ($log)
**{{ __('Relatório de execução') }}:**

```
{{ $log }}
```
@endif

<x-mail::button :url="route('processes.edit', $process)">
{{ __('Abrir processo') }}
</x-mail::button>

{{ __('Obrigado') }},<br>
{{ config('app.name') }}
</x-mail::message>
