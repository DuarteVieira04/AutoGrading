<?php

namespace App\Http\Controllers;

use App\Jobs\PrepareProcessProjectJob;
use App\Models\Group;
use App\Models\Process;
use App\Models\ProcessTestGroup;
use App\Models\ProcessType;
use App\Support\ProcessDbRebuildStrategy;
use App\Support\ProcessProjectPaths;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProcessController extends Controller
{
    public function index(): View
    {
        $processes = Process::where('teacher_id', auth()->id())->with(['groups', 'processTestGroups'])->get();

        return view('processes.index', compact('processes'));
    }

    public function create(): View
    {
        return view('processes.create', [
            'groups' => Group::all(),
            'processTypes' => ProcessType::all(),
        ]);
    }

    public function store(Request $request)
    {
        $this->guardProjectZipUpload($request);

        $validated = $request->validate($this->processRules(), $this->processMessages(), $this->processAttributes());

        $processType = ProcessType::find($validated['process_type_id']) ?: ProcessType::firstOrCreate(['name' => ProcessType::DEFAULT_NAME]);

        $notifyStudent = $request->boolean('email_notification_student');
        $notifyTeacher = $request->boolean('email_notification_teacher');
        $isEvaluation = (bool) data_get($validated, 'config.is_evaluation', false);
        $evaluationMaxGrade = $isEvaluation
            ? (float) data_get($validated, 'config.evaluation_max_grade')
            : null;
        $submissionLimit = max(0, (int) data_get($validated, 'config.submission_limit', 0));
        $dbRebuildStrategy = ProcessDbRebuildStrategy::normalize(
            data_get($validated, 'config.db_rebuild_strategy')
        );

        $data = [
            'teacher_id' => auth()->id(),
            'process_type_id' => $processType->id,
            'process_name' => $validated['process_name'] ?? null,
            'email_notification' => $notifyStudent || $notifyTeacher,
            'config' => [
                'results_visibility' => data_get($validated, 'config.results_visibility', 'student'),
                'is_evaluation' => $isEvaluation,
                'evaluation_max_grade' => $evaluationMaxGrade,
                'results_criteria' => $isEvaluation ? 'final_grade' : 'tests_only',
                'submission_limit' => $submissionLimit,
                'db_rebuild_strategy' => $dbRebuildStrategy,
                'email_notification_student' => $notifyStudent,
                'email_notification_teacher' => $notifyTeacher,
            ],
        ];

        if ($validated['open_date']) {
            $data['open_date'] = $this->parseDateTime($validated['open_date']);
        }
        if ($validated['close_date']) {
            $data['close_date'] = $this->parseDateTime($validated['close_date']);
        }

        $process = Process::create($data);

        ProcessProjectPaths::ensureProcessStorageLayout($process);

        if (! empty($validated['groups'])) {
            $process->groups()->sync($validated['groups']);
        }

        $this->syncProcessTestGroups($process, $request->input('test_groups', []));

        $uploadedZip = $request->hasFile('project_zip');
        if ($uploadedZip) {
            $this->storeProjectZipAndDispatchBuild($process, $request->file('project_zip'));
        }

        return redirect()->route('processes.index')
            ->with('status', $uploadedZip
                ? __('Processo criado. O projeto base está a ser preparado em segundo plano — mantém a fila ativa (composer run queue).')
                : __('Processo criado.'));
    }

    public function edit(Process $process)
    {
        $process->load(['groups', 'processTestGroups']);

        return view('processes.edit', [
            'process' => $process,
            'groups' => Group::all(),
            'processTypes' => ProcessType::all(),
        ]);
    }

    public function update(Request $request, Process $process)
    {
        $this->guardProjectZipUpload($request);

        $validated = $request->validate($this->processRules(), $this->processMessages(), $this->processAttributes());

        if (! empty($validated['process_type_id'])) {
            $process->process_type_id = $validated['process_type_id'];
        }

        $prevConfig = $process->config ?? [];
        $notifyStudent = $request->boolean('email_notification_student');
        $notifyTeacher = $request->boolean('email_notification_teacher');
        $isEvaluation = (bool) data_get($validated, 'config.is_evaluation', false);
        $evaluationMaxGrade = $isEvaluation
            ? (float) data_get($validated, 'config.evaluation_max_grade')
            : null;
        $submissionLimit = max(0, (int) data_get($validated, 'config.submission_limit', data_get($prevConfig, 'submission_limit', 0)));
        $dbRebuildStrategy = ProcessDbRebuildStrategy::normalize(
            data_get($validated, 'config.db_rebuild_strategy', data_get($prevConfig, 'db_rebuild_strategy'))
        );

        $data = [
            'process_name' => $validated['process_name'] ?? null,
            'email_notification' => $notifyStudent || $notifyTeacher,
            'config' => array_merge($prevConfig, [
                'results_visibility' => data_get($validated, 'config.results_visibility', data_get($prevConfig, 'results_visibility', 'student')),
                'is_evaluation' => $isEvaluation,
                'evaluation_max_grade' => $evaluationMaxGrade,
                'results_criteria' => $isEvaluation ? 'final_grade' : 'tests_only',
                'submission_limit' => $submissionLimit,
                'db_rebuild_strategy' => $dbRebuildStrategy,
                'email_notification_student' => $notifyStudent,
                'email_notification_teacher' => $notifyTeacher,
            ]),
        ];

        if (! empty($validated['process_type_id'])) {
            $data['process_type_id'] = $validated['process_type_id'];
        }

        if ($validated['open_date']) {
            $data['open_date'] = $this->parseDateTime($validated['open_date']);
        }
        if ($validated['close_date']) {
            $data['close_date'] = $this->parseDateTime($validated['close_date']);
        }

        $process->update($data);

        if (isset($validated['groups'])) {
            $process->groups()->sync($validated['groups'] ?? []);
        }

        $this->syncProcessTestGroups($process, $request->input('test_groups', []));

        $uploadedZip = $request->hasFile('project_zip');
        if ($uploadedZip) {
            $this->storeProjectZipAndDispatchBuild($process->fresh(), $request->file('project_zip'));
        }

        return redirect()
            ->route('processes.index')
            ->with('status', $uploadedZip
                ? __('Processo atualizado. O novo projeto base está a ser preparado em segundo plano (composer run queue).')
                : __('Processo atualizado.'));
    }

    /**
     * Regras de validação partilhadas entre store e update.
     *
     * @return array<string, string>
     */
    private function processRules(): array
    {
        return [
            'process_name' => 'nullable|string|max:255',
            'open_date' => 'nullable|string',
            'close_date' => 'nullable|string',
            'process_type_id' => 'nullable|exists:process_types,id',
            'groups' => 'nullable|array',
            'groups.*' => 'exists:groups,id',
            'config' => 'nullable|array',
            'config.results_visibility' => 'nullable|in:student,teacher,both',
            'config.is_evaluation' => 'nullable|boolean',
            'config.evaluation_max_grade' => 'nullable|numeric|gt:0|required_if:config.is_evaluation,1',
            'config.submission_limit' => 'nullable|integer|min:0|max:1000',
            'config.db_rebuild_strategy' => 'nullable|in:'.implode(',', ProcessDbRebuildStrategy::values()),
            'email_notification_student' => 'nullable|boolean',
            'email_notification_teacher' => 'nullable|boolean',
            'test_groups' => 'nullable|array',
            'test_groups.*.name' => 'nullable|string|max:255',
            'test_groups.*.path_pattern' => 'nullable|string|max:500',
            'project_zip' => [
                'nullable',
                'file',
                'mimes:zip',
                'mimetypes:application/zip,application/x-zip-compressed,multipart/x-zip,application/octet-stream',
                'max:1024000',
            ],
        ];
    }

    /**
     * Mensagens custom (PT) para os erros de validação mais visíveis ao docente.
     *
     * @return array<string, string>
     */
    private function processMessages(): array
    {
        return [
            'config.evaluation_max_grade.required_if' => __('Preenche a "Nota final do processo" ou desmarca "Para avaliação".'),
            'config.evaluation_max_grade.gt' => __('A "Nota final do processo" tem de ser maior que 0.'),
            'config.evaluation_max_grade.numeric' => __('A "Nota final do processo" tem de ser um número.'),
            'config.submission_limit.integer' => __('O "Total limite de submissões" tem de ser um inteiro (0 = sem limite).'),
            'config.submission_limit.min' => __('O "Total limite de submissões" não pode ser negativo (0 = sem limite).'),
            'config.submission_limit.max' => __('O "Total limite de submissões" é muito grande.'),
            'config.results_visibility.in' => __('A visibilidade dos resultados tem de ser Aluno, Professor ou Ambos.'),
            'config.db_rebuild_strategy.in' => __('A estratégia de reconstrução da base de dados não é válida.'),
            'project_zip.mimes' => __('O ficheiro do projeto base tem de ser um ZIP.'),
            'project_zip.mimetypes' => __('O ficheiro do projeto base tem de ser um ZIP.'),
            'project_zip.max' => __('O ZIP do projeto base é demasiado grande (limite ~1 GB).'),
            'project_zip.uploaded' => __('Não foi possível receber o ZIP. Verifica upload_max_filesize e post_max_size no php.ini.'),
        ];
    }

    /**
     * Nomes legíveis dos campos para as mensagens de validação.
     *
     * @return array<string, string>
     */
    private function processAttributes(): array
    {
        return [
            'process_name' => __('nome do processo'),
            'open_date' => __('data de abertura'),
            'close_date' => __('data de fecho'),
            'process_type_id' => __('tipo de processo'),
            'groups' => __('turmas'),
            'config.results_visibility' => __('visibilidade dos resultados'),
            'config.is_evaluation' => __('para avaliação'),
            'config.evaluation_max_grade' => __('nota final do processo'),
            'config.submission_limit' => __('total limite de submissões'),
            'config.db_rebuild_strategy' => __('reconstrução da base de dados'),
            'email_notification_student' => __('notificar alunos'),
            'email_notification_teacher' => __('notificar professores'),
            'test_groups' => __('grupos de testes'),
            'project_zip' => __('projeto base (ZIP)'),
        ];
    }

    private function storeProjectZipAndDispatchBuild(Process $process, \Illuminate\Http\UploadedFile $file): void
    {
        $root = ProcessProjectPaths::processRoot($process);
        File::ensureDirectoryExists($root);

        $absZip = ProcessProjectPaths::zipPath($process);
        $file->move(dirname($absZip), basename($absZip));

        $process->forceFill([
            'project_zip_path' => ProcessProjectPaths::relative($absZip),
            'project_status' => Process::PROJECT_STATUS_PENDING,
            'project_error' => null,
            'project_log' => null,
            'project_base_path' => null,
            'project_working_path' => null,
            'project_prepared_at' => null,
        ])->save();

        // Preparação do projeto base (composer/npm/phpunit) corre sempre em fila —
        // nunca bloquear o pedido HTTP durante vários minutos.
        PrepareProcessProjectJob::dispatch($process->id)->afterCommit();
    }

    /**
     * Deteta falhas de upload PHP (ex.: ZIP > upload_max_filesize) antes da validação Laravel,
     * para mostrar erro claro em project_zip em vez de falhar silenciosamente.
     */
    private function guardProjectZipUpload(Request $request): void
    {
        if (! isset($_FILES['project_zip'])) {
            return;
        }

        $error = (int) ($_FILES['project_zip']['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            return;
        }

        if ($error === UPLOAD_ERR_OK) {
            return;
        }

        throw ValidationException::withMessages([
            'project_zip' => [$this->projectZipUploadErrorMessage($error)],
        ]);
    }

    private function projectZipUploadErrorMessage(int $phpUploadError): string
    {
        return match ($phpUploadError) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => __(
                'O ZIP é demasiado grande para o limite atual do PHP (upload_max_filesize=:upload, post_max_size=:post). '
                .'Aumenta esses valores no php.ini (ex.: submission-platform/php.ini ou public/.user.ini).',
                [
                    'upload' => ini_get('upload_max_filesize') ?: '?',
                    'post' => ini_get('post_max_size') ?: '?',
                ]
            ),
            UPLOAD_ERR_PARTIAL => __('O upload do ZIP foi interrompido. Tenta enviar de novo.'),
            UPLOAD_ERR_NO_TMP_DIR => __('Servidor sem pasta temporária para uploads. Contacta o administrador.'),
            UPLOAD_ERR_CANT_WRITE => __('Não foi possível gravar o ZIP no disco do servidor.'),
            UPLOAD_ERR_EXTENSION => __('Uma extensão PHP bloqueou o upload do ZIP.'),
            default => __('Falha no upload do ZIP (código :code).', ['code' => $phpUploadError]),
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncProcessTestGroups(Process $process, array $rows): void
    {
        $process->processTestGroups()->delete();

        foreach (array_values($rows) as $i => $row) {
            if (! is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            $path = trim((string) ($row['path_pattern'] ?? ''));
            if ($name === '' && $path === '') {
                continue;
            }

            ProcessTestGroup::create([
                'process_id' => $process->id,
                'name' => $name !== '' ? $name : __('Grupo :n', ['n' => $i + 1]),
                'path_pattern' => $path !== '' ? $path : 'tests/tests',
                'visibility' => null,
            ]);
        }
    }

    private function parseDateTime(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::createFromFormat('d/m/y H:i', trim($value))->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return $value;
        }
    }

    public function submissions(Process $process)
    {
        $process->load([
            'submissions' => function ($query) {
                $query->orderByDesc('submission_date')->orderByDesc('created_at');
            },
            'submissions.student',
            'submissions.submissionResult',
            'submissions.processTestGroup',
            'processTestGroups',
        ]);

        return view('processes.submissions', compact('process'));
    }

    public function destroy(Process $process): RedirectResponse
    {
        $process->delete();

        return redirect()
            ->route('processes.index')
            ->with('status', __('Processo removido.'));
    }
}
