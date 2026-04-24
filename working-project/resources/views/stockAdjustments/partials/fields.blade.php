@php
    $stockAdjustment = $stockAdjustment ?? new \App\Models\StockAdjustment();
    $mode = $mode ?? 'edit';
    $readonly = $mode == 'show';
@endphp

<div class="flex flex-wrap space-x-8">
    <div class="grow mt-6 space-y-4">
        <flux:input
            name="product_id"
            label="Product ID"
            :value="old('product_id', $stockAdjustment->product_id)"
            :disabled="$readonly"
        />

        <flux:input
            name="registered_by_user_id"
            label="Registered By (User ID)"
            :value="old('registered_by_user_id', $stockAdjustment->registered_by_user_id)"
            :disabled="$readonly"
        />

        <flux:input
            name="quantity_changed"
            label="Quantity Changed"
            type="number"
            :value="old('quantity_changed', $stockAdjustment->quantity_changed)"
            :disabled="$readonly"
        />
    </div>
</div>
