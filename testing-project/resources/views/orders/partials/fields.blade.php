@php
    $order = $order ?? new \App\Models\Order();
    $mode = $mode ?? 'edit';
    $readonly = $mode == 'show';
@endphp

<div class="flex flex-wrap space-x-8">
    <div class="grow mt-6 space-y-4">
        <flux:input
            name="member_id"
            label="Member (User ID)"
            :value="old('member_id', $order->member_id)"
            :disabled="$readonly"
        />

        <flux:select
            name="status"
            label="Status"
            :value="old('status', $order->status)"
            :disabled="$readonly"
        >
            <option value="completed">Completed</option>
            <option value="canceled">Canceled</option>
            <option value="pending">Pending</option>
        </flux:select>

        <flux:input
            name="date"
            label="Date"
            type="date"
            :value="old('date', $order->date)"
            :disabled="$readonly"
        />

        <flux:input
            name="total_items"
            label="Total Items"
            type="number"
            :value="old('total_items', $order->total_items)"
            :disabled="$readonly"
        />

        <flux:input
            name="shipping_cost"
            label="Shipping Cost"
            type="number"
            step="0.01"
            :value="old('shipping_cost', $order->shipping_cost)"
            :disabled="$readonly"
        />

        <flux:input
            name="total"
            label="Total"
            type="number"
            step="0.01"
            :value="old('total', $order->total)"
            :disabled="$readonly"
        />

        <flux:input
            name="nif"
            label="NIF"
            :value="old('nif', $order->nif)"
            :disabled="$readonly"
        />

        <flux:input
            name="delivery_address"
            label="Delivery Address"
            :value="old('delivery_address', $order->delivery_address)"
            :disabled="$readonly"
        />

        <flux:input
            name="pdf_receipt"
            label="PDF Receipt"
            :value="old('pdf_receipt', $order->pdf_receipt)"
            :disabled="$readonly"
        />

        <flux:input
            name="cancel_reason"
            label="Cancel Reason"
            :value="old('cancel_reason', $order->cancel_reason)"
            :disabled="$readonly"
        />
    </div>
</div>
