@php
    $user = $user ?? new \App\Models\User();
    $mode = $mode ?? 'edit';
    $readonly = $mode == 'show';
@endphp

<div class="flex flex-wrap space-x-8">
    <div class="grow mt-6 space-y-4">
        <flux:input
            name="name"
            label="Name"
            :value="old('name', $user->name)"
            :disabled="$readonly"
        />

        <flux:input
            name="email"
            label="Email"
            type="email"
            :value="old('email', $user->email)"
            :disabled="$readonly"
        />

        <flux:input
            name="password"
            label="Password"
            type="password"
            :value="old('password')"
            :disabled="$readonly"
        />

        <flux:select
            name="type"
            label="Type"
            :value="old('type', $user->type)"
            :disabled="$readonly"
        >
            <option value="board">Board</option>
            <option value="member">Member</option>
            <option value="employee">Employee</option>
        </flux:select>

        <flux:input
            name="blocked"
            label="Blocked"
            type="checkbox"
            :checked="old('blocked', $user->blocked)"
            :disabled="$readonly"
        />

        <flux:input
            name="gender"
            label="Gender"
            :value="old('gender', $user->gender)"
            :disabled="$readonly"
        />

        <flux:input
            name="photo"
            label="Photo URL"
            :value="old('photo', $user->photo)"
            :disabled="$readonly"
        />

        <flux:input
            name="nif"
            label="NIF"
            :value="old('nif', $user->nif)"
            :disabled="$readonly"
        />

        <flux:input
            name="default_delivery_address"
            label="Default Delivery Address"
            :value="old('default_delivery_address', $user->default_delivery_address)"
            :disabled="$readonly"
        />

        <flux:input
            name="default_payment_type"
            label="Default Payment Type"
            :value="old('default_payment_type', $user->default_payment_type)"
            :disabled="$readonly"
        />

        <flux:input
            name="default_payment_reference"
            label="Default Payment Reference"
            :value="old('default_payment_reference', $user->default_payment_reference)"
            :disabled="$readonly"
        />
    </div>
</div>
