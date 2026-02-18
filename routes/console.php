<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;
use App\Models\Task;
use App\Models\TaskHistory;

Schedule::call(function () {
    $tasks = Task::where('status', 'today')->get();
    foreach ($tasks as $task) {
        $task->update(['status' => 'todo']);
        TaskHistory::create([
            'task_id' => $task->id,
            'from_status' => 'today',
            'to_status' => 'todo',
        ]);
    }
})->daily();
