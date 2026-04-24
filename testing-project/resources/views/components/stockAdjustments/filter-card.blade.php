<!-- filepath: c:\laragon\www\Ainet-Projeto\resources\views\components\stockAdjustments\filter-card.blade.php -->
<div {{ $attributes }}>
    <form method="GET" action="{{ $filterAction }}">
        <div class="flex justify-between space-x-3">
            <div class="grow flex flex-col space-y-2">
                <div>
                    <flux:input name="product_id" label="ProductId" class="grow" value="{{ $productId }}"/>
                    <flux:input name="quantity_changed" label="Quantity Changed" class="grow" value="{{ $quantityChanged }}"/>
                    <flux:input name="registered_by_user_id" label="UserId" class="grow" value="{{ $userId }}"/>
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
