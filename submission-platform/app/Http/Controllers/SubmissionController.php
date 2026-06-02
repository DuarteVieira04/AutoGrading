<?php

namespace App\Http\Controllers;

use App\Jobs\GradeSubmissionJob;
use App\Models\Process;
use App\Models\ProcessTestGroup;
use App\Models\Submission;
use App\Services\AutoGradingRunner;
use App\Support\ProcessProjectPaths;
use App\Support\SubmissionRowPresenter;
use App\Support\SuiteAutograding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class SubmissionController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $student = $user->student;

        $submissions = $student
            ? Submission::with(['process.processTestGroups', 'processTestGroup', 'submissionResult.testExecutions'])
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
                ->filter(fn ($p) => $p->processTestGroups->isNotEmpty()
                    && ProcessProjectPaths::processHasUsableBaseProject($p)
                    && $p->studentCanSubmit((int) $user->id))
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

        if (! ProcessProjectPaths::processHasUsableBaseProject($process)) {
            return back()->withErrors([
                'process_test_group_id' => __('Este processo ainda não tem projeto disponível para correção. Tente novamente assim que o docente o disponibilizar.'),
            ]);
        }

        if (! $process->studentCanSubmit((int) $user->id)) {
            $limit = $process->submissionLimit();

            return back()->withErrors([
                'process_test_group_id' => __('Atingiu o limite de :n submissões para este processo.', ['n' => $limit]),
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

        $artifactsDir = ProcessProjectPaths::ensureSubmissionArtifactsDir($submission);
        $file->move($artifactsDir, 'submission.zip');
        $submission->update(['zip_file_path' => ProcessProjectPaths::submissionZipRelative($submission)]);

        $runSync = config('autograding.run_sync', false)
            || config('queue.default') === 'sync';

        if ($runSync) {
            $runner->grade($submission->fresh(['process.processTestGroups', 'processTestGroup', 'student']));
            app(\App\Services\SubmissionGradingNotifier::class)->notify(
                $submission->fresh(['process.teacher', 'processTestGroup', 'student', 'submissionResult'])
            );
            $flash = __('Submissão recebida e corrigida.');
        } else {
            $submission->update(['status' => 'processing']);
            GradeSubmissionJob::dispatch($submission->id)->afterCommit();
            $flash = __('Submissão recebida e em correção.');
        }

        return redirect()
            ->route('submissions.index')
            ->with('success', $flash);
    }

    public function pollStatuses(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $user = $request->user();
        $ids = array_values(array_unique(array_map('intval', $request->input('ids', []))));

        $submissions = Submission::query()
            ->with(['process.processTestGroups', 'processTestGroup', 'submissionResult.testExecutions'])
            ->whereIn('id', $ids)
            ->where('student_id', $user->id)
            ->get();

        $out = [];
        foreach ($submissions as $submission) {
            $viewData = SubmissionRowPresenter::forSubmission($submission);
            $out[$submission->id] = [
                'status' => $submission->status,
                'status_label' => SubmissionRowPresenter::statusLabel($submission->status),
                'finished' => in_array($submission->status, ['graded', 'failed'], true),
                'status_html' => view('submissions.partials.status-label', ['row' => $submission])->render(),
                'grade_html' => view('submissions.partials.grade-points', [
                    'result' => $viewData['result'],
                    'finalGradePoints' => $viewData['finalGradePoints'],
                    'maxGradePoints' => $viewData['maxGradePoints'],
                    'displayFinalGrade' => $viewData['displayFinalGrade'],
                    'displayMaxGrade' => $viewData['displayMaxGrade'],
                    'displayGradeUnit' => $viewData['displayGradeUnit'],
                    'isEvaluation' => $viewData['isEvaluation'],
                    'canView' => true,
                    'showMax' => $viewData['isEvaluation'] && $viewData['evaluationMaxGrade'] !== null,
                ])->render(),
                'details_html' => view('submissions.index.partials.details-cell', $viewData)->render(),
            ];
        }

        return response()->json(['submissions' => $out]);
    }

    public function show(Submission $submission)
    {
        $user = auth()->user();
        $submission->load(['process.processTestGroups', 'student', 'processTestGroup', 'submissionResult.testExecutions']);

        $owns = (int) $user->id === (int) $submission->student_id;
        $isProcessTeacher = $user->hasRole('teacher')
            && $submission->process
            && (int) $submission->process->teacher_id === (int) $user->id;

        if (! $owns && ! $isProcessTeacher) {
            abort(403);
        }

        $payload = $submission->submissionResult?->report_sent_payload ?? [];
        $payload = is_array($payload) ? $payload : [];

        $canViewFinalGrade = SuiteAutograding::canViewFinalGrade($owns, $isProcessTeacher);
        $displayTests = SuiteAutograding::collectTestsForDisplay($submission->submissionResult, $payload);
        $testsByGroup = SuiteAutograding::groupTestsByProcessTestGroups(
            $submission->process?->processTestGroups ?? [],
            $displayTests,
            $payload
        );
        $testsByGroup = SuiteAutograding::enrichTestsByGroupWithAccess(
            $testsByGroup,
            $payload,
            $owns,
            $isProcessTeacher
        );
        $canViewGradingDetails = SuiteAutograding::canViewAnySuiteDetails(
            $testsByGroup,
            $payload,
            $owns,
            $isProcessTeacher
        );
        $suiteVisibility = SuiteAutograding::effectiveVisibilityFromSubmission($submission);

        $overallSuccessRate = $submission->submissionResult?->success_rate_percent
            ?? data_get($payload, 'results.summary.success_rate_percent')
            ?? data_get($payload, 'results.summary.success_rate');
        $overallSuccessRate = $overallSuccessRate !== null ? (float) $overallSuccessRate : null;

        $showView = SubmissionRowPresenter::forShowPage(
            $submission,
            $testsByGroup,
            $displayTests,
            $overallSuccessRate,
            $canViewFinalGrade
        );

        return view('submissions.show', array_merge($showView, [
            'canViewGradingDetails' => $canViewGradingDetails,
            'suiteVisibility' => $suiteVisibility,
            'displayTests' => $displayTests,
        ]));
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
        return $this->show($submission);
    }
}
