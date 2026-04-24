<div>
    <table class="table-auto border-collapse w-full">
        <thead>
        <tr class="border-b-2 border-b-gray-400 dark:border-b-gray-500 bg-gray-100 dark:bg-gray-800 w-full">
            <th class="px-2 py-2 text-left">Supply Order ID</th>
            <th class="px-2 py-2 text-left">Product ID</th>
            <th class="px-2 py-2 text-left">Registered By User ID</th>
            <th class="px-2 py-2 text-left">Status</th>
            <th class="px-2 py-2 text-left">Quantity</th>
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
        @foreach ($supplyOrders as $supplyOrder)
            <tr class="border-b border-b-gray-400 dark:border-b-gray-500" style="height: 50px;">
                <td class="px-2 py-2 text-left align-middle">{{ $supplyOrder->id }}</td>
                <td class="px-2 py-2 text-left align-middle">{{ $supplyOrder->product_id }}</td>
                <td class="px-2 py-2 text-left align-middle">{{ $supplyOrder->registered_by_user_id }}</td>
                <td class="px-2 py-2 text-left align-middle">{{ $supplyOrder->status }}</td>
                <td class="px-2 py-2 text-left align-middle">{{ $supplyOrder->quantity }}</td>
                @if($showView ?? false)
                    <td class="ps-2 px-0.5">
                        <a href="{{ route('supplyOrders.show', ['supplyOrder' => $supplyOrder]) }}">
                            <flux:icon.eye class="size-5 hover:text-green-600" />
                        </a>
                    </td>
                @endif
                @if($showEdit ?? false)
                    <td class="px-0.5">
                        <a href="{{ route('supplyOrders.edit', ['supplyOrder' => $supplyOrder]) }}">
                            <flux:icon.pencil-square class="size-5 hover:text-blue-600" />
                        </a>
                    </td>
                @endif
                @if($showDelete ?? false)
                    <td class="px-0.5">
                        <form method="POST" action="{{ route('supplyOrders.destroy', ['supplyOrder' => $supplyOrder]) }}" class="flex items-center">
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
