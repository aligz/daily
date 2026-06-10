<?php

namespace App\Mcp\Tools;

use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetTaskTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Retrieve tasks from the database. Supports filtering by ID, status (backlog, todo, today, done), keyword search, and limiting results.';

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', 'string', 'max:255'],
            'search' => ['sometimes', 'string', 'max:255'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        // If an ID is provided, return a single task immediately.
        if (! empty($validated['id'])) {
            $task = Task::with(['user', 'history'])->find($validated['id']);

            return $task
                ? Response::json($task->toArray())
                : Response::error('Task not found.');
        }

        $query = Task::query();

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        $isDone = ($validated['status'] ?? '') === 'done';
        $limit = $isDone ? 15 : ($validated['limit'] ?? 15);

        if ($isDone) {
            $tasks = $query
                ->with(['user', 'history'])
                ->whereNotNull('completed_at')
                ->orderByDesc('completed_at')
                ->limit($limit)
                ->get();
        } else {
            $tasks = $query
                ->with(['user', 'history'])
                ->orderBy('position')
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get();
        }

        return Response::json($tasks->toArray());
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('Filter by a specific task ID. When provided, other filters are ignored.')
                ->nullable(),
            'status' => $schema->string()
                ->enum(['backlog', 'todo', 'today', 'done'])
                ->description('Filter tasks by status. Available values: backlog, todo, today, done.')
                ->nullable(),
            'search' => $schema->string()
                ->description('Search keyword that matches title, description, or reference.')
                ->nullable(),
            'limit' => $schema->integer()
                ->description('Maximum number of tasks to return (1-100). Defaults to 15.')
                ->nullable(),
        ];
    }
}
