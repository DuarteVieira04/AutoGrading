<!-- filepath: c:\laragon\www\Ainet-Projeto\resources\views\components\settingsShippingCosts\table.blade.php -->
<div>
    <table class="table-auto border-collapse w-full">
        <thead>
        <tr class="border-b-2 border-b-gray-400 dark:border-b-gray-500 bg-gray-100 dark:bg-gray-800 w-full">
            <th class="px-2 py-2 text-left">ID</th>
            <th class="px-2 py-2 text-left">Min Value Threshold</th>
            <th class="px-2 py-2 text-left">Max Value Threshold</th>
            <th class="px-2 py-2 text-left">Shipping Cost</th>
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
        @foreach ($settingsShippingCosts as $settingsShippingCost)
            <tr class="border-b border-b-gray-400 dark:border-b-gray-500" style="height: 50px;">
                <td class="px-2 py-2 text-left align-middle">{{ $settingsShippingCost->id }}</td>
                <td class="px-2 py-2 text-left align-middle">{{ $settingsShippingCost->min_value_threshold }}</td>
                <td class="px-2 py-2 text-left align-middle">{{ $settingsShippingCost->max_value_threshold }}</td>
                <td class="px-2 py-2 text-left align-middle">{{ $settingsShippingCost->shipping_cost }}</td>
                @if($showView ?? false)
                    <td class="ps-2 px-0.5">
                        <a href="{{ route('settingsShippingCosts.show', ['settingsShippingCost' => $settingsShippingCost]) }}">
                            <flux:icon.eye class="size-5 hover:text-green-600" />
                        </a>
                    </td>
                @endif
                @if($showEdit ?? false)
                    <td class="px-0.5">
                        <a href="{{ route('settingsShippingCosts.edit', ['settingsShippingCost' => $settingsShippingCost]) }}">
                            <flux:icon.pencil-square class="size-5 hover:text-blue-600" />
                        </a>
                    </td>
                @endif
                @if($showDelete ?? false)
                    <td class="px-0.5">
                        <form method="POST" action="{{ route('settingsShippingCosts.destroy', ['settingsShippingCost' => $settingsShippingCost]) }}" class="flex items-center">
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
