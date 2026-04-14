@php
    $defaultJson = '["app", "routes", "resources"]';
    $tomorrowStart = \Carbon\Carbon::tomorrow()->startOfDay();
    $tomorrowEnd = \Carbon\Carbon::tomorrow()->setTime(23, 59);

    if (old('components_json') !== null) {
        $componentsJson = old('components_json');
    } elseif (isset($process) && $process) {
        $componentsJson = json_encode($process->components, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        $componentsJson = $defaultJson;
    }

    $formatDateTime = static function ($value, $default) {
        if ($value !== null) {
            return $value;
        }

        if ($default instanceof \Carbon\CarbonInterface) {
            return $default->format('d/m/y H:i');
        }

        return \Carbon\Carbon::parse($default)->format('d/m/y H:i');
    };
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
    <p class="text-xs text-gray-500 mt-1">{{ __('Pastas a extrair do ZIP do estudante e copiar para o projeto de teste.') }}</p>
    <textarea id="components_json" name="components_json" rows="6" class="mt-1 block w-full font-mono text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>{{ $componentsJson }}</textarea>
    <x-input-error class="mt-2" :messages="$errors->get('components_json')" />
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    <div>
        <x-input-label for="start_date" :value="__('Data inicial')" />
        <x-text-input id="start_date" name="start_date" type="text"
            class="mt-1 block w-full"
            :value="$formatDateTime(old('start_date'), isset($process->start_date) ? $process->start_date : $tomorrowStart)"
            placeholder="dd/mm/yy hh:mm"
            inputmode="numeric"
            required />
        <p class="mt-1 text-xs text-gray-500">{{ __('Formato: dd/mm/yy hh:mm (00:00 a 23:59)') }}</p>
        <x-input-error class="mt-2" :messages="$errors->get('start_date')" />
    </div>

    <div>
        <x-input-label for="submission_start_date" :value="__('Início das submissões')" />
        <x-text-input id="submission_start_date" name="submission_start_date" type="text"
            class="mt-1 block w-full"
            :value="$formatDateTime(old('submission_start_date'), isset($process->submission_start_date) ? $process->submission_start_date : $tomorrowStart)"
            placeholder="dd/mm/yy hh:mm"
            inputmode="numeric"
            required />
        <p class="mt-1 text-xs text-gray-500">{{ __('Formato: dd/mm/yy hh:mm (00:00 a 23:59)') }}</p>
        <x-input-error class="mt-2" :messages="$errors->get('submission_start_date')" />
    </div>

    <div>
        <x-input-label for="end_date" :value="__('Data final')" />
        <x-text-input id="end_date" name="end_date" type="text"
            class="mt-1 block w-full"
            :value="$formatDateTime(old('end_date'), isset($process->end_date) ? $process->end_date : $tomorrowEnd)"
            placeholder="dd/mm/yy hh:mm"
            inputmode="numeric"
            required />
        <p class="mt-1 text-xs text-gray-500">{{ __('Formato: dd/mm/yy hh:mm (00:00 a 23:59)') }}</p>
        <x-input-error class="mt-2" :messages="$errors->get('end_date')" />
    </div>

    <div>
        <x-input-label for="submission_end_date" :value="__('Fim das submissões')" />
        <x-text-input id="submission_end_date" name="submission_end_date" type="text"
            class="mt-1 block w-full"
            :value="$formatDateTime(old('submission_end_date'), isset($process->submission_end_date) ? $process->submission_end_date : $tomorrowEnd)"
            placeholder="dd/mm/yy hh:mm"
            inputmode="numeric"
            required />
        <p class="mt-1 text-xs text-gray-500">{{ __('Formato: dd/mm/yy hh:mm (00:00 a 23:59)') }}</p>
        <x-input-error class="mt-2" :messages="$errors->get('submission_end_date')" />
    </div>

</div>

<div class="flex items-center gap-2">
    <input id="is_active" name="is_active" type="checkbox" value="1"
        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
        @checked(old('is_active', $process->is_active ?? false)) />
    
    <x-input-label for="is_active" :value="__('Processo Ativo')" class="!mb-0" />
</div>
