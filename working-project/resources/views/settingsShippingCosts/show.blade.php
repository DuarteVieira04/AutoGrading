<x-layouts.main-content :title="'Settings Shipping Costs'"
                        heading="Settings Shipping Costs"
                        subheading="Application Settings">
    <div class="flex flex-col space-y-6">
        <div class="max-full">
            <section>
                <div class="flex flex-wrap justify-start items-center gap-4 mb-4">
                    <flux:button variant="primary" href="{{ route('settingsShippingCosts.edit', ['settingsShippingCost' => $settingsShippingCost]) }}">Edit</flux:button>
                    <flux:button href="{{ route('settingsShippingCosts.create') }}">New</flux:button>
                    <form method="POST" action="{{ route('settingsShippingCosts.destroy', ['settingsShippingCost' => $settingsShippingCost]) }}">
                        @csrf
                        @method('DELETE')
                        <flux:button variant="danger" type="submit">Delete</flux:button>
                    </form>
                </div>
                <div style="user-select: none; pointer-events: none;">
                        @include('settingsShippingCosts.partials.fields', ['settingsShippingCost' => $settingsShippingCost, 'readonly' => true, 'disabled' => true])
                </div>
            </section>
        </div>
    </div>
</x-layouts.main-content>
