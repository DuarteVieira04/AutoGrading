<x-layouts.main-content title="Settings"
                        heading="List of settings"
                        subheading="Manage the settings of the institution">
    <div class="flex flex-col gap-4 rounded-xl w-full max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-start">
            <div class="my-4 p-6 w-full">
                <x-settings.filter-card
                    :filterAction="route('settings.index')"
                    :resetUrl="route('settings.index')"
                    :membershipFee="old('membership_fee', $filterByMembershipFee)"
                    class="mb-6"
                />
                <div class="flex items-center gap-4 mb-4 flex-wrap">
                    <flux:button variant="primary" href="{{ route('settings.create') }}">Create a new Setting</flux:button>
                </div>
                <div class="my-4 font-base text-sm text-gray-700 dark:text-gray-300 overflow-x-auto">
                    <x-settings.table :settings="$settings"
                                     :showView="true"
                                     :showEdit="true"
                                     :showDelete="true"
                    />
                </div>
                <div class="mt-4">
                    {{ $settings->links() }}
                </div>
            </div>
        </div>
    </div>
</x-layouts.main-content>
