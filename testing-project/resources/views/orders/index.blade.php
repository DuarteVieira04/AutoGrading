<x-layouts.main-content title="Orders"
                        heading="List of Orders"
                        subheading="Manage the orders of the institution">
    <div class="flex flex-col gap-4 rounded-xl w-full max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-start">
            <div class="my-4 p-6 w-full">
                <x-orders.filter-card
                    :filterAction="route('admin.orders.index')"
                    :resetUrl="route('orders.index')"
                    :orderId="old('order_id', $orderId)"
                    :productId="old('product_id', $productId)"
                    :memberId="old('member_id', $memberId)"
                    :status="old('status', $status)"
                    :nif="old('nif', $nif)"
                    class="mb-6"
                />
                <div class="flex items-center gap-4 mb-4 flex-wrap">
                    <flux:button variant="primary" href="{{ route('orders.create') }}">Create a new Order</flux:button>
                </div>
                <div class="my-4 font-base text-sm text-gray-700 dark:text-gray-300 overflow-x-auto">
                    <x-orders.table :orders="$orders"
                                    :showView="true"
                                    :showEdit="true"
                                    :showDelete="true"
                    />
                </div>
                <div class="mt-4">
                    {{ $orders->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
</x-layouts.main-content>
