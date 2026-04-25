<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('processes.index') }}"
                   class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                    &lt;
                </a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Tipos de processo') }}
                </h2>
            </div>
            <a href="{{ route('process-types.create') }}"
               class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                {{ __('Novo tipo') }}
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

                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-700">{{ __('Nome') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-700">{{ __('Processos') }}</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-700">{{ __('Ações') }}</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200">
                        @forelse ($processTypes as $type)
                            <tr>
                                <td class="px-4 py-3 text-gray-900 font-medium">{{ $type->name }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $type->processes_count }}</td>
                                <td class="px-4 py-3 text-right space-x-2">
                                    @if (!$type->isDefault())
                                        <a href="{{ route('process-types.edit', $type) }}" class="text-indigo-600 hover:text-indigo-800">{{ __('Editar') }}</a>
                                        <form action="{{ route('process-types.destroy', $type) }}" method="post" class="inline" onsubmit="return confirm('{{ __('Remover este tipo de processo?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800">{{ __('Apagar') }}</button>
                                        </form>
                                    @else
                                        <span class="text-gray-400">{{ __('Não pode modificar') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-gray-500">{{ __('Nenhum tipo de processo definido ainda.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>
        </div>
    </div>
</x-app-layout>
