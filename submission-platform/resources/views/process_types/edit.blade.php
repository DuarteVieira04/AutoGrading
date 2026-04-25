<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Editar tipo de processo') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="post" action="{{ route('process-types.update', $processType) }}" class="space-y-6 bg-white p-6 shadow sm:rounded-lg border border-gray-200">
                @csrf
                @method('PUT')
                @include('process_types._form', ['processType' => $processType])
                <div class="flex gap-3">
                    <x-primary-button>{{ __('Atualizar') }}</x-primary-button>
                    <a href="{{ route('process-types.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50">{{ __('Cancelar') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
