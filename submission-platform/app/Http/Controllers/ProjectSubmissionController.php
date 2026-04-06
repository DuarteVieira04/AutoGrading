<?php

namespace App\Http\Controllers;

use App\Models\ProjectSubmissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectSubmissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return ProjectSubmissions::with('student.user')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'file' => 'required|file|mimes:zip|max:10240', // 10MB zip
        ]);

        $file = $request->file('file');
        $path = $file->store('submissions', 'public');

        $submission = ProjectSubmissions::create([
            'student_id' => $request->student_id,
            'file_path' => $path,
            'status' => 'pending',
        ]);

        // Trigger grading job here, but for now, just create

        return response()->json($submission, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ProjectSubmissions $projectSubmission)
    {
        return $projectSubmission->load('student.user');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProjectSubmissions $projectSubmission)
    {
        $request->validate([
            'status' => 'in:pending,processing,graded,failed',
            'feedback' => 'nullable|array',
            'grade' => 'nullable|numeric|min:0|max:100',
        ]);

        $projectSubmission->update($request->only(['status', 'feedback', 'grade']));
        return response()->json($projectSubmission);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProjectSubmissions $projectSubmission)
    {
        Storage::disk('public')->delete($projectSubmission->file_path);
        $projectSubmission->delete();
        return response()->json(null, 204);
    }
}
