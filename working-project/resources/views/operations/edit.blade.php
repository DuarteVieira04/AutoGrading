<x-layouts.main-content :title="'Edit Operation #'.$operation->id"
                        heading="Edit Operation"
                        :subheading="'Operation #'.$operation->id">
    <div class="flex flex-col space-y-6">
        <div class="max-full">
            <section>
                <div class="static sm:absolute -top-2 right-0 flex flex-wrap justify-start sm:justify-end items-center gap-4">
                    <flux:button variant="primary" href="{{ route('operations.create', ['operation' => $operation]) }}">New</flux:button>
                    <flux:button href="{{ route('operations.show', ['operation' => $operation]) }}">View</flux:button>
                    <form method="POST" action="{{ route('operations.destroy', ['operation' => $operation]) }}">
                        @csrf
                        @method('DELETE')
                        <flux:button variant="danger" type="submit">Delete</flux:button>
                    </form>
                </div>

                <form method="POST" action="{{ route('operations.update', ['operation' => $operation]) }}">
                    @csrf
                    @method('PUT')
                    <div class="mt-6 space-y-4">
                        @include('operations.partials.fields', ['mode' => 'edit'])
                    </div>
                    <div class="flex mt-6">
                        <flux:button variant="primary" type="submit" class="uppercase">Save</flux:button>
                        <flux:button class="uppercase ms-4" href="{{ url()->full() }}">Cancel</flux:button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-layouts.main-content>
