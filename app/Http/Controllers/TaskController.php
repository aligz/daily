<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TaskController extends Controller
{
    public function index()
    {
        $users = \App\Models\User::select('id', 'name', 'email')->get();

        $tasks = Task::with(['user', 'history'])
            ->get()
            ->groupBy('status')
            ->map(function ($statusTasks, $status) {
                if (in_array($status, ['backlog', 'todo'])) {
                    return $statusTasks->sortBy('position')->values();
                }
                return $statusTasks->sortByDesc(function ($task) {
                    return $task->completed_at ?? $task->created_at;
                })->values();
            });

        return Inertia::render('tasks/Index', [
            'tasks' => $tasks,
            'users' => $users
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'reference' => 'nullable|string|max:50',
            'status' => 'required|in:backlog,todo,today,done',
        ]);

        DB::transaction(function () use ($validated) {
            if (($validated['status'] ?? null) === 'done') {
                $validated['completed_at'] = now();
            }

            if (in_array($validated['status'], ['backlog', 'todo'])) {
                $max = auth()->user()->tasks()
                    ->where('status', $validated['status'])
                    ->max('position') ?? 0.0;
                $validated['position'] = $max + 1.0;
            }

            $task = auth()->user()->tasks()->create($validated);

            TaskHistory::create([
                'task_id' => $task->id,
                'from_status' => null,
                'to_status' => $task->status,
            ]);
        });

        return redirect()->back();
    }

    public function update(Request $request, Task $task)
    {
        if ($task->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'reference' => 'nullable|string|max:50',
            'status' => 'sometimes|in:backlog,todo,today,done',
        ]);

        DB::transaction(function () use ($task, $validated) {
            $oldStatus = $task->status;
            $newStatus = $validated['status'] ?? $oldStatus;

            if ($newStatus === 'done' && $oldStatus !== 'done') {
                $validated['completed_at'] = now();
            } elseif ($newStatus !== 'done') {
                $validated['completed_at'] = null;
            }

            if ($newStatus === 'today' && $oldStatus !== 'today') {
                $existingTodayTask = auth()->user()->tasks()
                    ->where('status', 'today')
                    ->where('id', '!=', $task->id)
                    ->first();

                if ($existingTodayTask) {
                    $maxPosition = auth()->user()->tasks()
                        ->where('status', 'todo')
                        ->max('position') ?? 0.0;
                    $existingTodayTask->update([
                        'status' => 'todo',
                        'position' => $maxPosition + 1.0,
                    ]);
                    TaskHistory::create([
                        'task_id' => $existingTodayTask->id,
                        'from_status' => 'today',
                        'to_status' => 'todo',
                    ]);
                }
            }

            if (isset($validated['status']) && $oldStatus !== $newStatus) {
                if (in_array($newStatus, ['today', 'done'])) {
                    $validated['position'] = null;
                } elseif (in_array($newStatus, ['backlog', 'todo'])) {
                    $max = auth()->user()->tasks()
                        ->where('status', $newStatus)
                        ->max('position') ?? 0.0;
                    $validated['position'] = $max + 1.0;
                }
            }

            $task->update($validated);

            if (isset($validated['status']) && $oldStatus !== $newStatus) {
                TaskHistory::create([
                    'task_id' => $task->id,
                    'from_status' => $oldStatus,
                    'to_status' => $newStatus,
                ]);
            }
        });

        return redirect()->back();
    }

    public function reorder(Request $request, Task $task)
    {
        if ($task->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        if (!in_array($task->status, ['backlog', 'todo'])) {
            abort(422, 'Cannot reorder tasks with this status.');
        }

        $validated = $request->validate([
            'position' => 'required|numeric',
        ]);

        $task->update(['position' => $validated['position']]);

        $tasks = auth()->user()->tasks()
            ->where('status', $task->status)
            ->orderBy('position')
            ->get();

        $needsRebalance = false;
        for ($i = 1; $i < $tasks->count(); $i++) {
            if (($tasks[$i]->position - $tasks[$i - 1]->position) < 0.001) {
                $needsRebalance = true;
                break;
            }
        }

        if ($needsRebalance) {
            foreach ($tasks as $index => $t) {
                $t->update(['position' => (float)($index + 1)]);
            }
        }

        return redirect()->back();
    }

    public function destroy(Task $task)
    {
        if ($task->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $task->delete();
        return redirect()->back();
    }
}
