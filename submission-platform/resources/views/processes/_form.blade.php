@php
    $tomorrowStart = \Carbon\Carbon::tomorrow()->startOfDay();
    $tomorrowEnd = \Carbon\Carbon::tomorrow()->setTime(23, 59);

    $formatDateTime = static function ($value, $default) {
        if ($value !== null && $value instanceof \Carbon\CarbonInterface) {
            return $value->format('d/m/y H:i');
        }

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
    <x-input-label for="process_name" :value="__('Nome do processo')" />
    <x-text-input id="process_name" name="process_name" type="text" class="mt-1 block w-full" :value="old('process_name', $process->process_name ?? '')" />
    <x-input-error class="mt-2" :messages="$errors->get('process_name')" />
</div>

<div class="grid grid-cols-1 gap-4">

    <div>
        <x-input-label for="process_type_id" :value="__('Tipo de processo')" />
        <select id="process_type_id" name="process_type_id" class="mt-1 block w-full border-gray-300 rounded-md text-sm">
            @foreach ($processTypes as $processType)
                <option value="{{ $processType->id }}"
                    @if(old('process_type_id', $process->process_type_id ?? null) == $processType->id) selected @endif>
                    {{ $processType->name }}
                </option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('process_type_id')" />
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-800">
            {{ __('Groups') }}
        </label>

        <select name="groups[]" multiple
                class="mt-2 block w-full border-gray-300 rounded-md text-sm">

            @foreach ($groups as $group)
                <option value="{{ $group->id }}"
                    @if(isset($process) && $process->groups->contains($group)) selected @endif>
                    {{ $group->name }}
                </option>
            @endforeach

        </select>

        <p class="text-xs text-gray-500 mt-1">
            {{ __('Grupos que podem submeter.') }}
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <x-input-label for="open_date" :value="__('Data de abertura')" />
            <x-text-input id="open_date" name="open_date" type="text"
                class="mt-1 block w-full"
                :value="$formatDateTime(old('open_date'), isset($process->open_date) ? $process->open_date : $tomorrowStart)"
                placeholder="dd/mm/yy hh:mm"
                inputmode="numeric" />
            <p class="mt-1 text-xs text-gray-500">{{ __('Formato: dd/mm/yy hh:mm (00:00 a 23:59)') }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('open_date')" />
        </div>

        <div>
            <x-input-label for="close_date" :value="__('Data de fecho')" />
            <x-text-input id="close_date" name="close_date" type="text"
                class="mt-1 block w-full"
                :value="$formatDateTime(old('close_date'), isset($process->close_date) ? $process->close_date : $tomorrowEnd)"
                placeholder="dd/mm/yy hh:mm"
                inputmode="numeric" />
            <p class="mt-1 text-xs text-gray-500">{{ __('Formato: dd/mm/yy hh:mm (00:00 a 23:59)') }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('close_date')" />
        </div>
    </div>

</div>
