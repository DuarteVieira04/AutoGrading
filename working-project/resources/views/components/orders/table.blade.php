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

<div class="overflow-x-auto rounded-lg shadow bg-gray-900 w-full">
    <table class="min-w-full text-sm text-left text-gray-200">
        <thead>
        <tr class="bg-gray-800 border-b border-gray-700">
            <th class="px-3 py-2 font-semibold">
                <a href="{{ sortUrl('id') }}" class="hover:underline flex items-center gap-1">
                    ID {!! sortArrow('id') !!}
                </a>
            </th>
            <th class="px-3 py-2 font-semibold">
                <a href="{{ sortUrl('member_id') }}" class="hover:underline flex items-center gap-1">
                    Member ID {!! sortArrow('member_id') !!}
                </a>
            </th>
            <th class="px-3 py-2 font-semibold">
                <a href="{{ sortUrl('status') }}" class="hover:underline flex items-center gap-1">
                    Status {!! sortArrow('status') !!}
                </a>
            </th>
            <th class="px-3 py-2 font-semibold">
                <a href="{{ sortUrl('date') }}" class="hover:underline flex items-center gap-1">
                    Date {!! sortArrow('date') !!}
                </a>
            </th>
            <th class="px-3 py-2 font-semibold">
                <a href="{{ sortUrl('total_items') }}" class="hover:underline flex items-center gap-1">
                    Total Items {!! sortArrow('total_items') !!}
                </a>
            </th>
            <th class="px-3 py-2 font-semibold">
                <a href="{{ sortUrl('shipping_cost') }}" class="hover:underline flex items-center gap-1">
                    Shipping Cost {!! sortArrow('shipping_cost') !!}
                </a>
            </th>
            <th class="px-3 py-2 font-semibold">
                <a href="{{ sortUrl('total') }}" class="hover:underline flex items-center gap-1">
                    Total {!! sortArrow('total') !!}
                </a>
            </th>
            <th class="px-3 py-2 font-semibold">
                <a href="{{ sortUrl('nif') }}" class="hover:underline flex items-center gap-1">
                    NIF {!! sortArrow('nif') !!}
                </a>
            </th>
            <th class="px-3 py-2 font-semibold">
                <a href="{{ sortUrl('delivery_address') }}" class="hover:underline flex items-center gap-1">
                    Delivery Address {!! sortArrow('delivery_address') !!}
                </a>
            </th>
            <th class="px-3 py-2 font-semibold">PDF Receipt</th>
            <th class="px-3 py-2 font-semibold">Cancel Reason</th>
            @if($showView ?? false)
                <th class="px-2"></th>
            @endif
            @if($showEdit ?? false)
                <th class="px-2"></th>
            @endif
            @if($showDelete ?? false)
                <th class="px-2"></th>
            @endif
        </tr>
        </thead>
        <tbody>
        @forelse ($orders as $order)
            <tr class="border-b border-gray-800 hover:bg-gray-800 transition">
                <td class="px-3 py-2">{{ $order->id }}</td>
                <td class="px-3 py-2">{{ $order->member_id }}</td>
                <td class="px-3 py-2">{{ $order->status }}</td>
                <td class="px-3 py-2">{{ $order->date }}</td>
                <td class="px-3 py-2">{{ $order->total_items }}</td>
                <td class="px-3 py-2">{{ $order->shipping_cost }}</td>
                <td class="px-3 py-2">{{ $order->total }}</td>
                <td class="px-3 py-2">{{ $order->nif }}</td>
                <td class="px-3 py-2">{{ $order->delivery_address }}</td>
                <td class="px-3 py-2">{{ $order->pdf_receipt }}</td>
                <td class="px-3 py-2">{{ $order->cancel_reason }}</td>
                @if($showView ?? false)
                    <td class="px-2">
                        <a href="{{ route('orders.show', ['order' => $order]) }}" title="Ver">
                            <flux:icon.eye class="size-5 hover:text-green-400" />
                        </a>
                    </td>
                @endif
                @if($showEdit ?? false)
                    <td class="px-2">
                        <a href="{{ route('orders.edit', ['order' => $order]) }}" title="Editar">
                            <flux:icon.pencil-square class="size-5 hover:text-blue-400" />
                        </a>
                    </td>
                @endif
                @if($showDelete ?? false)
                    <td class="px-2">
                        <form method="POST" action="{{ route('orders.destroy', ['order' => $order]) }}" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Apagar">
                                <flux:icon.trash class="size-5 hover:text-red-400" />
                            </button>
                        </form>
                    </td>
                @endif
            </tr>
        @empty
            <tr>
                <td colspan="100%" class="px-3 py-4 text-center text-gray-400">Sem encomendas.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
