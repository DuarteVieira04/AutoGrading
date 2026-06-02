<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Processos de correção automática') }}
            </h2>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('process-types.index') }}"
                   class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                    {{ __('Tipos de processo') }}
                </a>
                <a href="{{ route('processes.create') }}"
                   class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                    {{ __('Novo processo') }}
                </a>
            </div>
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
                                {{ __('Projeto') }}
                            </th>

                            <th class="px-4 py-3 text-left font-medium text-gray-700">
                                {{ __('Turmas') }}
                            </th>

                            <th class="px-4 py-3 text-left font-medium text-gray-700">
                                {{ __('Período de Abertura') }}
                            </th>

                            <th class="px-4 py-3 text-left font-medium text-gray-700">
                                {{ __('Período de Fecho') }}
                            </th>

                            <th class="px-4 py-3 text-left font-medium text-gray-700">
                                {{ __('Submissões') }}
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
                                    @php
                                        $limit = $p->submissionLimit();
                                    @endphp
                                    @if ($limit > 0)
                                        <span class="mt-1 block text-[11px] text-gray-500">{{ __('Limite: :n submissões/aluno', ['n' => $limit]) }}</span>
                                    @else
                                        <span class="mt-1 block text-[11px] text-gray-400">{{ __('Submissões ilimitadas') }}</span>
                                    @endif
                                </td>

                                {{-- PROJECT STATUS --}}
                                <td class="px-4 py-3 text-xs align-top">
                                    @php
                                        $st = $p->project_status;
                                        $hasOwn = $st === \App\Models\Process::PROJECT_STATUS_READY && $p->project_base_path;
                                        $hasGlobal = \App\Support\ProcessProjectPaths::hasGlobalBaseProject();
                                        $info = match ($st) {
                                            \App\Models\Process::PROJECT_STATUS_READY => ['Pronto', 'bg-green-100 text-green-800'],
                                            \App\Models\Process::PROJECT_STATUS_PREPARING => ['A preparar', 'bg-blue-100 text-blue-800'],
                                            \App\Models\Process::PROJECT_STATUS_FAILED => ['Erro', 'bg-red-100 text-red-800'],
                                            \App\Models\Process::PROJECT_STATUS_PENDING => ['Pendente', 'bg-yellow-100 text-yellow-800'],
                                            default => null,
                                        };
                                    @endphp
                                    @if ($hasOwn)
                                        <span class="inline-block rounded px-2 py-1 {{ $info[1] }}">{{ __($info[0]) }}</span>
                                        @if ($p->project_prepared_at)
                                            <span class="mt-1 block text-[11px] text-gray-500">{{ $p->project_prepared_at->format('d/m/y H:i') }}</span>
                                        @endif
                                    @elseif ($info)
                                        <span class="inline-block rounded px-2 py-1 {{ $info[1] }}">{{ __($info[0]) }}</span>
                                        @if ($hasGlobal)
                                            <span class="mt-1 block text-[11px] text-indigo-700">{{ __('Usa base-project partilhado') }}</span>
                                        @endif
                                    @elseif ($hasGlobal)
                                        <span class="inline-block rounded px-2 py-1 bg-indigo-100 text-indigo-800">{{ __('Base global') }}</span>
                                    @else
                                        <span class="text-gray-400 italic">{{ __('Sem projeto') }}</span>
                                    @endif
                                </td>

                                {{-- CLASS GROUPS (turmas) --}}
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

                                {{-- SUBMISSÕES --}}
                                <td class="px-4 py-3 text-left">
                                    <a href="{{ route('processes.submissions', $p) }}"
                                       class="inline-flex items-center rounded border border-gray-300 bg-white px-3 py-1 text-xs font-medium text-gray-900 hover:bg-gray-50">
                                        {{ __('Ver submissões') }}
                                    </a>
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
                                <td colspan="8" class="px-4 py-8 text-center text-gray-500">
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