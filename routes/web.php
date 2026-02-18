<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return redirect()->route('tasks.index');
})->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('tasks', \App\Http\Controllers\TaskController::class);
});

require __DIR__ . '/settings.php';
