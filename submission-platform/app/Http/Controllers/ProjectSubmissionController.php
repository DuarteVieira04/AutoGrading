<?php

namespace App\Http\Controllers;

use App\Jobs\GradeProjectSubmissionJob;
use App\Models\GradingProcess;
use App\Models\ProjectSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Process;

class ProjectSubmissionController extends Controller
{

    public function index()
    {
        $submissions = ProjectSubmission::with(['student.user', 'gradingProcess'])
            ->latest()
            ->get();

        return view('submissions.index', [
            'submissions' => $submissions,
            'hasStudentProfile' => auth()->user()->student ?? false,
            'gradingProcesses' => \App\Models\GradingProcess::all(),
        ]);
    }


    public function store(Request $request)
    {

        $student = auth()->user()->student;

        $process = Process::findOrFail($request->grading_process_id);

        $allowed = $process->groups()
        ->whereHas('users', function ($q) use ($student) {
            $q->where('users.id', $student->user_id);
        })
        ->exists();

        if (!$allowed) {
            return back()->withErrors([
                'grading_process_id' => 'You are not allowed to submit to this process.'
            ]);
        }
        
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'grading_process_id' => 'required|exists:grading_processes,id',
            'file' => 'required|file|mimes:zip|max:512000',
        ]);

        $file = $request->file('file');
        $path = $file->store('submissions', 'public');

        try {
            $submission = ProjectSubmission::create([
                'student_id' => $request->student_id,
                'grading_process_id' => GradingProcess::active()?->id,
                'file_path' => $path,
                'status' => 'pending',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('submissions');
            $absolutePath = storage_path('app/' . $path);
        }

        $result = Process::run([
            'python3', 
            '/home/tochas/ProjectInf/AutoGrading/main.py', 
            $absolutePath
        ]);
    
        if ($result->successful()) {
            $submission->update([
                'status' => 'graded',
                'feedback' => ['output' => $result->output()]
            ]);
        } else {
            $submission->update([
                'status' => 'failed',
                'feedback' => ['error' => $result->errorOutput()]
            ]);
        }

        return redirect()
        ->route('submissions.index')
        ->with('success', 'Project submitted successfully!');
    }

    public function show(ProjectSubmission $projectSubmission)
    {
        return $projectSubmission->load('student.user');
    }

    public function update(Request $request, ProjectSubmission $projectSubmission)
    {
        $request->validate([
            'status' => 'in:pending,processing,graded,failed',
            'feedback' => 'nullable|array',
            'grade' => 'nullable|numeric|min:0|max:100',
        ]);

        $projectSubmission->update($request->only(['status', 'feedback', 'grade']));
        return response()->json($projectSubmission);
    }


    public function destroy(GradingProcess $gradingProcess): RedirectResponse
    {
        $gradingProcess->delete();

        return redirect()
            ->route('grading-processes.index')
            ->with('status', __('Processo removido.'));
    }
}
