<x-layouts.main-content title="Categories"
                        heading="List of categories"
                        subheading="Manage the categories of the institution">
    <div class="flex flex-col gap-4 rounded-xl w-full max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-start">
            <div class="my-4 p-6 w-full">
                <x-categories.filter-card
                    :filterAction="route('admin.categories.index')"
                    :resetUrl="route('categories.index')"
                    :name="old('name', $name ?? '')"
                    :image="old('image', $image ?? '')"
                    class="mb-6"
                />
                <div class="flex items-center gap-4 mb-4 flex-wrap">
                    <flux:button variant="primary" href="{{ route('admin.categories.create') }}">Create a new Category</flux:button>
                </div>
                <div class="my-4 font-base text-sm text-gray-700 dark:text-gray-300 overflow-x-auto">
                    <x-categories.table :categories="$allCategories"
                                       :showView="true"
                                       :showEdit="true"
                                       :showDelete="true"
                    />
                </div>
                <div class="mt-4">
                    {{ $allCategories->links() }}
                </div>
            </div>
        </div>
    </div>
</x-layouts.main-content>
