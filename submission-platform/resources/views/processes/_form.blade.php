@php
    $tomorrowStart = \Carbon\Carbon::tomorrow()->startOfDay();
    $tomorrowEnd = \Carbon\Carbon::tomorrow()->setTime(23, 59);

    $testGroupRows = old('test_groups');
    if (! is_array($testGroupRows)) {
        if (isset($process) && $process->relationLoaded('processTestGroups') && $process->processTestGroups->isNotEmpty()) {
            $testGroupRows = $process->processTestGroups->map(function ($g) {
                return [
                    'name' => $g->name,
                    'path_pattern' => $g->path_pattern,
                ];
            })->values()->all();
        } else {
            $testGroupRows = [[
                'name' => '',
                'path_pattern' => 'tests/tests',
            ]];
        }
    }

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
    <x-text-input id="process_name" name="process_name" type="text" class="mt-1 block w-full" :value="old('process_name', $process?->process_name ?? '')" />
    <x-input-error class="mt-2" :messages="$errors->get('process_name')" />
</div>

<div class="grid grid-cols-1 gap-4">

    <div>
        <x-input-label for="process_type_id" :value="__('Tipo de processo')" />
        <select id="process_type_id" name="process_type_id" class="mt-1 block w-full border-gray-300 rounded-md text-sm">
            @foreach ($processTypes as $processType)
                <option value="{{ $processType->id }}"
                    @if(old('process_type_id', $process?->process_type_id ?? null) == $processType->id) selected @endif>
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
                :value="$formatDateTime(old('open_date'), $process?->open_date ?? $tomorrowStart)"
                placeholder="dd/mm/yy hh:mm"
                inputmode="numeric" />
            <p class="mt-1 text-xs text-gray-500">{{ __('Formato: dd/mm/yy hh:mm (00:00 a 23:59)') }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('open_date')" />
        </div>

        <div>
            <x-input-label for="close_date" :value="__('Data de fecho')" />
            <x-text-input id="close_date" name="close_date" type="text"
                class="mt-1 block w-full"
                :value="$formatDateTime(old('close_date'), $process?->close_date ?? $tomorrowEnd)"
                placeholder="dd/mm/yy hh:mm"
                inputmode="numeric" />
            <p class="mt-1 text-xs text-gray-500">{{ __('Formato: dd/mm/yy hh:mm (00:00 a 23:59)') }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('close_date')" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 mt-6">
        <p class="text-xs text-gray-600 rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
            {{ __('Por cada pasta de testes no projeto base (ex.: base-project/tests/tests1/), coloca um ficheiro') }}
            <code class="rounded bg-white px-1">autograding.json</code>
            {{ __('com peso (weight), visibilidade (visibility: student, teacher, both) e finalidade (purpose: formative ou summative). Opcionalmente o peso global do processo na UC em') }}
            <code class="rounded bg-white px-1">config/autograding.php</code>
            (<code class="rounded bg-white px-1">process_weight_percent</code>).
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-input-label for="results_visibility" :value="__('Visibilidade dos resultados')" />
                <select
                    id="results_visibility"
                    name="config[results_visibility]"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                >
                    <option value="student" @selected(old('config.results_visibility', data_get($process?->config, 'results_visibility', 'student')) === 'student')>{{ __('Aluno') }}</option>
                    <option value="teacher" @selected(old('config.results_visibility', data_get($process?->config, 'results_visibility', '')) === 'teacher')>{{ __('Professor') }}</option>
                    <option value="both" @selected(old('config.results_visibility', data_get($process?->config, 'results_visibility', '')) === 'both')>{{ __('Ambos') }}</option>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('config.results_visibility')" />
            </div>

            <div>
                <x-input-label for="results_criteria" :value="__('Critério de avaliação')" />
                <select
                    id="results_criteria"
                    name="config[results_criteria]"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                >
                    <option value="final_grade" @selected(old('config.results_criteria', data_get($process?->config, 'results_criteria', 'final_grade')) === 'final_grade')>{{ __('Nota final') }}</option>
                    <option value="tests_only" @selected(old('config.results_criteria', data_get($process?->config, 'results_criteria', '')) === 'tests_only')>{{ __('Apenas testes') }}</option>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('config.results_criteria')" />
            </div>
        </div>
    </div>

    <div class="mt-8 border-t border-gray-200 pt-6">
        <h3 class="text-sm font-semibold text-gray-900">{{ __('Grupos de testes (pastas)') }}</h3>
        <p class="mt-1 text-xs text-gray-500">{{ __('Cada grupo associa um nome a uma pasta no projeto base (ex.: tests/tests1). O ficheiro autograding.json nessa pasta é usado na correção automática; não é editável aqui.') }}</p>

        <div class="mt-4 space-y-4">
            @foreach ($testGroupRows as $i => $row)
                <div class="rounded-lg border border-gray-200 bg-slate-50 p-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">{{ __('Nome') }}</label>
                        <input type="text" name="test_groups[{{ $i }}][name]" value="{{ $row['name'] ?? '' }}"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm"
                            placeholder="{{ __('Ex.: Testes unitários') }}" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">{{ __('Caminho / padrão da pasta') }}</label>
                        <input type="text" name="test_groups[{{ $i }}][path_pattern]" value="{{ $row['path_pattern'] ?? '' }}"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm font-mono"
                            placeholder="tests/tests1 …" />
                    </div>
                </div>
            @endforeach
        </div>
        <p class="mt-2 text-xs text-gray-500">{{ __('Para adicionar mais grupos, duplica os campos ou será possível numa próxima versão com botão «Adicionar grupo».') }}</p>
    </div>

</div>
