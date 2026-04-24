<x-layouts.main-content title="Cards" heading="List of cards" subheading="Manage the cards of the institution">
  <div class="flex flex-col gap-4 rounded-xl w-full max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-start">
            <div class="my-4 p-6 w-full">
                <x-cards.filter-card
                    :filterAction="route('admin.cards.index')"
                    :resetUrl="route('admin.cards.index')"
                    :card_number="old('card_number', $card_number ?? '')"
                    :balance="old('balance', $balance ?? '')"
                    :id="old('id', $id ?? '')"
                    class="mb-6"
                />
                <div class="flex items-center gap-4 mb-4 flex-wrap">
                    <flux:button variant="primary" href="{{ route('admin.cards.create') }}">Create a new Card</flux:button>
                </div>
                <div class="my-4 font-base text-sm text-gray-700 dark:text-gray-300 overflow-x-auto">
                    <x-cards.table :cards="$cards" :showView="true" :showEdit="true" :showDelete="true" />
                </div>
                <div class="mt-4">
                    {{ $cards->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
</x-layouts.main-content>
