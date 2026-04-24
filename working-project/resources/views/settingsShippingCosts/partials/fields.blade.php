@php
    $settingsShippingCost = $settingsShippingCost ?? new \App\Models\SettingsShippingCost();
    $mode = $mode ?? 'edit';
    $readonly = $mode == 'show';
@endphp

<div class="flex flex-wrap space-x-8">
    <div class="grow mt-6 space-y-4">
        <flux:input
            name="min_value_threshold"
            label="Min Value Threshold"
            type="number"
            step="0.01"
            :value="old('min_value_threshold', $settingsShippingCost->min_value_threshold)"
            :disabled="$readonly"
        />

        <flux:input
            name="max_value_threshold"
            label="Max Value Threshold"
            type="number"
            step="0.01"
            :value="old('max_value_threshold', $settingsShippingCost->max_value_threshold)"
            :disabled="$readonly"
        />

        <flux:input
            name="shipping_cost"
            label="Shipping Cost"
            type="number"
            step="0.01"
            :value="old('shipping_cost', $settingsShippingCost->shipping_cost)"
            :disabled="$readonly"
        />
    </div>
</div>
