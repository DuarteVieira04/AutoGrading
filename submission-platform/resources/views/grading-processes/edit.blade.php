<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Editar processo') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="post" action="{{ route('grading-processes.update', $process) }}" class="space-y-6 bg-white p-6 shadow sm:rounded-lg border border-gray-200">
                @csrf
                @method('PUT')
                @include('grading-processes._form', ['process' => $process])
                <div class="flex gap-3">
                    <x-primary-button>{{ __('Atualizar') }}</x-primary-button>
                    <a href="{{ route('grading-processes.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50">{{ __('Cancelar') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
