<div>
    <x-input-label for="name" :value="__('Nome do tipo de processo')" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name', $processType->name ?? '') }}"  />
    <x-input-error class="mt-2" :messages="$errors->get('name')" />

    @if (isset($processType) && $processType->isDefault())
        <p class="mt-2 text-sm text-gray-500">{{ __('O tipo padrão não pode ser editado nem eliminado.') }}</p>
    @endif
</div>
