<x-layouts.main-content :title="'Category #'.$category->id"
                        heading="Category Details"
                        :subheading="'Category #'.$category->id">
    <div class="flex flex-col space-y-6">
        <div class="max-full">
            <section>
                <div class="flex flex-wrap justify-start items-center gap-4 mb-4">
                    <flux:button variant="primary" href="{{ route('admin.categories.edit', ['category' => $category]) }}">Edit</flux:button>
                    <flux:button href="{{ route('admin.categories.create') }}">New</flux:button>
                    <form method="POST" action="{{ route('admin.categories.destroy', ['category' => $category]) }}">
                        @csrf
                        @method('DELETE')
                        <flux:button variant="danger" type="submit">Delete</flux:button>
                    </form>
                </div>
                <div style="user-select: none; pointer-events: none;">
                        @include('categories.partials.fields', ['category' => $category, 'readonly' => true, 'disabled' => true])
                    </div>
            </section>
        </div>
    </div>
</x-layouts.main-content>
