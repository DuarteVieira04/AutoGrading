<x-layouts.main-content :title="'Product #' . $product->id" heading="Product Details" :subheading="'Product #' . $product->id">
    <div class="flex flex-col space-y-6">
        <div class="max-full">
            <section>
                @auth
                    @if (Auth::user()->type === \App\Enums\UserType::BOARD)
                        <div class="flex flex-wrap justify-start items-center gap-4 mb-4">
                            <flux:button variant="primary" href="{{ route('admin.products.edit', ['product' => $product]) }}">
                                Edit</flux:button>

                            <flux:button href="{{ route('admin.products.create') }}">New</flux:button>

                            <form method="POST" action="{{ route('admin.products.destroy', ['product' => $product]) }}"
                                onsubmit="return confirm('Tem a certeza que quer apagar este produto?');">
                                @csrf
                                @method('DELETE')
                                <flux:button variant="danger" type="submit">Delete</flux:button>
                            </form>
                        </div>
                    @endif
                @endauth
                <div class="mt-6 space-y-4">
                    <div style="user-select: none; pointer-events: none;">
                        @include('products.partials.fields', [
                            'product' => $product,
                            'readonly' => true,
                            'disabled' => true,
                        ])
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-layouts.main-content>
