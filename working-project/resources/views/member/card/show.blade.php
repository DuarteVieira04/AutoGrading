<!-- resources/views/member/card/show.blade.php -->
<x-layouts.app.sidebar>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Meu Cartão Virtual') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">Detalhes do Cartão</h3>
                <div class="mt-4 space-y-2">
                    <div>
                        <span class="text-sm text-gray-500">Número do Cartão:</span>
                        <p class="font-mono text-lg">{{ $card->card_number }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Saldo Disponível:</span>
                        <p class="text-2xl font-bold text-green-600">{{ number_format($card->balance, 2) }} €</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app.sidebar>
