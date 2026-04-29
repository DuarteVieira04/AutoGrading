<?php

use App\Http\Controllers\ProcessController;
use App\Http\Controllers\ProcessTypeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\GroupController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'teacher'])->group(function () {
    Route::resource('processes', ProcessController::class)->except(['show']);
    Route::get('/processes/{process}/submissions', [ProcessController::class, 'submissions'])->name('processes.submissions');
    Route::resource('process-types', ProcessTypeController::class)->except(['show']);
    Route::resource('groups', GroupController::class);
    Route::post('groups/{group}/users', [GroupController::class, 'addStudent'])->name('groups.addStudent');
    Route::delete('groups/{group}/users/{user}', [GroupController::class, 'removeStudent'])->name('groups.removeStudent');
    Route::get('/groups/{group}/students', [GroupController::class, 'students'])
    ->name('groups.students');
    });

Route::middleware('auth')->group(function () {
    Route::get('/submissions', [SubmissionController::class, 'index'])->name('submissions.index');
    Route::post('/submissions', [SubmissionController::class, 'store'])->name('submissions.store');
    Route::get('/submissions/{submission}', [SubmissionController::class, 'show'])->name('submissions.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
