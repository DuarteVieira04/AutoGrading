@php
    $operation = $operation ?? new \App\Models\Operation();
    $mode = $mode ?? 'edit';
    $readonly = $mode == 'show';
@endphp

<div class="flex flex-wrap space-x-8">
    <div class="grow mt-6 space-y-4">
        <flux:input
            name="card_id"
            label="Card ID"
            :value="old('card_id', $operation->card_id)"
            :disabled="$readonly"
        />

        <flux:input
            name="type"
            label="Type"
            :value="old('type', $operation->type)"
            :disabled="$readonly"
        />

        <flux:input
            name="value"
            label="Value"
            type="number"
            step="0.01"
            :value="old('value', $operation->value)"
            :disabled="$readonly"
        />

        <flux:input
            name="date"
            label="Date"
            type="date"
            :value="old('date', $operation->date)"
            :disabled="$readonly"
        />

        <flux:input
            name="debit_type"
            label="Debit Type"
            :value="old('debit_type', $operation->debit_type)"
            :disabled="$readonly"
        />

        <flux:input
            name="credit_type"
            label="Credit Type"
            :value="old('credit_type', $operation->credit_type)"
            :disabled="$readonly"
        />

        <flux:input
            name="payment_type"
            label="Payment Type"
            :value="old('payment_type', $operation->payment_type)"
            :disabled="$readonly"
        />

        <flux:input
            name="payment_reference"
            label="Payment Reference"
            :value="old('payment_reference', $operation->payment_reference)"
            :disabled="$readonly"
        />

        <flux:input
            name="order_id"
            label="Order ID"
            :value="old('order_id', $operation->order_id)"
            :disabled="$readonly"
        />
    </div>
</div>
