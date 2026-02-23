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
        $tasks = Task::with(['user', 'history'])
            ->get()
            ->sortByDesc(function ($task) {
                return $task->status === 'done' ? $task->completed_at : $task->created_at;
            })
            ->groupBy('status');

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks
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
            $task = auth()->user()->tasks()->create($validated);

            // Record history
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
                // Find existing today task and move to todo
                /** @var \App\Models\Task|null $existingTodayTask */
                $existingTodayTask = auth()->user()->tasks()
                    ->where('status', 'today')
                    ->where('id', '!=', $task->id)
                    ->first();

                if ($existingTodayTask) {
                    $existingTodayTask->update(['status' => 'todo']);
                    TaskHistory::create([
                        'task_id' => $existingTodayTask->id,
                        'from_status' => 'today',
                        'to_status' => 'todo',
                    ]);
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

    public function destroy(Task $task)
    {
        if ($task->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $task->delete();
        return redirect()->back();
    }
}
