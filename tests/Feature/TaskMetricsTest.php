<?php

use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the board exposes metrics keyed by task id', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id, 'status' => 'todo']);
    recordTaskHistory($task, null, 'todo');

    actingAs($user)
        ->get(route('tasks.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('metrics')
            ->where("metrics.{$task->id}.today_count", 0)
            ->where("metrics.{$task->id}.rollback_count", 0)
            ->where("metrics.{$task->id}.stuck_days", 0)
            ->where("metrics.{$task->id}.is_molor", false)
            ->where("metrics.{$task->id}.is_stale", false));
});

test('a task with repeated today entries and rollbacks is flagged as delayed', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id, 'status' => 'todo']);

    recordTaskHistory($task, 'todo', 'today');
    recordTaskHistory($task, 'today', 'todo');
    recordTaskHistory($task, 'todo', 'today');
    recordTaskHistory($task, 'today', 'todo');
    recordTaskHistory($task, 'todo', 'today');

    actingAs($user)
        ->get(route('tasks.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where("metrics.{$task->id}.today_count", 3)
            ->where("metrics.{$task->id}.rollback_count", 2)
            ->where("metrics.{$task->id}.is_molor", true));
});

test('a task that has not moved for two weeks is flagged as stale', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id, 'status' => 'todo']);

    $history = recordTaskHistory($task, null, 'todo');
    $history->created_at = now()->subDays(15);
    $history->save();

    actingAs($user)
        ->get(route('tasks.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where("metrics.{$task->id}.stuck_days", 15)
            ->where("metrics.{$task->id}.is_stale", true));
});

test('completed tasks are never flagged as stale', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id, 'status' => 'done', 'completed_at' => now()]);

    $history = recordTaskHistory($task, 'todo', 'done');
    $history->created_at = now()->subDays(30);
    $history->save();

    actingAs($user)
        ->get(route('tasks.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where("metrics.{$task->id}.stuck_days", null)
            ->where("metrics.{$task->id}.is_stale", false));
});

test('a task without history has empty metrics', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id, 'status' => 'backlog']);

    actingAs($user)
        ->get(route('tasks.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where("metrics.{$task->id}.stuck_days", null)
            ->where("metrics.{$task->id}.is_molor", false)
            ->where("metrics.{$task->id}.is_stale", false));
});
