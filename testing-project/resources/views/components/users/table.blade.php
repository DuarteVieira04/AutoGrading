<!-- filepath: c:\laragon\www\Ainet-Projeto\resources\views\components\users\table.blade.php -->
<div>
    <table class="table-auto border-collapse w-full">
        <thead>
        <tr class="border-b-2 border-b-gray-400 dark:border-b-gray-500 bg-gray-100 dark:bg-gray-800">
            <th class="px-2 py-2 text-left">ID</th>
            <th class="px-2 py-2 text-left">Name</th>
            <th class="px-2 py-2 text-left">Email</th>
            <th class="px-2 py-2 text-left">Type</th>
            <th class="px-2 py-2 text-left">Blocked</th>
            <th class="px-2 py-2 text-left">Gender</th>
            <th class="px-2 py-2 text-left">Photo</th>
            <th class="px-2 py-2 text-left">NIF</th>
            <th class="px-2 py-2 text-left">Default Delivery Address</th>
            <th class="px-2 py-2 text-left">Default Payment Type</th>
            <th class="px-2 py-2 text-left">Default Payment Reference</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($users as $user)
            <tr class="border-b border-b-gray-400 dark:border-b-gray-500" style="height: 50px;">
                <td class="px-2 py-2 text-left align-middle">{{ $user->id }}</td>
                <td class="px-2 py-2 text-left align-middle">{{ $user->name }}</td>
                <td class="px-2 py-2 text-left align-middle">{{ $user->email }}</td>
                <td class="px-2 py-2 text-left align-middle">{{ $user->type }}</td>
                <td class="px-2 py-2 text-left align-middle">{{ $user->blocked }}</td>
                <td class="px-2 py-2 text-left align-middle">{{ $user->gender }}</td>
                <td class="px-2 py-2 text-left align-middle">{{ $user->photo }}</td>
                <td class="px-2 py-2 text-left align-middle">{{ $user->nif }}</td>
                <td class="px-2 py-2 text-left align-middle">{!! nl2br(e($user->default_delivery_address)) !!}</td>
                <td class="px-2 py-2 text-left align-middle">{{ $user->default_payment_type }}</td>
                <td class="px-2 py-2 text-left align-middle">{{ $user->default_payment_reference }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
