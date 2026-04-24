<?php

namespace App\Http\Controllers;

use App\Models\GradingProcess;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Process;
use App\Models\Group;

class GradingProcessController extends Controller
{
    public function index(): View
    {
        $gradingProcesses = Process::whereHas('groups.users', function ($q) {
            $q->where('users.id', auth()->id());
        })->get();

        return view('grading-processes.index', compact('gradingProcesses'));
    }

    public function create(): View
    {
        return view('grading-processes.create', [
            'groups' => Group::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'groups' => 'nullable|array',
            'groups.*' => 'exists:groups,id',
        ]);

        $process = Process::create([
            'teacher_id' => auth()->id(),
        ]);

        if (!empty($validated['groups'])) {
            $process->groups()->sync($validated['groups']);
        }

        return redirect()->route('grading-processes.index')
            ->with('status', 'Processo criado.');
    }

    public function edit(Process $grading_process)
    {
        $process->load('groups');

        return view('grading-processes.edit', [
            'process' => $process,
            'groups' => Group::all(),
        ]);
    }

    public function update(Request $request, Process $grading_process)
    {
        $validated = $request->validate([
            'groups' => 'nullable|array',
            'groups.*' => 'exists:groups,id',
        ]);

        $process->update($validated);

        $process->groups()->sync($validated['groups'] ?? []);

        return redirect()
            ->route('grading-processes.index')
            ->with('status', 'Processo atualizado.');
    }
    
    public function destroy(GradingProcess $gradingProcess): RedirectResponse
    {
        $gradingProcess->delete();

        if (GradingProcess::query()->where('is_active', true)->doesntExist()) {
            $fallback = GradingProcess::query()->orderByDesc('updated_at')->first();
            $fallback?->update(['is_active' => true]);
        }

        return redirect()
            ->route('grading-processes.index')
            ->with('status', __('Processo removido.'));
    }

    private function validated(Request $request): array
    {
        $dateFields = [
            'start_date',
            'submission_start_date',
            'submission_end_date',
            'end_date',
        ];

        foreach ($dateFields as $field) {
            $request->merge([
                $field => $this->normalizeDateTimeInput($request->input($field)),
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'components_json' => ['required', 'string'],
            'start_date' => ['required', 'date'],
            'submission_start_date' => ['required', 'date'],
            'submission_end_date' => ['required', 'date'],
            'end_date' => ['required','date'],
        ]);

        $decoded = json_decode($validated['components_json'], true);
        if (! is_array($decoded) || $decoded === []) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'components_json' => [__('Indique um array JSON não vazio, ex.: ["app","routes","resources"]')],
            ]);
        }

        foreach ($decoded as $item) {
            if (! is_string($item) || $item === '') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'components_json' => [__('Cada componente tem de ser uma string.')],
                ]);
            }
        }

        return [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'components' => array_values($decoded),
            'is_active' => $request->boolean('is_active'),
            'start_date' => $validated['start_date'],
            'submission_start_date' => $validated['submission_start_date'],
            'submission_end_date' => $validated['submission_end_date'],
            'end_date' => $validated['end_date'],
        ];
    }

    private function normalizeDateTimeInput(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return '';
        }

        try {
            return Carbon::createFromFormat('d/m/y H:i', $value)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return $value;
        }
    }
}
