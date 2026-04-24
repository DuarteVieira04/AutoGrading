<table class="table-auto border-collapse w-full">
    <thead>
        <tr class="border-b-2 border-b-gray-400 dark:border-b-gray-500 bg-gray-100 dark:bg-gray-800 w-full">
            <th class="px-2 py-2 text-left">ID</th>
            <th class="px-2 py-2 text-left">Card Number</th>
            <th class="px-2 py-2 text-left">Balance</th>
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
        @foreach ($cards as $card)
            <tr class="border-b border-b-gray-400 dark:border-b-gray-500">
                <td class="px-2 py-2 text-left">{{ $card->id }}</td>
                <td class="px-2 py-2 text-left">{{ $card->card_number }}</td>
                <td class="px-2 py-2 text-left">{{ $card->balance }}</td>
                @if($showView)
                    <td class="ps-2 px-0.5">
                        <a href="{{ route('cards.show', ['card' => $card]) }}">
                            <flux:icon.eye class="size-5 hover:text-green-600" />
                        </a>
                    </td>
                @endif
                @if($showEdit)
                    <td class="px-0.5">
                        <a href="{{ route('cards.edit', ['card' => $card]) }}">
                            <flux:icon.pencil-square class="size-5 hover:text-blue-600" />
                        </a>
                    </td>
                @endif
                @if($showDelete)
                    <td class="px-0.5">
                        <form method="POST" action="{{ route('cards.destroy', ['card' => $card]) }}" class="flex items-center">
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
