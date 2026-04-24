<?php

namespace App\Http\Controllers;

use App\Models\SubmissionResult;
use Illuminate\Http\Request;

class SubmissionResultController extends Controller
{
    public function index()
    {
        $results = SubmissionResult::with('submission', 'testExecutions')->get();
        return response()->json($results);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'submissions_id' => 'required|exists:submissions,id',
            'final_grade' => 'nullable|numeric',
            'report_sent' => 'nullable|string',
            'notified_student' => 'nullable|boolean',
            'notified_teacher' => 'nullable|boolean',
        ]);

        $result = SubmissionResult::create($validated);
        return response()->json($result, 201);
    }

    public function show(SubmissionResult $submissionResult)
    {
        $submissionResult->load('submission', 'testExecutions');
        return response()->json($submissionResult);
    }

    public function update(Request $request, SubmissionResult $submissionResult)
    {
        $validated = $request->validate([
            'submissions_id' => 'sometimes|exists:submissions,id',
            'final_grade' => 'nullable|numeric',
            'report_sent' => 'nullable|string',
            'notified_student' => 'nullable|boolean',
            'notified_teacher' => 'nullable|boolean',
        ]);

        $submissionResult->update($validated);
        return response()->json($submissionResult);
    }

    public function destroy(SubmissionResult $submissionResult)
    {
        $submissionResult->delete();
        return response()->json(null, 204);
    }

    public function getBySubmission($submissionId)
    {
        $results = SubmissionResult::where('submissions_id', $submissionId)
            ->with('submission', 'testExecutions')
            ->get();
        return response()->json($results);
    }

    public function notifyStudent(SubmissionResult $submissionResult)
    {
        $submissionResult->update(['notified_student' => true]);
        return response()->json(['message' => 'Student notified']);
    }

    public function notifyTeacher(SubmissionResult $submissionResult)
    {
        $submissionResult->update(['notified_teacher' => true]);
        return response()->json(['message' => 'Teacher notified']);
    }
}