<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Processos de correção automática') }}
            </h2>
            <a href="{{ route('processes.create') }}"
               class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                {{ __('Novo processo') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800 border border-green-200">
                    {{ session('status') }}
                </div>
            @endif

            <div class="overflow-x-auto bg-white shadow-sm sm:rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">

                    {{-- HEADER --}}
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-700">
                                {{ __('Processo') }}
                            </th>

                            <th class="px-4 py-3 text-left font-medium text-gray-700">
                                {{ __('Tipo') }}
                            </th>

                            <th class="px-4 py-3 text-left font-medium text-gray-700">
                                {{ __('Grupos') }}
                            </th>

                            <th class="px-4 py-3 text-left font-medium text-gray-700">
                                {{ __('Período de Abertura') }}
                            </th>

                            <th class="px-4 py-3 text-left font-medium text-gray-700">
                                {{ __('Período de Fecho') }}
                            </th>

                            <th class="px-4 py-3 text-right font-medium text-gray-700">
                                {{ __('Ações') }}
                            </th>
                        </tr>
                    </thead>

                    {{-- BODY --}}
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($processes as $p)
                            <tr>

                                {{-- PROCESS NAME --}}
                                <td class="px-4 py-3 text-gray-900 font-medium">
                                    {{ $p->process_name ?? '—' }}
                                </td>

                                {{-- PROCESS TYPE --}}
                                <td class="px-4 py-3 text-gray-700">
                                    {{ $p->processType->name ?? '—' }}
                                </td>

                                {{-- GROUPS --}}
                                <td class="px-4 py-3 text-xs text-gray-700">
                                    @if ($p->groups->isNotEmpty())
                                        @foreach ($p->groups as $group)
                                            <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded mr-1 mb-1">
                                                {{ $group->name }}
                                            </span>
                                        @endforeach
                                    @else
                                        <span class="text-gray-400 italic">{{ __('Não tem grupo definido') }}</span>
                                    @endif
                                </td>

                                {{-- OPEN DATE --}}
                                <td class="px-4 py-3 text-xs text-gray-700">
                                    @if ($p->open_date)
                                        {{ $p->open_date->format('d/m/y H:i') }}
                                    @else
                                        —
                                    @endif
                                </td>

                                {{-- CLOSE DATE --}}
                                <td class="px-4 py-3 text-xs text-gray-700">
                                    @if ($p->close_date)
                                        {{ $p->close_date->format('d/m/y H:i') }}
                                    @else
                                        —
                                    @endif
                                </td>

                                {{-- ACTIONS --}}
                                <td class="px-4 py-3 text-right space-x-2">
                                    <a href="{{ route('processes.edit', $p->id) }}"
                                       class="text-indigo-600 hover:text-indigo-800">
                                        {{ __('Editar') }}
                                    </a>

                                    <form action="{{ route('processes.destroy', $p) }}"
                                          method="post"
                                          class="inline"
                                          onsubmit="return confirm('{{ __('Remover este processo?') }}');">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="text-red-600 hover:text-red-800">
                                            {{ __('Apagar') }}
                                        </button>
                                    </form>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                    {{ __('Nenhum processo. Crie um novo processo para começar.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>
        </div>
    </div>
</x-app-layout>