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
                <div style="user-select: none; pointer-events: none;">
                        @include('settings.partials.fields', ['setting' => $setting, 'readonly' => true, 'disabled' => true])
                </div>
            </section>
        </div>
    </div>
</x-layouts.main-content>
