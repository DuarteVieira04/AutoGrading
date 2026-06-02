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
            $testGroupRows = [
                ['name' => 'Core', 'path_pattern' => 'tests/tests_core'],
                ['name' => 'API', 'path_pattern' => 'tests/tests_api'],
                ['name' => 'Orders', 'path_pattern' => 'tests/tests_orders'],
                ['name' => 'Products', 'path_pattern' => 'tests/tests_products'],
                ['name' => 'Users', 'path_pattern' => 'tests/tests_users'],
                ['name' => 'Reports', 'path_pattern' => 'tests/tests_reports'],
            ];
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

@if ($errors->any())
    <div class="rounded-md border border-red-300 bg-red-50 p-4 text-sm text-red-900">
        <p class="font-semibold">{{ __('Não foi possível guardar o processo. Corrige os seguintes pontos:') }}</p>
        <ul class="mt-2 list-disc pl-5 space-y-1">
            @foreach ($errors->all() as $msg)
                <li>{{ $msg }}</li>
            @endforeach
        </ul>
    </div>
@endif

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
        @php
            $emailStudentDefault = data_get($process?->config, 'email_notification_student', $process?->email_notification ?? true);
            $emailTeacherDefault = data_get($process?->config, 'email_notification_teacher', $process?->email_notification ?? true);
        @endphp

        <fieldset class="space-y-3">
            <legend class="text-sm font-medium text-gray-800">{{ __('Notificações por email') }}</legend>
            <p class="text-xs text-gray-500">{{ __('Escolhe quem é notificado por email quando a correção terminar.') }}</p>

            <div class="flex items-start gap-2">
                <input type="hidden" name="email_notification_student" value="0" />
                <input
                    type="checkbox"
                    id="email_notification_student"
                    name="email_notification_student"
                    value="1"
                    class="mt-1 rounded border-gray-300"
                    @checked(old('email_notification_student', $emailStudentDefault))
                />
                <label for="email_notification_student" class="text-sm text-gray-800">
                    {{ __('Notificar os alunos por email') }}
                    <span class="block text-xs text-gray-500">{{ __('Apenas é enviado se a visibilidade dos resultados o permitir.') }}</span>
                </label>
            </div>

            <div class="flex items-start gap-2">
                <input type="hidden" name="email_notification_teacher" value="0" />
                <input
                    type="checkbox"
                    id="email_notification_teacher"
                    name="email_notification_teacher"
                    value="1"
                    class="mt-1 rounded border-gray-300"
                    @checked(old('email_notification_teacher', $emailTeacherDefault))
                />
                <label for="email_notification_teacher" class="text-sm text-gray-800">
                    {{ __('Notificar os professores por email') }}
                    <span class="block text-xs text-gray-500">{{ __('Envia ao docente responsável pelo processo.') }}</span>
                </label>
            </div>
        </fieldset>

        @php
            $submissionLimitDefault = data_get($process?->config, 'submission_limit', 0);
            if (! is_numeric($submissionLimitDefault) || (int) $submissionLimitDefault < 0) {
                $submissionLimitDefault = 0;
            } else {
                $submissionLimitDefault = (int) $submissionLimitDefault;
            }
        @endphp
        <div>
            <x-input-label for="submission_limit" :value="__('Total limite de submissões')" />
            <x-text-input
                id="submission_limit"
                name="config[submission_limit]"
                type="number"
                step="1"
                min="0"
                class="mt-1 block w-full md:w-48"
                :value="old('config.submission_limit', $submissionLimitDefault)"
                placeholder="0"
            />
            <p class="mt-1 text-xs text-gray-500">{{ __('Número máximo de submissões por aluno neste processo. 0 = sem limite.') }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('config.submission_limit')" />
        </div>

        @php
            $dbRebuildDefault = \App\Support\ProcessDbRebuildStrategy::normalize(
                data_get($process?->config, 'db_rebuild_strategy')
            );
        @endphp
        <div>
            <x-input-label for="db_rebuild_strategy" :value="__('Reconstrução da base de dados (por submissão)')" />
            <select
                id="db_rebuild_strategy"
                name="config[db_rebuild_strategy]"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
            >
                @foreach (\App\Support\ProcessDbRebuildStrategy::labels() as $value => $label)
                    <option value="{{ $value }}" @selected(old('config.db_rebuild_strategy', $dbRebuildDefault) === $value)>
                        {{ __($label) }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500">
                {{ __('Aplica-se na pasta working/ do processo durante cada correção. Para cópia SQLite, inclui database/database.sqlite no projeto base ou no ZIP do aluno.') }}
            </p>
            <x-input-error class="mt-2" :messages="$errors->get('config.db_rebuild_strategy')" />
        </div>

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
                @php
                    $cfg = $process?->config ?? [];
                    $isEvaluationDefault = array_key_exists('is_evaluation', $cfg)
                        ? (bool) $cfg['is_evaluation']
                        : (($cfg['results_criteria'] ?? 'final_grade') === 'final_grade');
                    $evaluationMaxGradeDefault = $cfg['evaluation_max_grade'] ?? null;
                    if (! is_numeric($evaluationMaxGradeDefault) || (float) $evaluationMaxGradeDefault <= 0) {
                        $evaluationMaxGradeDefault = '';
                    } else {
                        $evaluationMaxGradeDefault = rtrim(rtrim(number_format((float) $evaluationMaxGradeDefault, 2, '.', ''), '0'), '.');
                    }
                @endphp

                <x-input-label :value="__('Avaliação do processo')" />

                <div class="mt-1 space-y-3 rounded-md border border-gray-300 bg-white p-3 shadow-sm">
                    <div class="flex items-start gap-2">
                        <input type="hidden" name="config[is_evaluation]" value="0" />
                        <input
                            type="checkbox"
                            id="is_evaluation"
                            name="config[is_evaluation]"
                            value="1"
                            class="mt-1 rounded border-gray-300"
                            data-toggle-evaluation
                            @checked(old('config.is_evaluation', $isEvaluationDefault))
                        />
                        <label for="is_evaluation" class="text-sm text-gray-800">
                            {{ __('Para avaliação') }}
                            <span class="block text-xs text-gray-500">{{ __('Se ativo, as submissões recebem uma nota final escalada para a nota máxima do processo.') }}</span>
                        </label>
                    </div>

                    <div>
                        <x-input-label for="evaluation_max_grade" :value="__('Nota final do processo')" class="text-xs" />
                        <x-text-input
                            id="evaluation_max_grade"
                            name="config[evaluation_max_grade]"
                            type="number"
                            step="0.01"
                            min="0.01"
                            class="mt-1 block w-full"
                            :value="old('config.evaluation_max_grade', $evaluationMaxGradeDefault)"
                            placeholder="{{ __('ex.: 3') }}"
                            data-evaluation-max-grade
                        />
                        <p class="mt-1 text-xs text-gray-500">{{ __('Valor máximo da nota final (ex.: 3, 20). Apenas usado quando o processo é para avaliação.') }}</p>
                        <x-input-error class="mt-1" :messages="$errors->get('config.evaluation_max_grade')" />
                    </div>
                </div>

                <x-input-error class="mt-2" :messages="$errors->get('config.is_evaluation')" />
            </div>
        </div>
    </div>

    <script>
        (function () {
            const checkbox = document.querySelector('[data-toggle-evaluation]');
            const input = document.querySelector('[data-evaluation-max-grade]');
            if (! checkbox || ! input) {
                return;
            }
            const apply = () => {
                const enabled = checkbox.checked;
                input.disabled = ! enabled;
                input.classList.toggle('bg-gray-100', ! enabled);
                input.classList.toggle('text-gray-500', ! enabled);
            };
            checkbox.addEventListener('change', apply);
            apply();
        })();
    </script>

    <div class="mt-8 border-t border-gray-200 pt-6">
        <h3 class="text-sm font-semibold text-gray-900">{{ __('Projeto base do processo (ZIP)') }}</h3>
        <p class="mt-1 text-xs text-gray-500">{{ __('Carrega um único ZIP com o projeto Laravel completo (sem vendor/ e sem node_modules/). Após guardar, a preparação corre em segundo plano (composer, npm, migrate, phpunit) — mantém «composer run queue» ativo se usares fila database.') }}</p>

        @php
            $projectStatus = $process?->project_status;
            $projectError = $process?->project_error;
            $projectLog = $process?->project_log;
            $hasOwnProject = $process && $process->project_status === \App\Models\Process::PROJECT_STATUS_READY && $process->project_base_path;
            $hasGlobalFallback = \App\Support\ProcessProjectPaths::hasGlobalBaseProject();
            $statusLabel = match ($projectStatus) {
                \App\Models\Process::PROJECT_STATUS_READY => ['label' => __('Pronto'), 'css' => 'bg-green-100 text-green-800 border-green-200'],
                \App\Models\Process::PROJECT_STATUS_PREPARING => ['label' => __('A preparar…'), 'css' => 'bg-blue-100 text-blue-800 border-blue-200'],
                \App\Models\Process::PROJECT_STATUS_FAILED => ['label' => __('Erro'), 'css' => 'bg-red-100 text-red-800 border-red-200'],
                \App\Models\Process::PROJECT_STATUS_PENDING => ['label' => __('Pendente'), 'css' => 'bg-yellow-100 text-yellow-800 border-yellow-200'],
                default => null,
            };
        @endphp

        @if ($projectStatus !== null)
            <div class="mt-3 rounded-md border {{ $statusLabel['css'] ?? 'bg-gray-100 text-gray-800 border-gray-200' }} px-3 py-2 text-xs">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-semibold">{{ __('Estado do projeto') }}:</span>
                    <span>{{ $statusLabel['label'] ?? $projectStatus }}</span>
                    @if ($process?->project_prepared_at)
                        <span class="text-gray-600">·</span>
                        <span class="text-gray-700">{{ __('Última preparação') }}: {{ $process->project_prepared_at->format('d/m/Y H:i') }}</span>
                    @endif
                </div>
                @if ($projectError)
                    <p class="mt-2 whitespace-pre-wrap font-mono text-[11px]">{{ \Illuminate\Support\Str::limit($projectError, 800) }}</p>
                @endif
                @if ($projectLog)
                    <details class="mt-2">
                        <summary class="cursor-pointer text-[11px] text-gray-600 hover:text-gray-900">{{ __('Ver relatório de execução') }}</summary>
                        <pre class="mt-1 max-h-64 overflow-auto rounded bg-white p-2 font-mono text-[11px] text-gray-800">{{ \Illuminate\Support\Str::limit($projectLog, 6000) }}</pre>
                    </details>
                @endif
            </div>
        @endif

        @if (! $hasOwnProject)
            <div class="mt-3 rounded-md border px-3 py-2 text-xs {{ $hasGlobalFallback ? 'border-indigo-200 bg-indigo-50 text-indigo-900' : 'border-gray-200 bg-gray-50 text-gray-700' }}">
                @if ($hasGlobalFallback)
                    {{ __('Sem projeto carregado para este processo. Por defeito as submissões correm com o projeto base partilhado em base-project (na mesma árvore da plataforma).') }}
                @else
                    {{ __('Sem projeto carregado para este processo e sem base-project partilhado disponível. As submissões irão falhar até carregares um ZIP.') }}
                @endif
            </div>
        @endif

        <div class="mt-3">
            <label for="project_zip" class="block text-xs font-medium text-gray-700">{{ __('Carregar ZIP') }}</label>
            <input type="file" id="project_zip" name="project_zip" accept=".zip,application/zip"
                class="mt-1 block w-full text-sm text-gray-800 file:mr-3 file:border file:border-gray-400 file:bg-white file:px-3 file:py-2 file:text-sm file:text-gray-900" />
            <p class="mt-1 text-xs text-gray-500">{{ __('Opcional ao editar — só preenche se quiseres substituir o projeto.') }}</p>
            <x-input-error class="mt-1" :messages="$errors->get('project_zip')" />
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
