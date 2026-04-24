<x-layouts.main-content :title="'Order #'.$order->id"
                        heading="Order Details"
                        :subheading="'Order #'.$order->id">
    <div class="flex flex-col space-y-6">
        <div class="max-full">
            <section>
                <div class="flex flex-wrap justify-start items-center gap-4 mb-4">
                    <flux:button variant="primary" href="{{ route('orders.edit', ['order' => $order]) }}">Edit</flux:button>
                    <flux:button href="{{ route('orders.create') }}">New</flux:button>
                    <form method="POST" action="{{ route('orders.destroy', ['order' => $order]) }}">
                        @csrf
                        @method('DELETE')
                        <flux:button variant="danger" type="submit">Delete</flux:button>
                    </form>
                </div>
                <div style="user-select: none; pointer-events: none;">
                        @include('orders.partials.fields', ['order' => $order, 'readonly' => true, 'disabled' => true])
                    </div>
            </section>
        </div>
    </div>
</x-layouts.main-content>
