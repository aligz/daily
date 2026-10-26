<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $this->get(route('tasks.calendar'))->assertRedirect(route('login'));
});

test('the calendar page renders with its props', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('tasks.calendar', ['month' => '2026-10']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('tasks/Calendar')
            ->where('month', '2026-10')
            ->has('days')
            ->has('summary')
            ->has('users')
            ->has('molorTasks')
            ->has('thresholds')
            ->where('thresholds.today', 3)
            ->where('thresholds.rollback', 2)
            ->where('thresholds.stale_days', 14)
            ->has('filters')
            ->has('days.2026-09-28')
            ->has('days.2026-11-01'));
});

test('history is grouped by the Jakarta calendar day', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id, 'status' => 'today']);

    // 17:30 UTC is 00:30 WIB on the next day.
    recordTaskHistory($task, 'todo', 'today', '2026-10-22 17:30:00');

    actingAs($user)
        ->get(route('tasks.calendar', ['month' => '2026-10']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('days.2026-10-23.today.0.id', $task->id)
            ->where('days.2026-10-22.today', []));
});

test('moving a task back to todo is recorded as a rollback', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id, 'status' => 'todo']);

    recordTaskHistory($task, 'today', 'todo', '2026-10-23 02:00:00');

    actingAs($user)
        ->get(route('tasks.calendar', ['month' => '2026-10']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('days.2026-10-23.rollback.0.id', $task->id)
            ->where('summary.rollbacks', 1));
});

test('the summary counts completed, today entries and rollbacks', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id, 'status' => 'done']);

    recordTaskHistory($task, 'todo', 'today', '2026-10-20 01:00:00');
    recordTaskHistory($task, 'today', 'todo', '2026-10-21 01:00:00');
    recordTaskHistory($task, 'todo', 'done', '2026-10-21 03:00:00');

    actingAs($user)
        ->get(route('tasks.calendar', ['month' => '2026-10']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('summary.today_entries', 1)
            ->where('summary.completed', 1)
            ->where('summary.rollbacks', 1));
});

test('the user filter only returns that user activity', function () {
    $first = User::factory()->create();
    $second = User::factory()->create();

    $firstTask = Task::factory()->create(['user_id' => $first->id, 'status' => 'today']);
    $secondTask = Task::factory()->create(['user_id' => $second->id, 'status' => 'today']);

    recordTaskHistory($firstTask, 'todo', 'today', '2026-10-23 01:00:00');
    recordTaskHistory($secondTask, 'todo', 'today', '2026-10-23 02:00:00');

    actingAs($first)
        ->get(route('tasks.calendar', ['month' => '2026-10', 'user_id' => $first->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('days.2026-10-23.today.0.id', $firstTask->id)
            ->has('days.2026-10-23.today', 1));
});

test('soft deleted tasks still appear in the calendar history', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id, 'status' => 'today']);

    recordTaskHistory($task, 'todo', 'today', '2026-10-23 01:00:00');
    $task->delete();

    actingAs($user)
        ->get(route('tasks.calendar', ['month' => '2026-10']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('days.2026-10-23.today.0.id', $task->id)
            ->where('days.2026-10-23.today.0.deleted', true));
});

test('soft deleted tasks still appear when filtering by user', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id, 'status' => 'today']);

    recordTaskHistory($task, 'todo', 'today', '2026-10-23 01:00:00');
    $task->delete();

    actingAs($user)
        ->get(route('tasks.calendar', ['month' => '2026-10', 'user_id' => $user->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('days.2026-10-23.today.0.id', $task->id)
            ->where('days.2026-10-23.today.0.deleted', true));
});

test('the most delayed task is exposed in the summary', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id, 'status' => 'todo']);

    recordTaskHistory($task, 'todo', 'today', '2026-10-20 01:00:00');
    recordTaskHistory($task, 'today', 'todo', '2026-10-21 01:00:00');
    recordTaskHistory($task, 'todo', 'today', '2026-10-22 01:00:00');
    recordTaskHistory($task, 'today', 'todo', '2026-10-23 01:00:00');

    actingAs($user)
        ->get(route('tasks.calendar', ['month' => '2026-10']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('summary.top_molor.id', $task->id)
            ->where('summary.top_molor.today_count', 2)
            ->where('summary.top_molor.rollback_count', 2));
});

test('an invalid month is rejected', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('tasks.calendar', ['month' => '2026-13']))
        ->assertSessionHasErrors('month');
});

test('an unknown user filter is rejected', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('tasks.calendar', ['user_id' => 99999]))
        ->assertSessionHasErrors('user_id');
});

test('a single rollback is not reported as delayed', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id, 'status' => 'todo']);

    recordTaskHistory($task, 'today', 'todo', '2026-10-23 01:00:00');

    actingAs($user)
        ->get(route('tasks.calendar', ['month' => '2026-10']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('summary.top_molor', null)
            ->has('molorTasks', 0));
});

test('tasks without history do not break the metrics', function () {
    $user = User::factory()->create();
    Task::factory()->create(['user_id' => $user->id, 'status' => 'backlog']);

    actingAs($user)
        ->get(route('tasks.calendar', ['month' => '2026-10']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('summary.top_molor', null)
            ->has('molorTasks', 0));
});
