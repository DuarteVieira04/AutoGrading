<!-- filepath: c:\laragon\www\Ainet-Projeto\resources\views\components\operations\table.blade.php -->
<table class="table-auto border-collapse w-full">
    <thead>
        <tr class="border-b-2 border-b-gray-400 dark:border-b-gray-500 bg-gray-100 dark:bg-gray-800">
            <th class="px-2 py-2 text-left">ID</th>
            <th class="px-2 py-2 text-left">Card ID</th>
            <th class="px-2 py-2 text-left">Type</th>
            <th class="px-2 py-2 text-left">Value</th>
            <th class="px-2 py-2 text-left">Date</th>
            <th class="px-2 py-2 text-left">Debit Type</th>
            <th class="px-2 py-2 text-left">Credit Type</th>
            <th class="px-2 py-2 text-left">Payment Type</th>
            <th class="px-2 py-2 text-left">Payment Reference</th>
            <th class="px-2 py-2 text-left">Order ID</th>
            @if($showView)
                <th></th>
            @endif
            @if($showEdit)
                <th></th>
            @endif
            @if($showDelete)
                <th></th>
            @endif
        </tr>
    </thead>
    <tbody>
        @foreach ($operations as $operation)
            <tr class="border-b border-b-gray-400 dark:border-b-gray-500">
                <td class="px-2 py-2 text-left">{{ $operation->id }}</td>
                <td class="px-2 py-2 text-left">{{ $operation->card_id }}</td>
                <td class="px-2 py-2 text-left">{{ $operation->type }}</td>
                <td class="px-2 py-2 text-left">{{ $operation->value }}</td>
                <td class="px-2 py-2 text-left">{{ $operation->date }}</td>
                <td class="px-2 py-2 text-left">{{ $operation->debit_type }}</td>
                <td class="px-2 py-2 text-left">{{ $operation->credit_type }}</td>
                <td class="px-2 py-2 text-left">{{ $operation->payment_type }}</td>
                <td class="px-2 py-2 text-left">{{ $operation->payment_reference }}</td>
                <td class="px-2 py-2 text-left">{{ $operation->order_id }}</td>
                @if($showView ?? false)
                    <td class="ps-2 px-0.5">
                        <a href="{{ route('operations.show', ['operation' => $operation]) }}">
                            <flux:icon.eye class="size-5 hover:text-green-600" />
                        </a>
                    </td>
                @endif
                @if($showEdit ?? false)
                    <td class="px-0.5">
                        <a href="{{ route('operations.edit', ['operation' => $operation]) }}">
                            <flux:icon.pencil-square class="size-5 hover:text-blue-600" />
                        </a>
                    </td>
                @endif
                @if($showDelete ?? false)
                    <td class="px-0.5">
                        <form method="POST" action="{{ route('operations.destroy', ['operation' => $operation]) }}" class="flex items-center">
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
