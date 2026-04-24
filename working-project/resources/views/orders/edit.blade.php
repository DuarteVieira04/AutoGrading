<x-layouts.main-content :title="'Edit Order #'.$order->id"
                        heading="Edit Order"
                        :subheading="'Order #'.$order->id">
    <div class="flex flex-col space-y-6">
        <div class="max-full">
            <section>
                <div class="flex flex-wrap justify-start items-center gap-4 mb-4">
                    <flux:button variant="primary" href="{{ route('orders.create', ['order' => $order]) }}">New</flux:button>
                    <flux:button href="{{ route('orders.show', ['order' => $order]) }}">View</flux:button>
                    <form method="POST" action="{{ route('orders.destroy', ['order' => $order]) }}">
                        @csrf
                        @method('DELETE')
                        <flux:button variant="danger" type="submit">Delete</flux:button>
                    </form>
                </div>

                <form method="POST" action="{{ route('orders.update', ['order' => $order]) }}">
                    @csrf
                    @method('PUT')
                    <div class="mt-6 space-y-4">
                        @include('orders.partials.fields', ['mode' => 'edit'])
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
