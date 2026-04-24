<x-layouts.main-content :title="'Supply Order #'.$supplyOrder->id"
                        heading="Supply Order Details"
                        :subheading="'Supply Order #'.$supplyOrder->id">
    <div class="flex flex-col space-y-6">
        <div class="max-full">
            <section>
                <div class="flex flex-wrap justify-start items-center gap-4 mb-4">
                    <flux:button variant="primary" href="{{ route('admin.supply-orders.edit', ['supply_order' => $supplyOrder]) }}">Edit</flux:button>
                    <flux:button href="{{ route('admin.supply-orders.create') }}">New</flux:button>
                    <form method="POST" action="{{ route('admin.supply-orders.destroy', ['supply_order' => $supplyOrder]) }}">
                        @csrf
                        @method('DELETE')
                        <flux:button variant="danger" type="submit">Delete</flux:button>
                    </form>
                </div>
                <div style="user-select: none; pointer-events: none;">
                        @include('supplyOrders.partials.fields', ['supplyOrder' => $supplyOrder, 'readonly' => true, 'disabled' => true])
                    </div>
            </section>
        </div>
    </div>
</x-layouts.main-content>
