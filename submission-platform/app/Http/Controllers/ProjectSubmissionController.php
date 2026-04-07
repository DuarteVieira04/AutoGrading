<?php

namespace App\Http\Controllers;

use App\Jobs\GradeProjectSubmissionJob;
use App\Models\GradingProcess;
use App\Models\ProjectSubmissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectSubmissionController extends Controller
{

    public function index()
    {
        return ProjectSubmissions::with('student.user')->get();
    }


    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'file' => 'required|file|mimes:zip|max:512000', // ≈500 MB; match PHP post_max_size / upload_max_filesize
        ]);

        $file = $request->file('file');
        $path = $file->store('submissions', 'public');

        $submission = ProjectSubmissions::create([
            'student_id' => $request->student_id,
            'grading_process_id' => GradingProcess::active()?->id,
            'file_path' => $path,
            'status' => 'pending',
        ]);

        GradeProjectSubmissionJob::dispatch($submission->id)->afterCommit();

        return response()->json($submission, 201);
    }

    public function show(ProjectSubmissions $projectSubmission)
    {
        return $projectSubmission->load('student.user');
    }

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


    public function destroy(ProjectSubmissions $projectSubmission)
    {
        Storage::disk('public')->delete($projectSubmission->file_path);
        $projectSubmission->delete();
        return response()->json(null, 204);
    }
}
