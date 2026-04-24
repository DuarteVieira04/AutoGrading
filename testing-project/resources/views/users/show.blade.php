<x-layouts.main-content :title="'User #'.$user->id"
                        heading="User Details"
                        :subheading="$user->name">
    <div class="flex flex-col space-y-6">
        <div class="max-full">
            <section>
                <div class="flex flex-wrap justify-start items-center gap-4 mb-4">
                    <flux:button variant="primary" href="{{ route('admin.users.edit', ['user' => $user]) }}">Edit</flux:button>
                    <flux:button href="{{ route('admin.users.create') }}">New</flux:button>
                    <form method="POST" action="{{ route('admin.users.destroy', ['user' => $user]) }}">
                        @csrf
                        @method('DELETE')
                        <flux:button variant="danger" type="submit">Delete</flux:button>
                    </form>
                </div>
                <div style="user-select: none; pointer-events: none;">
                        @include('users.partials.fields', ['user' => $user, 'readonly' => true, 'disabled' => true])
                    </div>
            </section>
        </div>
    </div>
</x-layouts.main-content>
