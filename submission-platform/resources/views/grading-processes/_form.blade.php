@php
    $defaultJson = '["app", "routes", "resources"]';
    if (old('components_json') !== null) {
        $componentsJson = old('components_json');
    } elseif (isset($process) && $process) {
        $componentsJson = json_encode($process->components, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        $componentsJson = $defaultJson;
    }
@endphp

<div>
    <x-input-label for="name" :value="__('Nome do processo')" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $process->name ?? '')" required />
    <x-input-error class="mt-2" :messages="$errors->get('name')" />
</div>

<div>
    <x-input-label for="description" :value="__('Descrição (opcional)')" />
    <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">{{ old('description', $process->description ?? '') }}</textarea>
    <x-input-error class="mt-2" :messages="$errors->get('description')" />
</div>

<div>
    <x-input-label for="components_json" :value="__('Componentes (JSON array)')" />
    <p class="text-xs text-gray-500 mt-1">{{ __('Pastas a extrair do ZIP do estudante e copiar para o projeto de teste, na mesma ordem que main.py espera.') }}</p>
    <textarea id="components_json" name="components_json" rows="6" class="mt-1 block w-full font-mono text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>{{ $componentsJson }}</textarea>
    <x-input-error class="mt-2" :messages="$errors->get('components_json')" />
</div>

<div class="flex items-center gap-2">
    <input id="is_active" name="is_active" type="checkbox" value="1"
        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
        @checked(old('is_active', ($process->is_active ?? true) ? true : false)) />
    <x-input-label for="is_active" :value="__('Definir como processo ativo (os restantes serão desativados)')" class="!mb-0" />
</div>