<?php

use App\Http\Controllers\ProjectSubmissionController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\ProcessController;
use App\Http\Controllers\ProcessTypeController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\SubmissionResultController;
use App\Http\Controllers\TestExecutionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::delete('students/{student}', [StudentController::class, 'destroy'])->middleware('auth:sanctum', 'teacher');

Route::middleware(['auth:sanctum', 'teacher'])->as('api.')->group(function () {
    Route::apiResource('students', StudentController::class);
    Route::apiResource('teachers', TeacherController::class);
    Route::apiResource('project-submissions', ProjectSubmissionController::class);
    Route::apiResource('groups', GroupController::class);
    Route::apiResource('processes', ProcessController::class);
    Route::apiResource('process-types', ProcessTypeController::class);
    Route::apiResource('submission-results', SubmissionResultController::class);
    Route::apiResource('test-executions', TestExecutionController::class);
    
    // Group routes
    Route::post('groups/{group}/users', [GroupController::class, 'addUser']);
    Route::delete('groups/{group}/users', [GroupController::class, 'removeUser']);
    
    // Process routes
    Route::post('processes/{process}/groups', [ProcessController::class, 'addGroup']);
    Route::delete('processes/{process}/groups', [ProcessController::class, 'removeGroup']);
    
    // Submission routes
    Route::get('submissions/by-process/{processId}', [SubmissionController::class, 'getByProcess']);
    Route::get('submissions/by-student/{studentId}', [SubmissionController::class, 'getByStudent']);
    Route::post('submission-results/{submissionResult}/notify-student', [SubmissionResultController::class, 'notifyStudent']);
    Route::post('submission-results/{submissionResult}/notify-teacher', [SubmissionResultController::class, 'notifyTeacher']);
    Route::get('submission-results/by-submission/{submissionId}', [SubmissionResultController::class, 'getBySubmission']);
    Route::get('test-executions/by-submission-result/{submissionResultId}', [TestExecutionController::class, 'getBySubmissionResult']);
});