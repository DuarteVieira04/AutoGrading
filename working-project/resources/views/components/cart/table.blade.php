@php
    $settings = \App\Models\SettingsShippingCost::first();
    $shippingCostSetting = $settings?->shipping_cost ?? 0;
    $minValueThreshold = $settings?->min_value_threshold ?? null;
    $maxValueThreshold = $settings?->max_value_threshold ?? null;
@endphp
@if(empty($carts) || count($carts) === 0)
    <div class="p-4 text-center text-gray-500">
        Your cart is empty.
    </div>
@else
    <table class="min-w-full bg-white dark:bg-zinc-800 rounded shadow w-full">
        <thead>
            <tr>
                <th class="px-4 py-2 text-left">Product</th>
                <th class="px-4 py-2 text-left">Quantity</th>
                <th class="px-4 py-2 text-left">Price</th>
                <th class="px-4 py-2 text-left">Subtotal</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody>
            @php
                $total = 0;
            @endphp
            @foreach($carts as $productId => $quantity)
                @php
                    $product = \App\Models\Product::find($productId);
                    $hasDiscount = $product && $product->discount && $product->discount_min_qty && $quantity >= $product->discount_min_qty;
                    $unitPrice = $product ? $product->price : 0;
                    $discountedPrice = $hasDiscount ? $unitPrice * (1 - $product->discount / 100) : $unitPrice;
                    $subtotal = $discountedPrice * $quantity;
                    $total += $subtotal;

                    // Out of stock or will be out of stock if this quantity is ordered
                    $willBeOutOfStock = $product && (
                        $product->stock < $product->stock_lower_limit ||
                        $quantity > ($product->stock - $product->stock_lower_limit)
                    );
                @endphp
                <tr>
                    <td class="border-t px-4 py-2">
                        @if($product)
                            <div class="flex items-center gap-2">
                                <img src="{{ asset('storage/products/' . $product->photo) }}" alt="{{ $product->name }}" class="w-10 h-10 object-cover rounded">
                                <span>{{ $product->name }}</span>
                            </div>
                            @if($willBeOutOfStock)
                                <div class="mt-2 px-2 py-1 bg-red-600 text-white text-xs rounded font-semibold">
                                    Out of stock (can still be ordered, but delivery will be delayed)
                                </div>
                            @endif
                        @else
                            <span class="text-red-500">Product not found</span>
                        @endif
                    </td>
                    <td class="border-t px-4 py-2">{{ $quantity }}</td>
                    <td class="border-t px-4 py-2">
                        @if($product)
                            {{ number_format($discountedPrice, 2) }} €
                            @if($hasDiscount)
                                <span class="ml-2 text-green-500 font-semibold">(Discount applied!)</span>
                            @endif
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="border-t px-4 py-2">
                        {{ $product ? number_format($subtotal, 2) : '0.00' }} €
                    </td>
                    <td class="border-t px-4 py-2 text-center align-middle">
                        <div class="flex flex-row justify-center items-center gap-2">
                            <form action="{{ route('cart.addQuantity', $productId) }}" method="POST" class="flex justify-center items-center">
                                @csrf
                                @method('PUT')
                                <flux:button type="submit" class="mt-[0.7rem]">
                                    Add 1
                                </flux:button>
                            </form>
                            <form action="{{ route('cart.removeQuantity', $productId) }}" method="POST" class="flex justify-center items-center">
                                @csrf
                                @method('PATCH')
                                <flux:button type="submit" class="mt-[0.7rem]">
                                    Remove 1
                                </flux:button>
                            </form>
                            <form action="{{ route('cart.remove', $productId) }}" method="POST" class="flex justify-center items-center">
                                @csrf
                                @method('PUT')
                                <flux:button type="submit" class="mt-[0.7rem]">
                                    Remove all
                                </flux:button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
            @php
                // Calculate shipping cost for the whole cart
                $shippingCost = 0;
                if ($minValueThreshold && $maxValueThreshold && $total >= $minValueThreshold && $total <= $maxValueThreshold) {
                    $shippingCost = $shippingCostSetting;
                }
            @endphp
            <tr>
                <td colspan="4" class="text-right font-bold px-4 py-2">Total:</td>
                <td class="font-bold px-4 py-2">{{ number_format($total, 2) }} €</td>
            </tr>
            <tr>
                <td colspan="4" class="text-right font-bold px-4 py-2">Shipping Cost:</td>
                <td class="font-bold px-4 py-2">{{ number_format($shippingCost, 2) }} €</td>
            </tr>
            <tr>
                <td colspan="4" class="text-right font-bold px-4 py-2">Grand Total:</td>
                <td class="font-bold px-4 py-2">{{ number_format($total + $shippingCost, 2) }} €</td>
            </tr>
        </tbody>
    </table>
@endif
