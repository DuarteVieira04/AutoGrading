@php
    $setting = $setting ?? new \App\Models\Setting();
    $mode = $mode ?? 'edit';
    $readonly = $mode == 'show';
@endphp

<div class="flex flex-wrap space-x-8">
    <div class="grow mt-6 space-y-4">
        <flux:input
            name="membership_fee"
            label="Membership Fee"
            type="number"
            step="0.01"
            :value="old('membership_fee', $setting->membership_fee)"
            :disabled="$readonly"
        />
    </div>
</div>
