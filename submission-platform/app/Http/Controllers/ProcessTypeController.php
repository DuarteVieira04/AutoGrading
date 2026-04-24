<?php

namespace App\Http\Controllers;

use App\Models\ProcessType;
use Illuminate\Http\Request;

class ProcessTypeController extends Controller
{
    public function index()
    {
        $processTypes = ProcessType::with('processes')->get();
        return response()->json($processTypes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:process_types',
        ]);

        $processType = ProcessType::create($validated);
        return response()->json($processType, 201);
    }

    public function show(ProcessType $processType)
    {
        $processType->load('processes');
        return response()->json($processType);
    }

    public function update(Request $request, ProcessType $processType)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|unique:process_types,name,' . $processType->id,
        ]);

        $processType->update($validated);
        return response()->json($processType);
    }

    public function destroy(ProcessType $processType)
    {
        $processType->delete();
        return response()->json(null, 204);
    }
}

    /**
     * Update the specified process type in storage.
     */
            'name' => 'sometimes|string|unique:process_types,name,' . $processType->id,
        ]);

        $processType->update($validated);
        return response()->json($processType);
    }

    /**
     * Remove the specified process type from storage.
     */
        return response()->json(null, 204);
    }
}
