<x-layouts.main-content :title="'Edit Card #'.$card->id"
                        heading="Edit Card"
                        :subheading="'Editing card for user '.$card->user->name">
    <div class="p-8">
        <section>
            <form method="POST" action="{{ route('admin.cards.update', $card) }}">
                @csrf
                @method('PUT')
                <div class="space-y-6">
              
                    @include('cards.partials.fields', ['card' => $card, 'mode' => 'edit'])
                </div>

                <div class="mt-6 flex justify-end space-x-4">
                     <a href="{{ route('admin.cards.index') }}" class="rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" class="rounded-md border border-transparent bg-blue-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
                        Save Changes
                    </button>
                </div>
            </form>
        </section>



    </div>
</x-layouts.main-content>
