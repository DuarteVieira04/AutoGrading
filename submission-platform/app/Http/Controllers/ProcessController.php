<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Process;
use App\Models\ProcessTestGroup;
use App\Models\ProcessType;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $validated = $request->validate([
            'process_name' => 'nullable|string|max:255',
            'open_date' => 'nullable|string',
            'close_date' => 'nullable|string',
            'process_type_id' => 'nullable|exists:process_types,id',
            'groups' => 'nullable|array',
            'groups.*' => 'exists:groups,id',
            'config' => 'nullable|array',
            'config.results_visibility' => 'nullable|in:student,teacher,both',
            'config.results_criteria' => 'nullable|in:final_grade,tests_only',
            'email_notification' => 'nullable|boolean',
            'test_groups' => 'nullable|array',
            'test_groups.*.name' => 'nullable|string|max:255',
            'test_groups.*.path_pattern' => 'nullable|string|max:500',
        ]);

        $processType = ProcessType::find($validated['process_type_id']) ?: ProcessType::firstOrCreate(['name' => ProcessType::DEFAULT_NAME]);

        $data = [
            'teacher_id' => auth()->id(),
            'process_type_id' => $processType->id,
            'process_name' => $validated['process_name'] ?? null,
            'email_notification' => $request->boolean('email_notification'),
            'config' => [
                'results_visibility' => data_get($validated, 'config.results_visibility', 'student'),
                'results_criteria' => data_get($validated, 'config.results_criteria', 'final_grade'),
            ],
        ];

        if ($validated['open_date']) {
            $data['open_date'] = $this->parseDateTime($validated['open_date']);
        }
        if ($validated['close_date']) {
            $data['close_date'] = $this->parseDateTime($validated['close_date']);
        }

        $process = Process::create($data);

        if (! empty($validated['groups'])) {
            $process->groups()->sync($validated['groups']);
        }

        $this->syncProcessTestGroups($process, $request->input('test_groups', []));

        return redirect()->route('processes.index')
            ->with('status', 'Processo criado.');
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
        $validated = $request->validate([
            'process_name' => 'nullable|string|max:255',
            'open_date' => 'nullable|string',
            'close_date' => 'nullable|string',
            'process_type_id' => 'nullable|exists:process_types,id',
            'groups' => 'nullable|array',
            'groups.*' => 'exists:groups,id',
            'config' => 'nullable|array',
            'config.results_visibility' => 'nullable|in:student,teacher,both',
            'config.results_criteria' => 'nullable|in:final_grade,tests_only',
            'email_notification' => 'nullable|boolean',
            'test_groups' => 'nullable|array',
            'test_groups.*.name' => 'nullable|string|max:255',
            'test_groups.*.path_pattern' => 'nullable|string|max:500',
        ]);

        if (! empty($validated['process_type_id'])) {
            $process->process_type_id = $validated['process_type_id'];
        }

        $prevConfig = $process->config ?? [];

        $data = [
            'process_name' => $validated['process_name'] ?? null,
            'email_notification' => $request->boolean('email_notification'),
            'config' => array_merge($prevConfig, [
                'results_visibility' => data_get($validated, 'config.results_visibility', data_get($prevConfig, 'results_visibility', 'student')),
                'results_criteria' => data_get($validated, 'config.results_criteria', data_get($prevConfig, 'results_criteria', 'final_grade')),
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

        return redirect()
            ->route('processes.index')
            ->with('status', 'Processo atualizado.');
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
