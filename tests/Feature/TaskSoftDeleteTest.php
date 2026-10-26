<?php

use App\Models\Task;
use App\Models\TaskHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('deleting a task keeps its history', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id]);

    TaskHistory::create([
        'task_id' => $task->id,
        'from_status' => null,
        'to_status' => $task->status,
    ]);

    $task->delete();

    $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    expect(Task::withTrashed()->find($task->id))->not->toBeNull();
    expect(TaskHistory::where('task_id', $task->id)->count())->toBe(1);
});

test('soft deleted tasks are excluded from normal queries', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id]);

    $task->delete();

    expect(Task::find($task->id))->toBeNull();
    expect(Task::where('user_id', $user->id)->count())->toBe(0);
});

test('deleting a task through the endpoint soft deletes it', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id]);

    actingAs($user)
        ->delete(route('tasks.destroy', $task))
        ->assertRedirect();

    $this->assertSoftDeleted('tasks', ['id' => $task->id]);
});
