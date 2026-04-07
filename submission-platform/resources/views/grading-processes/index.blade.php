<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Processos de correção automática') }}
            </h2>
            <a href="{{ route('grading-processes.create') }}"
                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                {{ __('Novo processo') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800 border border-green-200">{{ session('status') }}</div>
            @endif

            <p class="text-sm text-gray-600">
                {{ __('Define quais pastas do projeto base são substituídas pelas do ZIP do estudante antes de correr os testes (main.py). Só um processo pode estar ativo; as novas entregas usam o processo ativo.') }}
            </p>

            <div class="overflow-x-auto bg-white shadow-sm sm:rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-700">{{ __('Nome') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-700">{{ __('Componentes') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-700">{{ __('Ativo') }}</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-700">{{ __('Ações') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($processes as $p)
                            <tr>
                                <td class="px-4 py-3 text-gray-900">
                                    <div class="font-medium">{{ $p->name }}</div>
                                    @if ($p->description)
                                        <div class="text-gray-500 text-xs mt-1">{{ \Illuminate\Support\Str::limit($p->description, 120) }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-700 font-mono text-xs">{{ json_encode($p->components) }}</td>
                                <td class="px-4 py-3">
                                    @if ($p->is_active)
                                        <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">{{ __('Sim') }}</span>
                                    @else
                                        <span class="text-gray-400">{{ __('Não') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right space-x-2">
                                    <a href="{{ route('grading-processes.edit', $p) }}" class="text-indigo-600 hover:text-indigo-800">{{ __('Editar') }}</a>
                                    <form action="{{ route('grading-processes.destroy', $p) }}" method="post" class="inline" onsubmit="return confirm('{{ __('Remover este processo?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800">{{ __('Apagar') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500">{{ __('Nenhum processo. Crie um ou execute: php artisan db:seed --class=GradingProcessSeeder') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
