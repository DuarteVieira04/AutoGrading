<div class="relative mb-6 w-full">
    @if (session('alert-msg'))
        <flux:callout x-data="{ visible: true }" x-show="visible"
                      variant="{{ session('alert-type') ?? 'secondary' }}">
            <div>{!! session('alert-msg') !!}</div>
            <x-slot name="controls">
                <flux:button icon="x-mark" variant="ghost" x-on:click="visible = false" />
            </x-slot>
        </flux:callout>
    @endif
    @if (!$errors->isEmpty())
        <flux:callout x-data="{ visible: true }" x-show="visible"
                      variant="warning" icon="exclamation-circle"
                      heading="Operation failed because there are validation errors!">
            <x-slot name="controls">
                <flux:button icon="x-mark" variant="ghost" x-on:click="visible = false" />
            </x-slot>
        </flux:callout>
    @endif
</div>
