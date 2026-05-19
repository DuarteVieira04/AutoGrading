<div class="lg:col-span-2 w-full space-y-4 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
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
                @foreach ($testsByGroup ?? [] as $groupBlock)
                    <tr class="bg-slate-100">
                        <td colspan="3" class="px-4 py-3">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                <span class="font-semibold text-gray-900">{{ $groupBlock['name'] }}</span>
                                @if (! empty($groupBlock['path_pattern']))
                                    <span class="font-mono text-xs text-gray-500">{{ $groupBlock['path_pattern'] }}</span>
                                @endif
                                <span class="inline-flex items-center rounded-full bg-white px-2.5 py-0.5 text-xs font-medium text-gray-700 ring-1 ring-gray-200">
                                    {{ $groupBlock['passed'] }}/{{ $groupBlock['total'] }} {{ __('aprovados') }}
                                </span>
                                @if (! empty($groupBlock['weight']))
                                    <span class="inline-flex items-center rounded-full bg-white px-2.5 py-0.5 text-xs font-medium text-gray-600 ring-1 ring-gray-200">
                                        {{ __('Peso') }}: {{ (int) $groupBlock['weight'] }}
                                    </span>
                                @endif
                                @if (isset($groupBlock['success_rate_percent']) && $groupBlock['success_rate_percent'] !== null)
                                    <span class="inline-flex items-center rounded-full bg-white px-2.5 py-0.5 text-xs font-medium text-gray-600 ring-1 ring-gray-200">
                                        {{ number_format((float) $groupBlock['success_rate_percent'], 1) }}%
                                    </span>
                                @endif
                                @if (isset($groupBlock['weighted_points']) && $groupBlock['weighted_points'] !== null)
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-800 ring-1 ring-emerald-200">
                                        {{ number_format((float) $groupBlock['weighted_points'], 1) }} {{ __('pts') }}
                                    </span>
                                @endif
                                @if (! empty($groupBlock['purpose']))
                                    <span class="inline-flex items-center rounded-full bg-white px-2.5 py-0.5 text-xs font-medium text-gray-600 ring-1 ring-gray-200">
                                        {{ ($groupBlock['purpose'] ?? '') === 'summative' ? __('Sumativa') : __('Formativa') }}
                                    </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @if (! empty($groupBlock['can_view_details']))
                        @foreach ($groupBlock['tests'] as $test)
                            <tr>
                                <td class="px-4 py-3 pl-8 text-gray-900">{{ $test->test_name ?? __('Teste sem nome') }}</td>
                                <td class="px-4 py-3">
                                    @include('submissions.partials.test-status-badge', ['status' => $test->status ?? 'unknown'])
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
                    @elseif (($groupBlock['total'] ?? 0) > 0)
                        <tr>
                            <td colspan="3" class="px-4 py-3 pl-8 text-sm text-gray-600 italic">
                                @if (($groupBlock['purpose'] ?? '') === 'summative')
                                    {{ __('Avaliação sumativa — apenas a classificação global é apresentada.') }}
                                @else
                                    {{ __('Detalhes não disponíveis para o seu perfil.') }}
                                @endif
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>
