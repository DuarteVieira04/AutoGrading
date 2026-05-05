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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">

        {{-- TOTAL WEIGHTING --}}
        <div>
            <x-input-label for="weighting" :value="__('Peso total dos testes')" />

            <input
                id="weighting"
                name="configuration[weighting]"
                type="number"
                step="0.1"
                min="0"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                value="{{ old('configuration.weighting', $process->configuration['weighting'] ?? 1) }}"
            />

            <x-input-error class="mt-2" :messages="$errors->get('configuration.weighting')" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">

        {{-- RESULTS VISIBILITY --}}
        <div>
          <x-input-label for="results_visibility" :value="__('Visibilidade dos resultados')" />

            <select
                id="results_visibility"
                name="configuration[results_visibility]"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
            >
                <option value="student"
                    @selected(old('configuration.results_visibility', $process->configuration['results_visibility'] ?? 'student') === 'student')>
                    Aluno
                </option>

                <option value="teacher"
                    @selected(old('configuration.results_visibility', $process->configuration['results_visibility'] ?? '') === 'teacher')>
                    Professor
                </option>

                <option value="both"
                    @selected(old('configuration.results_visibility', $process->configuration['results_visibility'] ?? '') === 'both')>
                    Ambos
                </option>
            </select>
        </div>

        {{-- RESULTS CRITERIA --}}
        <div>
            <x-input-label for="results_criteria" :value="__('Critério de avaliação')" />

            <select
                id="results_criteria"
                name="configuration[results_criteria]"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
            >
                <option value="final_grade"
                    @selected(old('configuration.results_criteria', $process->configuration['results_criteria'] ?? 'final_grade') === 'final_grade')>
                    Nota final
                </option>

                <option value="tests_only"
                    @selected(old('configuration.results_criteria', $process->configuration['results_criteria'] ?? '') === 'tests_only')>
                    Apenas testes
                </option>
            </select>
        </div>

    </div>  

    

</div>
