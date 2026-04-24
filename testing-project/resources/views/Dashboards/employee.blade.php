<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Painel de Controlo do Funcionário') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">
                    Olá, {{ Auth::user()->name }}!
                </h3>
                <p class="mt-1 text-sm text-gray-600">
                    Aqui estão as suas tarefas principais.
                </p>
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="text-xl font-semibold text-gray-800">Encomendas Pendentes</h4>
                        <p class="mt-2 text-3xl font-bold text-blue-600">
                            {{ $pendingOrdersCount }}
                        </p>
                        <p class="mt-1 text-sm text-gray-500">
                            Encomendas a aguardar preparação e envio.
                        </p>
                        <div class="mt-4">
                            <a href="{{ route('staff.orders.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                Gerir Encomendas
                            </a>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="text-xl font-semibold text-gray-800">Produtos com Stock Baixo</h4>
                        <p class="mt-2 text-3xl font-bold text-red-600">
                            {{ $lowStockProductsCount }}
                        </p>
                        <p class="mt-1 text-sm text-gray-500">
                            Produtos que atingiram ou estão abaixo do limite mínimo de stock.
                        </p>
                        <div class="mt-4">
                            <a href="{{ route('staff.inventory.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                Ver Inventário
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
