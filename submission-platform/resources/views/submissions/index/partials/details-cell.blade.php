@if ($row->status === 'processing' || $row->status === 'pending')
    <span class="text-gray-500">{{ __('A aguardar correção automática…') }}</span>
@elseif ($hasSummary)
    @include('submissions.partials.evaluation-badge', ['passed' => $passed])
    <div class="mt-1 text-sm text-gray-600">
        {{ __('Testes passados') }}: {{ $passedCount }} / {{ $totalTests }}
    </div>
    @if (! $canViewRowDetails)
        <p class="mt-1 text-xs text-gray-500">{{ __('Detalhes por teste disponíveis apenas nas pastas formativas visíveis para si.') }}</p>
    @endif
@elseif ($result && $result->report_sent)
    <div class="space-y-2">
        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800">{{ __('Resultado pronto') }}</span>
        <p class="mt-1 text-sm text-gray-600">{{ __('Foi produzida saída de correção, mas não há resumo detalhado de testes disponível.') }}</p>
    </div>
@else
    <span class="text-gray-500">—</span>
@endif

<div class="mt-3">
    <a href="{{ route('submissions.show', $row) }}" class="inline-flex items-center rounded border border-gray-300 bg-white px-3 py-1 text-xs font-medium text-gray-900 hover:bg-gray-50">
        {{ __('Ver detalhes') }}
    </a>
</div>
