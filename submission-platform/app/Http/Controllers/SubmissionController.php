<?php

namespace App\Http\Controllers;

use App\Jobs\GradeSubmissionJob;
use App\Models\Process;
use App\Models\Submission;
use App\Services\AutoGradingRunner;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $student = $user->student;

        $submissions = $student
            ? Submission::with(['process', 'submissionResult.testExecutions'])
                ->where('student_id', $user->id)
                ->latest()
                ->get()
            : collect();

        $groups = $user->memberGroups ?? collect();
        $processes = collect();

        if ($student) {
            $groupIds = $user->memberGroups->pluck('id');
            $processes = Process::whereHas('groups', function ($query) use ($groupIds) {
                $query->whereIn('groups.id', $groupIds);
            })->distinct()->get();
        }

        return view('submissions.index', [
            'submissions' => $submissions,
            'hasStudentProfile' => (bool) $student,
            'groups' => $groups,
            'processes' => $processes,
        ]);
    }

    public function store(Request $request, AutoGradingRunner $runner)
    {
        $user = $request->user();
        $student = $user->student;

        if (! $student) {
            return back()->withErrors([
                'file' => 'Your account must be linked to a student profile to upload.',
            ]);
        }

        $request->validate([
            'evaluation_process_id' => 'required|exists:processes,id',
            'file' => 'required|file|mimes:zip|max:512000',
        ]);

        $process = Process::findOrFail($request->input('evaluation_process_id'));
        $allowed = $process->groups()
            ->whereHas('users', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })
            ->exists();

        if (! $allowed) {
            return back()->withErrors([
                'evaluation_process_id' => 'You are not allowed to submit to this process.',
            ]);
        }

        $file = $request->file('file');
        $path = $file->store('submissions');

        $submission = Submission::create([
            'evaluation_process_id' => $process->id,
            'student_id' => $user->id,
            'zip_file_path' => $path,
            'status' => 'pending',
            'submission_date' => now(),
        ]);

        if (config('queue.default') === 'sync') {
            $runner->grade($submission);
        } elseif (config('queue.default') === 'database') {
            GradeSubmissionJob::dispatchSync($submission->id);
        } else {
            GradeSubmissionJob::dispatch($submission->id)->afterCommit();
        }

        return redirect()
            ->route('submissions.index')
            ->with('success', 'Submission received successfully!');
    }

    public function show(Submission $submission)
    {
        $submission->load('process', 'student', 'submissionResult.testExecutions');

        return view('submissions.show', [
            'submission' => $submission,
        ]);
    }

    public function apiIndex()
    {
        $submissions = Submission::with('process', 'student', 'submissionResult.testExecutions')->get();
        return response()->json($submissions);
    }

    public function apiStore(Request $request)
    {
        $validated = $request->validate([
            'evaluation_process_id' => 'required|exists:processes,id',
            'student_id' => 'required|exists:users,id',
            'zip_file_path' => 'nullable|string',
            'status' => 'nullable|string',
            'submission_date' => 'nullable|date',
        ]);

        $submission = Submission::create($validated);
        return response()->json($submission, 201);
    }

    public function apiShow(Submission $submission)
    {
        $submission->load('process', 'student', 'submissionResult.testExecutions');
        return response()->json($submission);
    }

    public function apiUpdate(Request $request, Submission $submission)
    {
        $validated = $request->validate([
            'evaluation_process_id' => 'sometimes|exists:processes,id',
            'student_id' => 'sometimes|exists:users,id',
            'zip_file_path' => 'nullable|string',
            'status' => 'nullable|string',
            'submission_date' => 'nullable|date',
        ]);

        $submission->update($validated);
        return response()->json($submission);
    }

    public function apiDestroy(Submission $submission)
    {
        $submission->delete();
        return response()->json(null, 204);
    }

    public function getByProcess($processId)
    {
        $submissions = Submission::where('evaluation_process_id', $processId)
            ->with('process', 'student', 'submissionResult')
            ->get();
        return response()->json($submissions);
    }

    public function getByStudent($studentId)
    {
        $submissions = Submission::where('student_id', $studentId)
            ->with('process', 'student', 'submissionResult')
            ->get();
        return response()->json($submissions);
    }
}
