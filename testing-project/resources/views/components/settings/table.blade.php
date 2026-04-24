<div>
    <table class="table-auto border-collapse w-full">
        <thead>
        <tr class="border-b-2 border-b-gray-400 dark:border-b-gray-500 bg-gray-100 dark:bg-gray-800 w-full">
            <th class="px-2 py-2 text-left">ID</th>
            <th class="px-2 py-2 text-left">Membership Fee</th>
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
        @foreach ($settings as $setting)
            <tr class="border-b border-b-gray-400 dark:border-b-gray-500" style="height: 50px;">
                <td class="px-2 py-2 text-left align-middle">{{ $setting->id }}</td>
                <td class="px-2 py-2 text-left align-middle">{{ $setting->membership_fee }}</td>
                @if($showView ?? false)
                    <td class="ps-2 px-0.5">
                        <a href="{{ route('settings.show', ['setting' => $setting]) }}">
                            <flux:icon.eye class="size-5 hover:text-green-600" />
                        </a>
                    </td>
                @endif
                @if($showEdit ?? false)
                    <td class="px-0.5">
                        <a href="{{ route('settings.edit', ['setting' => $setting]) }}">
                            <flux:icon.pencil-square class="size-5 hover:text-blue-600" />
                        </a>
                    </td>
                @endif
                @if($showDelete ?? false)
                    <td class="px-0.5">
                        <form method="POST" action="{{ route('settings.destroy', ['setting' => $setting]) }}" class="flex items-center">
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
