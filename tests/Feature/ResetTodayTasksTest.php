<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it moves today tasks back to todo with a new position', function () {
    $user = User::factory()->create();
    Task::factory()->create(['user_id' => $user->id, 'status' => 'todo', 'position' => 2.0]);
    $today = Task::factory()->create(['user_id' => $user->id, 'status' => 'today', 'position' => null]);

    $this->artisan('tasks:reset-today')->assertSuccessful();

    $today->refresh();

    expect($today->status)->toBe('todo');
    expect($today->position)->toBe(3.0);

    $this->assertDatabaseHas('task_histories', [
        'task_id' => $today->id,
        'from_status' => 'today',
        'to_status' => 'todo',
    ]);
});

test('it only resets tasks that are on the today board', function () {
    $user = User::factory()->create();
    $backlog = Task::factory()->create(['user_id' => $user->id, 'status' => 'backlog']);

    $this->artisan('tasks:reset-today')->assertSuccessful();

    expect($backlog->refresh()->status)->toBe('backlog');
    $this->assertDatabaseCount('task_histories', 0);
});

test('positions are scoped per user', function () {
    $first = User::factory()->create();
    $second = User::factory()->create();

    Task::factory()->create(['user_id' => $first->id, 'status' => 'todo', 'position' => 5.0]);
    $today = Task::factory()->create(['user_id' => $second->id, 'status' => 'today']);

    $this->artisan('tasks:reset-today')->assertSuccessful();

    expect($today->refresh()->position)->toBe(1.0);
});

test('the reset is scheduled at midnight in the display timezone', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains((string) $event->command, 'tasks:reset-today'));

    expect($event)->not->toBeNull();
    expect($event->expression)->toBe('0 0 * * *');
    expect($event->timezone)->toBe(config('tasks.display_timezone'));
});
