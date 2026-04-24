<x-layouts.main-content :title="'Settings'"
                        heading="Settings"
                        subheading="Application Settings">
    <div class="flex flex-col space-y-6">
        <div class="max-full">
            <section>
                <div class="flex flex-wrap justify-start items-center gap-4 mb-4">
                    <flux:button variant="primary" href="{{ route('settings.edit', ['setting' => $setting]) }}">Edit</flux:button>
                    <flux:button href="{{ route('settings.create') }}">New</flux:button>
                    <form method="POST" action="{{ route('settings.destroy', ['setting' => $setting]) }}">
                        @csrf
                        @method('DELETE')
                        <flux:button variant="danger" type="submit">Delete</flux:button>
                    </form>
                </div>
                <div class="mt-6 space-y-4">
                    <div><strong>Setting ID:</strong> {{ $setting->id }}</div>
                    <div><strong>Membership Fee:</strong> {{ $setting->membership_fee }}</div>
                </div>
            </section>
        </div>
    </div>
</x-layouts.main-content>
