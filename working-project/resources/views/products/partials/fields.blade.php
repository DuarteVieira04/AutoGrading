@php
    $product = $product ?? new \App\Models\Product();
    $mode = $mode ?? 'edit';
    $readonly = $mode == 'show';
@endphp

<div class="flex flex-wrap space-x-8">
    <div class="grow mt-6 space-y-4">
        <flux:input
            name="name"
            label="Name"
            :value="old('name', $product->name)"
            :disabled="$readonly"
        />

        <flux:input
            name="category_id"
            label="Category ID"
            :value="old('category_id', $product->category_id)"
            :disabled="$readonly"
        />

        <flux:input
            name="price"
            label="Price"
            type="number"
            step="0.01"
            :value="old('price', $product->price)"
            :disabled="$readonly"
        />

        <flux:input
            name="stock"
            label="Stock"
            type="number"
            :value="old('stock', $product->stock)"
            :disabled="$readonly"
        />

        <flux:input
            name="description"
            label="Description"
            :value="old('stock', $product->description)"
            :disabled="$readonly"
        />

        <flux:input
            name="discount_min_qty"
            label="Discount Min Quantity"
            type="number"
            :value="old('discount_min_qty', $product->discount_min_qty)"
            :disabled="$readonly"
        />

        <flux:input
            name="discount"
            label="Discount Value"
            type="number"
            step="0.01"
            :value="old('discount', $product->discount)"
            :disabled="$readonly"
        />
        
        <flux:input
            name="stock_lower_limit"
            label="Stock Lower Limit"
            type="number"
            :value="old('stock_lower_limit', $product->stock_lower_limit)"
            :disabled="$readonly"
        />

        <flux:input
            name="stock_upper_limit"
            label="Stock Upper Limit"
            type="number"
            :value="old('stock_upper_limit', $product->stock_upper_limit)"
            :disabled="$readonly"
        />

        <flux:input
            name="photo_url"
            label="Photo URL"
            :value="old('photo_url', $product->photo_url)"
            :disabled="$readonly"
        />
    </div>
</div>
