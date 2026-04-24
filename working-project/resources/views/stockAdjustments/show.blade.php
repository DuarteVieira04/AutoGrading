<x-layouts.main-content :title="'Stock Adjustment #'.$stockAdjustment->id"
                        heading="Stock Adjustment Details"
                        :subheading="'Stock Adjustment #'.$stockAdjustment->id">
    <div class="flex flex-col space-y-6">
        <div class="max-full">
            <section>
                <div class="flex flex-wrap justify-start items-center gap-4 mb-4">
                    <flux:button variant="primary" href="{{ route('admin.stock-adjustments.edit', ['stock_adjustment' => $stockAdjustment]) }}">Edit</flux:button>
                    <flux:button href="{{ route('admin.stock-adjustments.create') }}">New</flux:button>
                    <form method="POST" action="{{ route('admin.stock-adjustments.destroy', ['stock_adjustment' => $stockAdjustment]) }}">
                        @csrf
                        @method('DELETE')
                        <flux:button variant="danger" type="submit">Delete</flux:button>
                    </form>
                </div>
                <div style="user-select: none; pointer-events: none;">
                        @include('stockAdjustments.partials.fields', ['stockAdjustment' => $stockAdjustment, 'readonly' => true, 'disabled' => true])
                    </div>
            </section>
        </div>
    </div>
</x-layouts.main-content>
