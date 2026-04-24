<x-layouts.main-content title="Shipping Cost Settings"
                        heading="List of Shipping Cost Settings"
                        subheading="Manage the shipping cost settings of the institution">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl ">
        <div class="flex justify-start ">
            <div class="my-4 p-6 ">
                <x-settingsShippingCosts.filter-card
                    :filterAction="route('settingsShippingCosts.index')"
                    :resetUrl="route('settingsShippingCosts.index')"
                    :minValueThreshold="old('min_value_threshold', $filterByMinValue)"
                    :maxValueThreshold="old('max_value_threshold', $filterByMaxValue)"
                    :shippingCost="old('shipping_cost', $filterByShippingCost)"
           class="mb-6">
                </x-settingsShippingCosts.filter-card>
                <div class="flex items-center gap-4 mb-4">
                    <flux:button variant="primary" href="{{ route('settingsShippingCosts.create') }}">Create a new Shipping Cost Setting</flux:button>
                </div>
                <div class="my-4 font-base text-sm text-gray-700 dark:text-gray-300">
                    <x-settingsShippingCosts.table :settingsShippingCosts="$settingsShippingCosts"
                                                  :showView="true"
                                                  :showEdit="true"
                                                  :showDelete="true"
                    />
                </div>
                <div class="mt-4">
                    {{ $settingsShippingCosts->links() }}
                </div>
            </div>
        </div>
    </div>
</x-layouts.main-content>
