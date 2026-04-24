<x-layouts.main-content title="Supply Orders"
                        heading="List of stock supply orders"
                        subheading="Manage the supply orders">
    <div class="flex flex-col gap-4 rounded-xl w-full max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-start">
            <div class="my-4 p-6 w-full">
                <x-supplyOrders.filter-card
                    :filterAction="route('admin.supply-orders.index')"
                    :resetUrl="route('supplyOrders.index')"
                    :supplyOrders="$supplyOrders"
                    :productId="old('productId', $filterByProductId)"
                    :quantity="old('quantity', $filterByQuantity)"
                    :userId="old('userId', $filterByUser)"
                    :status="old('status', $filterByStatus)"
                    class="mb-6"
                />
                <div class="flex items-center gap-4 mb-4 flex-wrap">
                    <flux:button variant="primary" href="{{ route('supplyOrders.create') }}">Create a new Supply Order</flux:button>
                </div>
                <div class="my-4 font-base text-sm text-gray-700 dark:text-gray-300 overflow-x-auto">
                    <x-supplyOrders.table :supplyOrders="$supplyOrders"
                                         :showView="true"
                                         :showEdit="true"
                                         :showDelete="true"
                    />
                </div>
                <div class="mt-4">
                    {{ $supplyOrders->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
</x-layouts.main-content>
