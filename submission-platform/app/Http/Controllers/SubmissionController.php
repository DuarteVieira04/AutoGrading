<?php

namespace App\Http\Controllers;

use App\Jobs\GradeSubmissionJob;
use App\Models\Process;
use App\Models\ProcessTestGroup;
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
            ? Submission::with(['process', 'processTestGroup', 'submissionResult.testExecutions'])
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
            })
                ->with(['processTestGroups' => fn ($q) => $q->orderBy('id')])
                ->distinct()
                ->get()
                ->filter(fn ($p) => $p->processTestGroups->isNotEmpty())
                ->values();
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
            'process_test_group_id' => 'required|exists:process_test_groups,id',
            'file' => 'required|file|mimes:zip|max:512000',
        ]);

        $group = ProcessTestGroup::with('process.groups.users')->findOrFail($request->input('process_test_group_id'));
        $process = $group->process;

        $allowed = $process->groups()
            ->whereHas('users', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })
            ->exists();

        if (! $allowed) {
            return back()->withErrors([
                'process_test_group_id' => 'You are not allowed to submit to this process.',
            ]);
        }

        $file = $request->file('file');

        $submission = Submission::create([
            'evaluation_process_id' => $process->id,
            'process_test_group_id' => $group->id,
            'student_id' => $user->id,
            'zip_file_path' => null,
            'status' => 'pending',
            'submission_date' => now(),
        ]);

        $path = $file->storeAs('autograding/submission-'.$submission->id, 'submission.zip');
        $submission->update(['zip_file_path' => $path]);

        if (config('queue.default') === 'sync') {
            $runner->grade($submission->fresh(['process', 'processTestGroup']));
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
        $submission->load('process', 'student', 'processTestGroup', 'submissionResult.testExecutions');

        return view('submissions.show', compact('submission'));
    }

    public function teacherIndex()
    {
        $submissions = Submission::with(['process', 'processTestGroup', 'student', 'submissionResult.testExecutions'])
            ->latest()
            ->get();

        return view('submissions.teacher-index', compact('submissions'));
    }

    public function teacherShow(Submission $submission)
    {
        $submission->load(['process', 'student', 'processTestGroup', 'submissionResult.testExecutions']);

        return view('submissions.show', compact('submission'));
    }
}
