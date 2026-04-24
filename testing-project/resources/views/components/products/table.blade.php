@php
    $currentSort = request('sort');
    $currentDirection = request('direction', 'asc');
    function sortUrl($column) {
        $direction = (request('sort') === $column && request('direction') === 'asc') ? 'desc' : 'asc';
        return request()->fullUrlWithQuery(['sort' => $column, 'direction' => $direction]);
    }
    function sortArrow($column) {
        if (request('sort') === $column) {
            return request('direction') === 'asc' ? '↑' : '↓';
        }
        return '';
    }
@endphp
<div>
    <table class="table-auto border-collapse w-full">
        <thead>
        <tr class="border-b-2 border-b-gray-400 dark:border-b-gray-500 bg-gray-100 dark:bg-gray-800">
            <th class="px-2 py-2 text-left">
                <a href="{{ sortUrl('id') }}">ID {!! sortArrow('id') !!}</a>
            </th>
            <th class="px-2 py-2 text-left">
                <a href="{{ sortUrl('name') }}">Name {!! sortArrow('name') !!}</a>
            </th>
            <th class="px-2 py-2 text-left">
                <a href="{{ sortUrl('description') }}">Description {!! sortArrow('description') !!}</a>
            </th>
            <th class="px-2 py-2 text-left">
                <a href="{{ sortUrl('price') }}">Price {!! sortArrow('price') !!}</a>
            </th>
            <th class="px-2 py-2 text-left">
                <a href="{{ sortUrl('stock') }}">Stock {!! sortArrow('stock') !!}</a>
            </th>
            <th class="px-2 py-2 text-left">Photo</th>
            <th class="px-2 py-2 text-left">
                <a href="{{ sortUrl('category_id') }}">Category {!! sortArrow('category_id') !!}</a>
            </th>
            <th class="px-2 py-2 text-left">
                <a href="{{ sortUrl('discount_min_qty') }}">Discount Min Quantity {!! sortArrow('discount_min_qty') !!}</a>
            </th>
            <th class="px-2 py-2 text-left">
                <a href="{{ sortUrl('discount') }}">Discount Value {!! sortArrow('discount') !!}</a>
            </th>
            <th class="px-2 py-2 text-left">
                <a href="{{ sortUrl('stock_lower_limit') }}">Stock Minimum {!! sortArrow('stock_lower_limit') !!}</a>
            </th>
            <th class="px-2 py-2 text-left">
                <a href="{{ sortUrl('stock_upper_limit') }}">Stock Maximum {!! sortArrow('stock_upper_limit') !!}</a>
            </th>
            @if($showView ?? false)
                <th class="px-2 py-2 text-left">Add Supply Order</th>
                <th class="px-2 py-2 text-left">View</th>
            @endif
            @if($showEdit ?? false)
                <th class="px-2 py-2 text-left">Edit</th>
            @endif
            @if($showDelete ?? false)
                <th class="px-2 py-2 text-left">Delete</th>
            @endif
        </tr>
        </thead>
        <tbody>
        @foreach ($products as $product)
            <tr class="border-b border-b-gray-400 dark:border-b-gray-500" style="height: 50px;">
                <td class="px-2 py-2 text-left align-middle">{{ $product->id }}</td>
                <td class="px-2 py-2 text-left align-middle">{{ $product->name }}</td>
                <td class="px-2 py-2 text-left align-middle">{!! nl2br(e($product->description)) !!}</td>
                <td class="px-2 py-2 text-left align-middle">{{ $product->price }}</td>
                <td class="px-2 py-2 text-left align-middle">{{ $product->stock }}</td>
                <td class="px-2 py-2 text-left align-middle">{{ $product->photo }}</td>
                <td class="px-2 py-2 text-left align-middle">
                    {{ $product->category?->name ?? 'N/A' }}
                </td>
                <td class="px-2 py-2 text-left align-middle">{{ $product->discount_min_qty }}</td>
                <td class="px-2 py-2 text-left align-middle">{{ $product->discount}}</td>
                <td class="px-2 py-2 text-left align-middle">{{ $product->stock_lower_limit }}</td>
                <td class="px-2 py-2 text-left align-middle">{{ $product->stock_upper_limit }}</td>
                @if($showView ?? false)
                    <td class="px-2 py-2 text-center align-middle">
                        <a href="{{ route('supplyOrders.create', ['product_id' => $product->id, 'registered_by_user_id' => Auth::user()->id]) }}">
                            <flux:icon.plus class="size-5 hover:text-green-600" />
                        </a>
                    </td>
                    <td class="px-2 py-2 text-center align-middle">
                        <a href="{{ route('admin.products.show', ['product' => $product]) }}">
                            <flux:icon.eye class="size-5 hover:text-green-600" />
                        </a>
                    </td>
                @endif
                @if($showEdit ?? false)
                    <td class="px-2 py-2 text-center align-middle">
                        <a href="{{ route('admin.products.edit', ['product' => $product]) }}">
                            <flux:icon.pencil-square class="size-5 hover:text-blue-600" />
                        </a>
                    </td>
                @endif
                @if($showDelete ?? false)
                    <td class="px-2 py-2 text-center align-middle">
                        <form method="POST" action="{{ route('admin.products.destroy', ['product' => $product]) }}" class="flex items-center justify-center">
                            @csrf
                            @method('DELETE')
                            <button type="submit">
                                <flux:icon.trash class="size-5 hover:text-red-600" />
                            </button>
                        </form>
                    </td>
                @endif
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
