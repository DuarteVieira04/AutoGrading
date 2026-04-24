<x-layouts.main-content title="New Stock Adjustment"
                        heading="Create a Stock Adjustment"
                        subheading='Click on "Save" button to store the information.'>
    <div class="flex flex-col space-y-6">
        <div class="max-full">
            <section>
               <form method="POST" action="{{ route('admin.stock-adjustments.store') }}"
 enctype="multipart/form-data">
                    @csrf
                    <div class="mt-6 space-y-4">
                        @include('stockAdjustments.partials.fields', ['mode' => 'create'])
                    </div>
                    <div class="flex mt-6">
                        <flux:button variant="primary" type="submit"  class="uppercase">Save</flux:button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-layouts.main-content>
