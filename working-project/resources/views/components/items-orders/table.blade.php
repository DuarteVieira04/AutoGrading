@php
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
                <th class="px-2 py-2 text-left"><a href="{{ sortUrl('id') }}">ID {!! sortArrow('id') !!}</a></th>
                <th class="px-2 py-2 text-left"><a href="{{ sortUrl('order_id') }}">Order ID {!! sortArrow('order_id') !!}</a></th>
                <th class="px-2 py-2 text-left"><a href="{{ sortUrl('product_id') }}">Product ID {!! sortArrow('product_id') !!}</a></th>
                <th class="px-2 py-2 text-left"><a href="{{ sortUrl('quantity') }}">Quantity {!! sortArrow('quantity') !!}</a></th>
                <th class="px-2 py-2 text-left"><a href="{{ sortUrl('subtotal') }}">Subtotal {!! sortArrow('price') !!}</a></th>
                @if($showView ?? false)
                    <th></th>
                @endif
                @if($showEdit ?? false)
                    <th></th>
                @endif
                @if($showDelete ?? false)
                    <th></th>
                @endif
            </tr>
        </thead>
        <tbody>
        @foreach ($itemsOrders as $item)
            <tr class="border-b border-b-gray-400 dark:border-b-gray-500" style="height: 50px;">
                <td class="px-2 py-2 text-left align-middle">{{ $item->id }}</td>
                <td class="px-2 py-2 text-left align-middle">{{ $item->order_id }}</td>
                <td class="px-2 py-2 text-left align-middle">{{ $item->product_id }}</td>
                <td class="px-2 py-2 text-left align-middle">{{ $item->quantity }}</td>
                <td class="px-2 py-2 text-left align-middle">{{ $item->subtotal }}</td>
                @if($showView ?? false)
                    <td class="ps-2 px-0.5">
                        <a href="{{ route('itemsOrders.show', ['itemsOrder' => $item]) }}">
                            <flux:icon.eye class="size-5 hover:text-green-600" />
                        </a>
                    </td>
                @endif
                @if($showEdit ?? false)
                    <td class="px-0.5">
                        <a href="{{ route('itemsOrders.edit', ['itemsOrder' => $item]) }}">
                            <flux:icon.pencil-square class="size-5 hover:text-blue-600" />
                        </a>
                    </td>
                @endif
                @if($showDelete ?? false)
                    <td class="px-0.5">
                        <form method="POST" action="{{ route('itemsOrders.destroy', ['itemsOrder' => $item]) }}" class="flex items-center">
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
