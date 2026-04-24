<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Process;
use App\Models\ProcessType;
use App\Models\Group;

class ProcessController extends Controller
{
    public function index(): View
    {
        $processes = Process::where('teacher_id', auth()->id())->with('groups')->get();

        return view('processes.index', compact('processes'));
    }

    public function create(): View
    {
        return view('processes.create', [
            'groups' => Group::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'process_name' => 'nullable|string|max:255',
            'open_date' => 'nullable|string',
            'close_date' => 'nullable|string',
            'groups' => 'nullable|array',
            'groups.*' => 'exists:groups,id',
        ]);

        $defaultProcessType = ProcessType::first();

        $data = [
            'teacher_id' => auth()->id(),
            'process_type_id' => $defaultProcessType->id,
            'process_name' => $validated['process_name'] ?? null,
        ];

        // Convert dates from d/m/y H:i to Y-m-d H:i:s
        if ($validated['open_date']) {
            $data['open_date'] = $this->parseDateTime($validated['open_date']);
        }
        if ($validated['close_date']) {
            $data['close_date'] = $this->parseDateTime($validated['close_date']);
        }

        $process = Process::create($data);

        if (!empty($validated['groups'])) {
            $process->groups()->sync($validated['groups']);
        }

        return redirect()->route('processes.index')
            ->with('status', 'Processo criado.');
    }

    public function edit(Process $process)
    {
        $process->load('groups');

        return view('processes.edit', [
            'process' => $process,
            'groups' => Group::all(),
        ]);
    }

    public function update(Request $request, Process $process)
    {
        $validated = $request->validate([
            'process_name' => 'nullable|string|max:255',
            'open_date' => 'nullable|string',
            'close_date' => 'nullable|string',
            'groups' => 'nullable|array',
            'groups.*' => 'exists:groups,id',
        ]);

        $data = [
            'process_name' => $validated['process_name'] ?? null,
        ];

        // Convert dates from d/m/y H:i to Y-m-d H:i:s
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

        return redirect()
            ->route('processes.index')
            ->with('status', 'Processo atualizado.');
    }

    private function parseDateTime(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::createFromFormat('d/m/y H:i', trim($value))->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return $value;
        }
    }
    
    public function destroy(Process $process): RedirectResponse
    {
        $process->delete();

        return redirect()
            ->route('processes.index')
            ->with('status', __('Processo removido.'));
    }
}
