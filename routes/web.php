<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('tasks.index');
    }

    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return redirect()->route('tasks.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('feature-requests', [\App\Http\Controllers\FeatureRequestController::class, 'index'])->name('feature-requests.index');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('tasks', \App\Http\Controllers\TaskController::class);
    Route::patch('tasks/{task}/reorder', [\App\Http\Controllers\TaskController::class, 'reorder'])->name('tasks.reorder');
    Route::get('reports', [\App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
    Route::resource('divisions', \App\Http\Controllers\DivisionController::class)->except(['create', 'show']);
    Route::get('feature-requests/create', [\App\Http\Controllers\FeatureRequestController::class, 'create'])->name('feature-requests.create');
    Route::post('feature-requests', [\App\Http\Controllers\FeatureRequestController::class, 'store'])->name('feature-requests.store');
    Route::get('feature-requests/{feature_request}/edit', [\App\Http\Controllers\FeatureRequestController::class, 'edit'])->name('feature-requests.edit');
    Route::put('feature-requests/{feature_request}', [\App\Http\Controllers\FeatureRequestController::class, 'update'])->name('feature-requests.update');
    Route::delete('feature-requests/{feature_request}', [\App\Http\Controllers\FeatureRequestController::class, 'destroy'])->name('feature-requests.destroy');
});

Route::get('feature-requests/{feature_request}', [\App\Http\Controllers\FeatureRequestController::class, 'show'])->name('feature-requests.show');

require __DIR__.'/settings.php';
