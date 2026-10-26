<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

test('dashboard redirects to tasks', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('tasks.index'));
});

test('user can view tasks page', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('tasks.index'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('tasks/Index')
                ->has('tasks')
                ->has('users')
        );
});

test('user can create task', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('tasks.store'), [
            'title' => 'New Task',
            'status' => 'backlog',
        ])
        ->assertRedirect();

    assertDatabaseHas('tasks', [
        'title' => 'New Task',
        'user_id' => $user->id,
        'status' => 'backlog',
    ]);

    assertDatabaseHas('task_histories', [
        'to_status' => 'backlog',
        'from_status' => null,
    ]);
    assertDatabaseHas('task_histories', [
        'to_status' => 'backlog',
        'from_status' => null,
    ]);
});

test('user can update task details', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Old Title',
        'description' => 'Old Description',
        'reference' => 'OLD-123',
    ]);

    actingAs($user)
        ->put(route('tasks.update', $task), [
            'title' => 'New Title',
            'description' => 'New Description',
            'reference' => 'NEW-456',
            'status' => $task->status,
        ])
        ->assertRedirect();

    assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => 'New Title',
        'description' => 'New Description',
        'reference' => 'NEW-456',
    ]);
});

test('moving task to another status updates history', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id, 'status' => 'backlog']);

    actingAs($user)
        ->put(route('tasks.update', $task), [
            'status' => 'todo',
        ])
        ->assertRedirect();

    $task->refresh();
    expect($task->status)->toBe('todo');

    assertDatabaseHas('task_histories', [
        'task_id' => $task->id,
        'from_status' => 'backlog',
        'to_status' => 'todo',
    ]);
});

test('moving task to today moves existing today task back to todo', function () {
    $user = User::factory()->create();
    $existingTask = Task::factory()->create(['user_id' => $user->id, 'status' => 'today']);
    $newTask = Task::factory()->create(['user_id' => $user->id, 'status' => 'todo']);

    actingAs($user)
        ->put(route('tasks.update', $newTask), [
            'status' => 'today',
        ])
        ->assertRedirect();

    $existingTask->refresh();
    $newTask->refresh();

    expect($newTask->status)->toBe('today');
    expect($existingTask->status)->toBe('todo');

    // Check History for existing task move
    assertDatabaseHas('task_histories', [
        'task_id' => $existingTask->id,
        'from_status' => 'today',
        'to_status' => 'todo',
    ]);
});

test('completed_at is set when task is marked as done', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id, 'status' => 'todo']);

    actingAs($user)
        ->put(route('tasks.update', $task), ['status' => 'done']);

    $task->refresh();
    expect($task->status)->toBe('done');
    expect($task->completed_at)->not->toBeNull();

    // Move back to todo, should clear completed_at
    actingAs($user)
        ->put(route('tasks.update', $task), ['status' => 'todo']);

    $task->refresh();
    expect($task->status)->toBe('todo');
    expect($task->completed_at)->toBeNull();
});

test('removed task resource routes are not registered', function () {
    $user = User::factory()->create();

    actingAs($user)->get('/tasks/create')->assertStatus(405);
    actingAs($user)->get('/tasks/1')->assertStatus(405);
});

test('creating a today task moves the existing today task back to todo', function () {
    $user = User::factory()->create();
    $existing = Task::factory()->create([
        'user_id' => $user->id,
        'status' => 'today',
        'position' => null,
    ]);
    Task::factory()->create([
        'user_id' => $user->id,
        'status' => 'todo',
        'position' => 5.0,
    ]);

    actingAs($user)
        ->post(route('tasks.store'), [
            'title' => 'New focus',
            'status' => 'today',
        ])
        ->assertRedirect();

    $existing->refresh();
    $new = Task::query()->where('title', 'New focus')->firstOrFail();

    expect($existing->status)->toBe('todo');
    expect($existing->position)->toBe(6.0);
    expect($new->status)->toBe('today');
    expect($new->position)->toBeNull();

    assertDatabaseHas('task_histories', [
        'task_id' => $existing->id,
        'from_status' => 'today',
        'to_status' => 'todo',
    ]);
    assertDatabaseHas('task_histories', [
        'task_id' => $new->id,
        'from_status' => null,
        'to_status' => 'today',
    ]);
});

test('creating a today task demotes every legacy today task', function () {
    $user = User::factory()->create();
    $first = Task::factory()->create(['user_id' => $user->id, 'status' => 'today']);
    $second = Task::factory()->create(['user_id' => $user->id, 'status' => 'today']);

    actingAs($user)
        ->post(route('tasks.store'), [
            'title' => 'Only focus',
            'status' => 'today',
        ])
        ->assertRedirect();

    expect($first->refresh()->status)->toBe('todo');
    expect($second->refresh()->status)->toBe('todo');
    expect(Task::query()->where('user_id', $user->id)->where('status', 'today')->count())->toBe(1);
});

test('creating a today task without an existing one keeps it on today', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('tasks.store'), [
            'title' => 'First focus',
            'status' => 'today',
        ])
        ->assertRedirect();

    expect(Task::query()->where('title', 'First focus')->firstOrFail()->status)->toBe('today');
});

test('a task can be created with a planned date', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('tasks.store'), [
            'title' => 'Planned task',
            'status' => 'todo',
            'planned_on' => '2026-10-30',
        ])
        ->assertRedirect();

    assertDatabaseHas('tasks', [
        'title' => 'Planned task',
        'planned_on' => '2026-10-30',
    ]);
});

test('the planned date can be updated and cleared', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Keep my title',
        'status' => 'todo',
        'planned_on' => '2026-10-30',
    ]);

    actingAs($user)
        ->put(route('tasks.update', $task), ['planned_on' => '2026-11-02'])
        ->assertRedirect();

    $task->refresh();
    expect($task->planned_on->toDateString())->toBe('2026-11-02');
    expect($task->title)->toBe('Keep my title');

    actingAs($user)
        ->put(route('tasks.update', $task), ['planned_on' => ''])
        ->assertRedirect();

    expect($task->refresh()->planned_on)->toBeNull();
});

test('the board exposes the planned date for card badges', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'status' => 'todo',
        'planned_on' => '2026-10-30',
    ]);

    actingAs($user)
        ->get(route('tasks.index'))
        ->assertInertia(fn ($page) => $page
            ->where('tasks.todo.0.id', $task->id)
            ->where('tasks.todo.0.planned_on', '2026-10-30'));
});
