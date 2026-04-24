@php
    $itemsOrder = $itemsOrder ?? new \App\Models\ItemsOrder();
    $mode = $mode ?? 'edit';
    $readonly = $mode == 'show';
@endphp

<div class="flex flex-wrap space-x-8">
    <div class="grow mt-6 space-y-4">
        <flux:input
            name="order_id"
            label="Order ID"
            :value="old('order_id', $itemsOrder->order_id)"
            :disabled="$readonly"
        />

        <flux:input
            name="product_id"
            label="Product ID"
            :value="old('product_id', $itemsOrder->product_id)"
            :disabled="$readonly"
        />

        <flux:input
            name="quantity"
            label="Quantity"
            type="number"
            :value="old('quantity', $itemsOrder->quantity)"
            :disabled="$readonly"
        />

        <flux:input
            name="unit_price"
            label="Unit Price"
            type="number"
            step="0.01"
            :value="old('unit_price', $itemsOrder->unit_price)"
            :disabled="$readonly"
        />

        <flux:input
            name="discount"
            label="Discount"
            type="number"
            step="0.01"
            :value="old('discount', $itemsOrder->discount)"
            :disabled="$readonly"
        />

        <flux:input
            name="subtotal"
            label="Subtotal"
            type="number"
            step="0.01"
            :value="old('subtotal', $itemsOrder->subtotal)"
            :disabled="$readonly"
        />
    </div>
</div>
