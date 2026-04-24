<x-layouts.main-content :title="'Item Order #'.$itemsOrder->id"
                        heading="Item Order Details"
                        :subheading="'Item Order #'.$itemsOrder->id">
    <div class="flex flex-col space-y-6">
        <div class="max-full">
            <section>
                <div class="flex flex-wrap justify-start items-center gap-4 mb-4">
                    <flux:button variant="primary" href="{{ route('itemsOrders.edit', ['itemsOrder' => $itemsOrder]) }}">Edit</flux:button>
                    <flux:button href="{{ route('itemsOrders.create') }}">New</flux:button>
                    <form method="POST" action="{{ route('itemsOrders.destroy', ['itemsOrder' => $itemsOrder]) }}">
                        @csrf
                        @method('DELETE')
                        <flux:button variant="danger" type="submit">Delete</flux:button>
                    </form>
                </div>
                <div style="user-select: none; pointer-events: none;">
                        @include('itemsOrders.partials.fields', ['itemsOrder' => $itemsOrder, 'readonly' => true, 'disabled' => true])
                    </div>
            </section>
        </div>
    </div>
</x-layouts.main-content>
