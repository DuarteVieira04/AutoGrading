<div {{ $attributes }}>
    <form method="GET" action="{{ $filterAction }}">
        <div class="flex justify-between space-x-3">
            <div class="grow flex flex-col space-y-2">
                <div>
                    <flux:input name="name" label="Name" class="grow" value="{{ $name ?? '' }}"/>
                    <flux:input name="email" label="Email" class="grow" value="{{ $email ?? '' }}"/>
                    <flux:input name="type" label="Type" class="grow" value="{{ $type ?? '' }}"/>
                    <flux:select name="blocked" label="Blocked" class="grow" :value="$blocked ?? ''">
                        <option value="">-- Select --</option>
                        <option value="1" @selected(old('blocked', $blocked ?? '') == '1')>Blocked</option>
                        <option value="0" @selected(old('blocked', $blocked ?? '') == '0')>Not Blocked</option>
                    </flux:select>
                    <flux:select name="gender" label="Gender" class="grow" :value="$gender ?? ''">
                        <option value="">-- Select --</option>
                        <option value="M" @selected(old('gender', $gender ?? '') == 'M')>Masculino</option>
                        <option value="F" @selected(old('gender', $gender ?? '') == 'F')>Feminino</option>
                    </flux:select>
                    <flux:input name="nif" label="NIF" class="grow" value="{{ $nif ?? '' }}"/>
                </div>
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
