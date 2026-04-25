<?php

namespace App\Http\Controllers;

use App\Models\ProcessType;
use Illuminate\Http\Request;

class ProcessTypeController extends Controller
{
    public function index(Request $request)
    {
        $processTypes = ProcessType::withCount('processes')->get();

        if ($request->wantsJson()) {
            return response()->json($processTypes);
        }

        return view('process_types.index', compact('processTypes'));
    }

    public function create()
    {
        return view('process_types.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:process_types',
        ]);

        $processType = ProcessType::create($validated);

        if ($request->wantsJson()) {
            return response()->json($processType, 201);
        }

        return redirect()
            ->route('process-types.index')
            ->with('status', __('Tipo de processo criado.'));
    }

    public function show(Request $request, ProcessType $processType)
    {
        $processType->load('processes');

        if ($request->wantsJson()) {
            return response()->json($processType);
        }

        return redirect()->route('process-types.index');
    }

    public function edit(ProcessType $processType)
    {
        return view('process_types.edit', compact('processType'));
    }

    public function update(Request $request, ProcessType $processType)
    {
        if ($processType->isDefault()) {
            if ($request->wantsJson()) {
                return response()->json(['message' => __('O tipo padrão não pode ser editado.')], 403);
            }

            return redirect()
                ->route('process-types.index')
                ->with('status', __('O tipo padrão não pode ser editado.'));
        }

        $validated = $request->validate([
            'name' => 'required|string|unique:process_types,name,' . $processType->id,
        ]);

        $processType->update($validated);

        if ($request->wantsJson()) {
            return response()->json($processType);
        }

        return redirect()
            ->route('process-types.index')
            ->with('status', __('Tipo de processo atualizado.'));
    }

    public function destroy(Request $request, ProcessType $processType)
    {
        if ($processType->isDefault()) {
            if ($request->wantsJson()) {
                return response()->json(['message' => __('O tipo padrão não pode ser eliminado.')], 403);
            }

            return redirect()
                ->route('process-types.index')
                ->with('status', __('O tipo padrão não pode ser eliminado.'));
        }

        $processType->delete();

        if ($request->wantsJson()) {
            return response()->json(null, 204);
        }

        return redirect()
            ->route('process-types.index')
            ->with('status', __('Tipo de processo eliminado.'));
    }
}
