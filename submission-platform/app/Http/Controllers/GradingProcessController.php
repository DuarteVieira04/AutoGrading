<?php

namespace App\Http\Controllers;

use App\Models\GradingProcess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GradingProcessController extends Controller
{
    public function index(): View
    {
        $processes = GradingProcess::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('grading-processes.index', compact('processes'));
    }

    public function create(): View
    {
        return view('grading-processes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        GradingProcess::create($data);

        return redirect()
            ->route('grading-processes.index')
            ->with('status', __('Processo de correção criado.'));
    }

    public function edit(GradingProcess $gradingProcess): View
    {
        return view('grading-processes.edit', ['process' => $gradingProcess]);
    }

    public function update(Request $request, GradingProcess $gradingProcess): RedirectResponse
    {
        $gradingProcess->update($this->validated($request));

        return redirect()
            ->route('grading-processes.index')
            ->with('status', __('Processo atualizado.'));
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

    /**
     * @return array{name: string, description: string|null, components: array, is_active: bool}
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'components_json' => ['required', 'string'],
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
        ];
    }
}
