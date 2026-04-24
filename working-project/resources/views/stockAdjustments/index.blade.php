<x-layouts.main-content title="Users"
                        heading="List of users"
                        subheading="Manage the users of the institution">
    <div class="flex flex-col gap-4 rounded-xl w-full max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-start">
            <div class="my-4 p-4 sm:p-6 w-full">
                <x-users.filter-card
                    :filterAction="route('admin.users.index')"
                    :resetUrl="route('users.index')"
                    :name="old('name', $name ?? '')"
                    :email="old('email', $email ?? '')"
                    :type="old('type', $type ?? '')"
                    :blocked="old('blocked', $blocked ?? '')"
                    :gender="old('gender', $gender ?? '')"
                    :photo="old('photo', $photo ?? '')"
                    :nif="old('nif', $nif ?? '')"
                    class="mb-6"
                />
                <div class="flex items-center gap-4 mb-4 flex-wrap">
                    <flux:button variant="primary" href="{{ route('admin.users.create') }}">Create a new user</flux:button>
                </div>
                <div class="my-4 font-base text-sm text-gray-700 dark:text-gray-300 overflow-x-auto">
                    <x-users.table :users="$users" />
                </div>
                <div class="mt-4">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</x-layouts.main-content>
