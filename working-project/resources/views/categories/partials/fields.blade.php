@php
    $category = $category ?? new \App\Models\Category();
    $mode = $mode ?? 'edit';
    $readonly = $mode == 'show';
@endphp

<div class="flex flex-wrap space-x-8">
    <div class="grow mt-6 space-y-4">
        <flux:input
            name="id"
            label="Category ID"
            :value="old('id', $category->id)"
            :disabled="true"
        />

        <flux:input
            name="name"
            label="Name"
            :value="old('name', $category->name)"
            :disabled="$readonly"
        />

        <flux:input
            name="image"
            label="Image"
            :value="old('image', $category->image)"
            :disabled="$readonly"
        />
    </div>
</div>
