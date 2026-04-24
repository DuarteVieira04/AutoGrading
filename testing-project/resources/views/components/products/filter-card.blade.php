@php
    $outOfStock = $outOfStock ?? false;
    $belowThreshold = $belowThreshold ?? false;
@endphp

<div {{ $attributes }}>
    <form method="GET" action="{{ $filterAction }}">
        <div class="flex justify-between space-x-3">
            <div class="grow flex flex-col space-y-2">
                <flux:input name="name" label="Name" class="grow" value="{{ $name }}"/>
                <flux:input name="category_id" label="Category ID" class="grow" value="{{ $categoryId }}"/>
                <flux:input name="price" label="Min Price" class="grow" value="{{ $price }}"/>
                <div class="flex items-center mt-2">
                    <input type="checkbox" id="belowThreshold" name="belowThreshold" value="1" {{ $belowThreshold ? 'checked' : '' }} class="mr-2">
                    <label for="belowThreshold" class="text-sm">Show products below minimum threshold</label>
                </div>
                <div class="flex items-center mt-2">
                    <input type="checkbox" id="outOfStock" name="outOfStock" value="1" {{ $outOfStock ? 'checked' : '' }} class="mr-2">
                    <label for="outOfStock" class="text-sm">Show only out of stock</label>
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
