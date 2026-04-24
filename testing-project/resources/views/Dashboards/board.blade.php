<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Painel de Administração') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">Olá, {{ Auth::user()->name }}!</h3>
                <p class="mt-1 text-sm text-gray-600">
                    Tem acesso total à gestão do Grocery Club.
                </p>
                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-gray-100 p-4 rounded-lg">Total de Utilizadores: {{ $stats['users'] }}</div>
                    <div class="bg-gray-100 p-4 rounded-lg">Encomendas Pendentes: {{ $stats['pendingOrders'] }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
