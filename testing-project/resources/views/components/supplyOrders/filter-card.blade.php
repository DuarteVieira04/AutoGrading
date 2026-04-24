<div {{ $attributes }}>
    <form method="GET" action="{{ $filterAction }}">
        <div class="flex justify-between space-x-3">
            <div class="grow flex flex-col space-y-2">
                <div>
                    <flux:input name="product_id" label="ProductId" class="grow" value="{{ $productId }}"/>
                    <flux:input name="quantity" label="Quantity" class="grow" value="{{ $quantity }}"/>
                    <flux:input name="registered_by_user_id" label="UserId" class="grow" value="{{ $userId }}"/>
                    <flux:select name="status" label="Status">
                        <flux:select.option value="" :selected="$status === ''">All</flux:select.option>
                        <flux:select.option value="pending" :selected="$status === 'pending'">Pending</flux:select.option>
                        <flux:select.option value="completed" :selected="$status === 'completed'">Completed</flux:select.option>
                    </flux:select>
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
