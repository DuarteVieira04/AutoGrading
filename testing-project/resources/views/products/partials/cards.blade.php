<div
    class="flex flex-col rounded-lg border border-zinc-200 bg-white shadow-md transition-shadow duration-300 hover:shadow-xl dark:border-zinc-700 dark:bg-zinc-800">

    <a href="{{ route('products.show', $product) }}" class="relative w-full" style="padding-top: 75%;">
        <img src="{{ $product->photo ? asset('storage/products/' . $product->photo) : asset('images/default_product.png') }}" alt="{{ $product->name }}"
            class="absolute top-0 left-0 h-full w-full object-cover">
        @if (!is_null($product->discount_min_qty) && !is_null($product->discount))
            <div class="absolute top-2 right-2 rounded-full bg-yellow-400 px-2 py-1 text-xs font-bold text-zinc-900">
                DESCONTO
            </div>
        @endif
    </a>

    <div class="flex flex-1 flex-col p-4">
        <h2
            class="text-lg font-semibold text-zinc-800 hover:text-blue-600 dark:text-white dark:hover:text-blue-400">
            {{ $product->name }}</h2>
        <p class="mb-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $product->category?->name ?? 'Sem Categoria' }}</p>

        <p class="mb-4 text-sm text-zinc-600 dark:text-zinc-300 line-clamp-2">
            {{ $product->description }}
        </p>

        @if ($product->stock <= 0)
            <p class="mb-2 text-xs font-bold text-red-500">INDISPONÍVEL (encomenda na mesma)</p>
        @elseif($product->stock < $product->stock_lower_limit)
            <p class="mb-2 text-xs font-bold text-orange-500">POUCAS UNIDADES</p>
        @endif

        <div class="flex-grow"></div>

        <div class="mt-4">
            <p class="mb-2 text-2xl font-bold text-green-600">
                {{ number_format($product->price, 2) }} €
            </p>

            <form method="POST" action="{{ route('cart.add', ['product' => $product->id]) }}" class="w-full">
                @csrf
                    <div class="mb-4 flex items-center justify-center space-x-2">

                    <button type="button" onclick="changeQty('{{ $product->id }}', -1)"
                        class="rounded bg-red-600 px-2 py-1 text-white hover:bg-red-700">–</button>

                    <input id="qty-{{ $product->id }}" name="quantity" type="number" value="1" min="1"
                        class="w-12 rounded border-zinc-300 bg-zinc-100 p-1 text-center font-semibold text-zinc-800 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                        readonly>

                    <button type="button" onclick="changeQty('{{ $product->id }}', 1)"
                        class="rounded bg-green-600 px-2 py-1 text-white hover:bg-green-700">+</button>
                </div>

                <button type="submit"
                    class="flex w-full items-center justify-center space-x-2 rounded-full bg-blue-600 py-2 px-4 font-bold text-white transition hover:bg-blue-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H7.118l-.421-1.684A1 1 0 005.695 1H3zM6 16a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z" />
                    </svg>
                    <span>Adicionar</span>
                </button>
            </form>
        </div>
    </div>
</div>

@once
    <script>
        function changeQty(id, delta) {
            const input = document.getElementById('qty-' + id);
            let value = parseInt(input.value) || 0;
            value = Math.max(1, value + delta); 
            input.value = value;
        }
    </script>
@endonce
