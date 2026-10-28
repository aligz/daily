<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('tasks.index');
    }

    return redirect()->route('feature-requests.index');
})->name('home');

Route::get('dashboard', function () {
    return redirect()->route('tasks.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('feature-requests', [\App\Http\Controllers\FeatureRequestController::class, 'index'])->name('feature-requests.index');

Route::get('feature-requests/create', [\App\Http\Controllers\FeatureRequestController::class, 'create'])->name('feature-requests.create');
Route::post('feature-requests', [\App\Http\Controllers\FeatureRequestController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('feature-requests.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('tasks/calendar', [\App\Http\Controllers\TaskCalendarController::class, 'index'])->name('tasks.calendar');
    Route::resource('tasks', \App\Http\Controllers\TaskController::class)->except(['show', 'create', 'edit']);
    Route::patch('tasks/{task}/reorder', [\App\Http\Controllers\TaskController::class, 'reorder'])->name('tasks.reorder');

    Route::post('routines', [\App\Http\Controllers\RoutineController::class, 'store'])->name('routines.store');
    Route::patch('routines/{routine}', [\App\Http\Controllers\RoutineController::class, 'update'])->name('routines.update');
    Route::patch('routines/{routine}/move', [\App\Http\Controllers\RoutineController::class, 'move'])->name('routines.move');
    Route::delete('routines/{routine}', [\App\Http\Controllers\RoutineController::class, 'archive'])->name('routines.destroy');
    Route::put('checklist/note', [\App\Http\Controllers\ChecklistController::class, 'saveNote'])->name('checklist.note.update');
    Route::patch('checklist/{routine}', [\App\Http\Controllers\ChecklistController::class, 'check'])->name('checklist.check');

    Route::get('guide', fn () => Inertia::render('Guide'))->name('guide');

    Route::get('reports', [\App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
    Route::resource('divisions', \App\Http\Controllers\DivisionController::class)->except(['create', 'show']);
    Route::get('feature-requests/{feature_request}/edit', [\App\Http\Controllers\FeatureRequestController::class, 'edit'])->name('feature-requests.edit');
    Route::put('feature-requests/{feature_request}', [\App\Http\Controllers\FeatureRequestController::class, 'update'])->name('feature-requests.update');
    Route::delete('feature-requests/{feature_request}', [\App\Http\Controllers\FeatureRequestController::class, 'destroy'])->name('feature-requests.destroy');
});

Route::get('feature-requests/{feature_request}', [\App\Http\Controllers\FeatureRequestController::class, 'show'])->name('feature-requests.show');

require __DIR__.'/settings.php';
