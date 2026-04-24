<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Área de Membro') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">Bem-vindo(a), {{ Auth::user()->name }}!</h3>
                <p class="mt-2 text-2xl font-bold">Saldo do Cartão: {{ number_format($balance, 2) }} €</p>
                <div class="mt-4">
                    <x-primary-button onclick="window.location.href='{{ route("#") }}'">
                        Carregar ou Ver Extrato
                    </x-primary-button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
