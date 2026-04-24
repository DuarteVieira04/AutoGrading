<x-layouts.main-content title="Confirmar Compra" heading="Confirmar Compra">
    <div class="grid grid-cols-1 md:grid-cols-12 gap-8 text-white">

        <div class="md:col-span-7">
            @if (session('error'))
                <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg dark:bg-gray-800 dark:text-red-400" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4 border-b border-gray-700 pb-3">Detalhes da Entrega e Faturação</h2>
                <form action="{{ route('cart.processConfirm') }}" method="POST" class="space-y-6">
                    @csrf
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-300">Nome</label>
                        <input type="text" id="name" value="{{ $user->name }}" disabled
                               class="mt-1 block w-full bg-gray-700 border-gray-600 rounded-md shadow-sm text-gray-400 cursor-not-allowed">
                    </div>
                    <div>
                        <label for="delivery_address" class="block text-sm font-medium text-gray-300">Morada de Entrega</label>
                        <input type="text" id="delivery_address" name="delivery_address" value="{{ old('delivery_address', $user->default_delivery_address) }}" required
                               class="mt-1 block w-full bg-gray-900 border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label for="nif" class="block text-sm font-medium text-gray-300">NIF</label>
                        <input type="text" id="nif" name="nif" value="{{ old('nif', $user->nif) }}"
                               class="mt-1 block w-full bg-gray-900 border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div class="pt-6 border-t border-gray-700">
                        <div class="flex gap-4">
                            <button type="submit"
                                    class="w-full px-6 py-3 rounded text-white font-semibold transition
                                           {{ $hasEnoughFunds ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-500 cursor-not-allowed' }}"
                                    {{ !$hasEnoughFunds ? 'disabled' : '' }}>
                                Confirmar e Pagar
                            </button>
                            <a href="{{ route('cart.show') }}" class="w-full text-center px-6 py-3 rounded bg-zinc-700 text-white hover:bg-zinc-600">Voltar ao Carrinho</a>
                        </div>
                         @if (!$hasEnoughFunds)
                            <p class="text-red-500 text-sm mt-4 text-center">O seu saldo é insuficiente para completar esta compra.</p>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="md:col-span-5">
            <div class="bg-gray-800 rounded-lg shadow p-6 space-y-4">
                <h2 class="text-xl font-semibold border-b border-gray-700 pb-3">Resumo do Pedido</h2>

                <div class="space-y-2">
                    @foreach($cartWithDetails as $id => $details)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-300">{{ $details['quantity'] }} x {{ $details['name'] }}</span>
                            <span class="font-medium">€{{ number_format($details['price'] * $details['quantity'], 2) }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="pt-4 border-t border-gray-700 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Subtotal</span>
                        <span class="text-gray-300">€{{ number_format($subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Envio</span>
                        <span class="text-gray-300">€{{ number_format($shippingPrice, 2) }}</span>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-700">
                     <div class="flex justify-between text-lg font-bold">
                        <span>Total do Pedido</span>
                        <span>€{{ number_format($total, 2) }}</span>
                    </div>
                </div>
                <div class="pt-4 border-t border-gray-700">
                    <p class="text-sm text-gray-400">Pagamento com o seu cartão de membro.</p>
                    <div class="flex justify-between font-medium">
                        <span>Saldo Disponível:</span>
                        <span>€{{ number_format($user->card->balance, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-layouts.main-content>
