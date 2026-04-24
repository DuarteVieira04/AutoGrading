<x-layouts.main-content :title="'Operation #'.$operation->id"
                        heading="Operation Details"
                        :subheading="'Operation #'.$operation->id">
    <div class="flex flex-col space-y-6">
        <div class="max-full">
            <section>
                <div class="flex flex-wrap justify-start items-center gap-4 mb-4">
                    <flux:button variant="primary" href="{{ route('operations.edit', ['operation' => $operation]) }}">Edit</flux:button>
                    <flux:button href="{{ route('operations.create') }}">New</flux:button>
                    <form method="POST" action="{{ route('operations.destroy', ['operation' => $operation]) }}">
                        @csrf
                        @method('DELETE')
                        <flux:button variant="danger" type="submit">Delete</flux:button>
                    </form>
                </div>
                <div style="user-select: none; pointer-events: none;">
                        @include('operations.partials.fields', ['operation' => $operation, 'readonly' => true, 'disabled' => true])
                    </div>
            </section>
        </div>
    </div>
</x-layouts.main-content>
