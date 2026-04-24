<div {{ $attributes }}>
    <form method="GET" action="{{ $filterAction }}">
        <div class="flex justify-between space-x-3">
            <div class="grow flex flex-col space-y-2">
                <flux:input name="order_id" label="Order ID" class="grow" value="{{ $orderId }}"/>
                <flux:input name="product_id" label="Product ID" class="grow" value="{{ $productId }}"/>
                <flux:input name="quantity" label="Quantity" class="grow" value="{{ $quantity }}"/>
            </div>
            <div class="grow-0 flex flex-col space-y-3 justify-start">
                <div class="pt-6">
                    <flux:button variant="primary" type="submit">Filter</flux:button>
                </div>
                <div>
                    <flux:button :href="$resetUrl">Cancel</flux:button>
                </div>
            </div>
        </div>
    </form>
</div>
