<?php

namespace App\Http\Controllers;

use App\Models\TestExecution;
use Illuminate\Http\Request;

class TestExecutionController extends Controller
{
    public function index()
    {
        $executions = TestExecution::with('submissionResult')->get();
        return response()->json($executions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'submission_result_id' => 'required|exists:submission_results,id',
            'test_name' => 'required|string',
            'status' => 'required|string',
            'error_message' => 'nullable|string',
            'execution_logs' => 'nullable|string',
        ]);

        $execution = TestExecution::create($validated);
        return response()->json($execution, 201);
    }

    /**
     * Display the specified test execution.
     */
        return response()->json($testExecution);
    }

    /**
     * Update the specified test execution in storage.
     */
            'submission_result_id' => 'sometimes|exists:submission_results,id',
            'test_name' => 'sometimes|string',
            'status' => 'sometimes|string',
            'error_message' => 'nullable|string',
            'execution_logs' => 'nullable|string',
        ]);

        $testExecution->update($validated);
        return response()->json($testExecution);
    }

    /**
     * Remove the specified test execution from storage.
     */
        return response()->json(null, 204);
    }

    /**
     * Get test executions by submission result.
     */
            ->with('submissionResult')
            ->get();
        return response()->json($executions);
    }
}
