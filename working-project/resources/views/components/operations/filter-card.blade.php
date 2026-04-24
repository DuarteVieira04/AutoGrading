<div {{ $attributes }}>
    <form method="GET" action="{{ $filterAction }}">
        <div class="flex justify-between space-x-3">
            <div class="grow flex flex-col space-y-2">
                <div>
                    <flux:input name="card_id" label="Card ID" class="grow" value="{{ $cardId }}"/>
                    <flux:select name="type" label="Type" class="grow" :value="$type ?? ''">
                        <option value="">All</option>
                        <option value="debit" @if(($type ?? '') === 'debit') selected @endif>Debit</option>
                        <option value="credit" @if(($type ?? '') === 'credit') selected @endif>Credit</option>
                    </flux:select>
                    <flux:input name="order_id" label="Order ID" class="grow" value="{{ $orderId }}"/>
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
