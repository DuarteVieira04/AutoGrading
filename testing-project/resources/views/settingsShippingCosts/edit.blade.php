<x-layouts.main-content :title="'Edit Shipping Cost Setting #'.$settingsShippingCost->id"
                        heading="Edit Shipping Cost Setting"
                        :subheading="'Shipping Cost Setting #'.$settingsShippingCost->id">
    <div class="flex flex-col space-y-6">
        <div class="max-full">
            <section>
                <div class="static sm:absolute -top-2 right-0 flex flex-wrap justify-start sm:justify-end items-center gap-4">
                    <flux:button variant="primary" href="{{ route('settingsShippingCosts.create') }}">New</flux:button>
                    <flux:button href="{{ route('settingsShippingCosts.show', ['settingsShippingCost' => $settingsShippingCost]) }}">View</flux:button>
                    <form method="POST" action="{{ route('settingsShippingCosts.destroy', ['settingsShippingCost' => $settingsShippingCost]) }}">
                        @csrf
                        @method('DELETE')
                        <flux:button variant="danger" type="submit">Delete</flux:button>
                    </form>
                </div>
                <form method="POST" action="{{ route('settingsShippingCosts.update', ['settingsShippingCost' => $settingsShippingCost]) }}">
                    @csrf
                    @method('PUT')
                    <div class="mt-6 space-y-4">
                        @include('settingsShippingCosts.partials.fields', ['mode' => 'edit'])
                    </div>
                    <div class="flex mt-6">
                        <flux:button variant="primary" type="submit" class="uppercase">Save</flux:button>
                        <flux:button class="uppercase ms-4" href="{{ url()->full() }}">Cancel</flux:button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-layouts.main-content>
