<x-layouts.main-content title="Item Orders"
                        heading="List of Item Orders"
                        subheading="Manage the item orders of the institution">
    <div class="flex flex-col gap-4 rounded-xl w-full max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-start">
            <div class="my-4 p-6 w-full">
                <x-items-orders.filter-card
                    :filterAction="route('itemsOrders.index')"
                    :resetUrl="route('itemsOrders.index')"
                    :orderId="old('order_id', $orderId)"
                    :productId="old('product_id', $productId)"
                    :quantity="old('quantity', $quantity)"
                    :subtotal="old('subtotal', $subtotal)"
                    class="mb-6"
                />
                <div class="flex items-center gap-4 mb-4 flex-wrap">
                    <flux:button variant="primary" href="{{ route('itemsOrders.create') }}">Create a new Item Order</flux:button>
                </div>
                <div class="my-4 font-base text-sm text-gray-700 dark:text-gray-300 overflow-x-auto">
                    <x-items-orders.table :itemsOrders="$itemsOrders"
                                         :showView="true"
                                         :showEdit="true"
                                         :showDelete="true"
                    />
                </div>
                <div class="mt-4">
                    {{ $itemsOrders->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
</x-layouts.main-content>
