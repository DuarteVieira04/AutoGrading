<?php

use App\Http\Controllers\GradingProcessController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectSubmissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'teacher'])->group(function () {
    Route::resource('grading-processes', GradingProcessController::class)->except(['show']);
});

Route::middleware('auth')->group(function () {
    Route::get('/submissions', [ProjectSubmissionController::class, 'index'])->name('submissions.index');
    Route::post('/submissions', [ProjectSubmissionController::class, 'store'])->name('submissions.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
