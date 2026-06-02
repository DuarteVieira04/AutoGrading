<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Novo processo de correção') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="post" action="{{ route('processes.store') }}" enctype="multipart/form-data" class="space-y-6 bg-white p-6 shadow sm:rounded-lg border border-gray-200">
                @csrf
                @include('processes._form', ['process' => null])
                <div class="flex gap-3">
                    <x-primary-button>{{ __('Guardar') }}</x-primary-button>
                    <a href="{{ route('processes.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50">{{ __('Cancelar') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
