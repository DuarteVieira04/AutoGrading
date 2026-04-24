<x-layouts.main-content title="Operations"
                        heading="List of Operations"
                        subheading="Manage the operations of the institution">
    <div class="flex flex-col gap-4 rounded-xl w-full max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-start">
            <div class="my-4 p-6 w-full">
                <x-operations.filter-card
                    :filterAction="route('operations.index')"
                    :resetUrl="route('operations.index')"
                    :cardId="old('card_id', $cardId)"
                    :type="old('type', $type)"
                    :orderId="old('order_id', $orderId)"
                    class="mb-6"
                />
                <div class="flex items-center gap-4 mb-4 flex-wrap">
                    <flux:button variant="primary" href="{{ route('operations.create') }}">Create a new Operation</flux:button>
                </div>
                <div class="my-4 font-base text-sm text-gray-700 dark:text-gray-300 overflow-x-auto">
                    <x-operations.table :operations="$operations"
                                        :showView="true"
                                        :showEdit="true"
                                        :showDelete="true"
                    />
                </div>
                <div class="mt-4">
                    {{ $operations->links() }}
                </div>
            </div>
        </div>
    </div>
</x-layouts.main-content>
