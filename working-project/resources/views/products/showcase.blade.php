<?php
use App\Models\Category;
?>

<x-layouts.main-content title="List of products">
    <form method="GET" action="{{ route('products.showcase') }}"
        class="mb-6 flex flex-col sm:flex-row items-start sm:items-center gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search products by name"
            class="border rounded px-3 py-2 w-full sm:w-1/3 bg-gray-800 text-white placeholder-gray-400 border-gray-700 focus:border-blue-500">

        <select name="category"
            class="border rounded px-3 py-2 w-full sm:w-1/4 bg-gray-800 text-white border-gray-700 focus:border-blue-500">
            <option value="" class="bg-gray-800 text-white">All Categories</option>
            @foreach (\App\Models\Category::all() as $category)
                <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}
                    class="bg-gray-800 text-white">
                    {{ $category->name }}
                </option>
            @endforeach
        </select>

        <select name="price_order"
            class="border rounded px-3 py-2 w-full sm:w-1/4 bg-gray-800 text-white border-gray-700 focus:border-blue-500">
            <option value="" class="bg-gray-800 text-white">Order by price</option>
            <option value="asc" {{ request('price_order') == 'asc' ? 'selected' : '' }}
                class="bg-gray-800 text-white">Price Ascending</option>
            <option value="desc" {{ request('price_order') == 'desc' ? 'selected' : '' }}
                class="bg-gray-800 text-white">Price Descending</option>
        </select>

        <label class="flex items-center space-x-2">
            <input type="checkbox" name="only_discount" value="1" {{ request('only_discount') ? 'checked' : '' }}>
            <span class="text-zinc-800 dark:text-zinc-200">Only products with discount</span>
        </label>

        <button type="submit" class="sm:ml-2 px-4 py-2 bg-blue-600 text-white rounded">
            Filter
        </button>
    </form>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
        @each('products.partials.cards', $products, 'product')
    </div>
    <div class="mt-6">
        {{ $products->appends(request()->query())->links() }}
    </div>
</x-layouts.main-content>
