<x-layouts.main-content title="Cart" heading="Shopping Cart">
    <div class="w-full p-4 sm:p-6 lg:p-8">
        @if (session('success'))
            <div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg dark:bg-gray-800 dark:text-green-400" role="alert">
                {{ session('success') }}
            </div>
        @endif

        @if (!empty($cart))
            <div class="overflow-x-auto bg-gray-800 rounded-lg shadow">
                <table class="min-w-full text-sm text-left text-gray-300">
                    <thead class="text-xs text-gray-400 uppercase bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 w-1/2">Produto</th>
                            <th scope="col" class="px-6 py-3 text-center">Preço</th>
                            <th scope="col" class="px-6 py-3 text-center">Quantidade</th>
                            <th scope="col" class="px-6 py-3 text-center">Subtotal</th>
                            <th scope="col" class="px-6 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cart as $id => $details)
                        <tr class="border-b border-gray-700">
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-4">
                                    <img src="{{ $details['photo'] ? asset('storage/' . $details['photo']) : 'https://via.placeholder.com/100' }}" alt="{{ $details['name'] }}" class="w-16 h-16 object-cover rounded">
                                    <span class="font-medium text-white">{{ $details['name'] }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">€{{ number_format($details['price'], 2) }}</td>
                            <td class="px-6 py-4">
                                <form action="{{ route('cart.update', $id) }}" method="POST" class="flex justify-center">
                                    @csrf
                                    @method('PATCH')
                                    <input type="number" name="quantity" value="{{ $details['quantity'] }}" 
                                           class="w-20 bg-gray-900 border border-gray-600 text-white text-center rounded-md focus:ring-blue-500 focus:border-blue-500" 
                                           min="1" onchange="this.form.submit()">
                                </form>
                            </td>
                            <td class="px-6 py-4 text-center">€{{ number_format($details['price'] * $details['quantity'], 2) }}</td>
                            <td class="px-6 py-4 text-right">
                                <form action="{{ route('cart.remove', $id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 font-semibold">Remover</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col md:flex-row justify-between items-start mt-8 gap-8">
                <div>
                    <a href="{{ route('products.showcase') }}" class="px-6 py-2 rounded bg-zinc-700 text-white hover:bg-zinc-600">Continuar a Comprar</a>
                </div>

                <div class="w-full md:w-1/3 bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex justify-between border-b border-gray-700 pb-4">
                        <span class="text-lg font-semibold text-white">Total do Carrinho</span>
                        <span class="text-lg font-semibold text-white">€{{ number_format($total, 2) }}</span>
                    </div>
                    <p class="text-xs text-gray-400 mt-4">Custos de envio serão calculados na página de confirmação.</p>
                    <div class="mt-6 flex flex-col gap-4">
                        <a href="{{ route('cart.confirm') }}" class="w-full text-center px-6 py-3 rounded bg-blue-600 text-white hover:bg-blue-700 font-semibold">
                            Finalizar Compra
                        </a>
                        <form action="{{ route('cart.destroy') }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full text-center px-6 py-2 rounded bg-red-800 text-white hover:bg-red-700">
                                Limpar Carrinho
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        @else
            <div class="text-center p-8 bg-gray-800 rounded-lg">
                <h2 class="text-2xl font-bold text-gray-300">O seu carrinho está vazio</h2>
                <a href="{{ route('products.showcase') }}" class="mt-4 inline-block px-6 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Ver Produtos</a>
            </div>
        @endif
    </div>
</x-layouts.main-content>