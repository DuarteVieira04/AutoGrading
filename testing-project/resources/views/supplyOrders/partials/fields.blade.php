@php
    $supplyOrder = $supplyOrder ?? new \App\Models\SupplyOrder();
    $mode = $mode ?? 'edit';
    $readonly = $mode == 'show';
@endphp

<div class="flex flex-wrap space-x-8">
    <div class="grow mt-6 space-y-4">
        <flux:input
            name="product_id"
            label="Product ID"
            :value="old('product_id', isset($productId) ? $productId : ($supplyOrder->product_id !== null ? $supplyOrder->product_id : ''))"
            :disabled="$readonly"
        />

        <flux:input
            name="registered_by_user_id"
            label="Registered By (User ID)"
            :value="old('registered_by_user_id', $supplyOrder->registered_by_user_id)"
            :disabled="$readonly"
        />

        <flux:select
            name="status"
            label="Status"
            :value="old('status', $supplyOrder->status)"
            :disabled="$readonly"
        >
            <option value="requested">Requested</option>
            <option value="completed">Completed</option>
        </flux:select>

        <flux:input
            name="quantity"
            label="Quantity"
            type="number"
            :value="old('quantity', $supplyOrder->quantity)"
            :disabled="$readonly"
        />
    </div>
</div>
