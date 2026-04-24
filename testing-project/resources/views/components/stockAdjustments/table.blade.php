<div class="w-full overflow-x-auto">
    <table class="min-w-full table-auto border-collapse">
        <thead>
        <tr class="border-b-2 border-b-gray-400 dark:border-b-gray-500 bg-gray-100 dark:bg-gray-800">
            <th class="px-2 py-2 text-left">ID</th>
            <th class="px-2 py-2 text-left">Product ID</th>
            <th class="px-2 py-2 text-left">Registered By User ID</th>
            <th class="px-2 py-2 text-left">Quantity Changed</th>
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
        @foreach ($stockAdjustments as $stockAdjustment)
            <tr class="border-b border-b-gray-400 dark:border-b-gray-500" style="height: 50px;">
                <td class="px-2 py-2 text-left align-middle">{{ $stockAdjustment->id }}</td>
                <td class="px-2 py-2 text-left align-middle">{{ $stockAdjustment->product_id }}</td>
                <td class="px-2 py-2 text-left align-middle">{{ $stockAdjustment->registered_by_user_id }}</td>
                <td class="px-2 py-2 text-left align-middle">{{ $stockAdjustment->quantity_changed }}</td>
                @if($showView ?? false)
                    <td class="ps-2 px-0.5">
                        <a href="{{ route('admin.stock-adjustments.show', ['stock_adjustment' => $stockAdjustment]) }}">
                            <flux:icon.eye class="size-5 hover:text-green-600" />
                        </a>
                    </td>
                @endif
                @if($showEdit ?? false)
                    <td class="px-0.5">
                        <a href="{{ route('admin.stock-adjustments.edit', ['stock_adjustment' => $stockAdjustment]) }}">
                            <flux:icon.pencil-square class="size-5 hover:text-blue-600" />
                        </a>
                    </td>
                @endif
                @if($showDelete ?? false)
                    <td class="px-0.5">
                        <form method="POST" action="{{ route('admin.stock-adjustments.destroy', ['stock_adjustment' => $stockAdjustment]) }}" class="flex items-center">
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
