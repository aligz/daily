<?php

namespace App\Http\Controllers;

use App\Actions\Tasks\ComputeTaskMetrics;
use App\Models\Task;
use App\Models\TaskHistory;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaskCalendarController extends Controller
{
    private const MAX_EVENTS_PER_DAY = 50;

    public function index(Request $request, ComputeTaskMetrics $computeMetrics)
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $timezone = config('tasks.display_timezone');
        $monthParam = $validated['month'] ?? now($timezone)->format('Y-m');
        $userId = isset($validated['user_id']) ? (int) $validated['user_id'] : null;

        $monthStart = CarbonImmutable::parse($monthParam.'-01', $timezone)->startOfMonth();
        $monthEnd = $monthStart->endOfMonth();
        $gridStart = $monthStart->startOfWeek(CarbonImmutable::MONDAY);
        $gridEnd = $monthEnd->endOfWeek(CarbonImmutable::SUNDAY);

        $histories = TaskHistory::query()
            ->whereBetween('created_at', [$gridStart->utc(), $gridEnd->endOfDay()->utc()])
            ->when($userId, fn ($query) => $query->whereHas(
                'task',
                fn ($taskQuery) => $taskQuery->withTrashed()->where('user_id', $userId)
            ))
            ->with([
                'task' => fn ($query) => $query->withTrashed()
                    ->select('id', 'user_id', 'title', 'reference', 'status', 'deleted_at'),
                'task.user:id,name',
            ])
            ->orderBy('created_at')
            ->get();

        $days = $this->buildDays($gridStart, $gridEnd, $histories, $timezone);

        $plannedTasks = Task::query()
            ->with('user:id,name')
            ->whereNotNull('planned_on')
            ->whereBetween('planned_on', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->orderBy('planned_on')
            ->get(['id', 'user_id', 'title', 'reference', 'status', 'planned_on']);

        foreach ($plannedTasks as $plannedTask) {
            $date = $plannedTask->planned_on->toDateString();

            if (! isset($days[$date])) {
                continue;
            }

            $days[$date]['planned'][] = [
                'id' => $plannedTask->id,
                'title' => $plannedTask->title,
                'reference' => $plannedTask->reference,
                'user' => ['id' => $plannedTask->user->id, 'name' => $plannedTask->user->name],
                'at' => $date,
                'deleted' => false,
            ];
        }

        foreach (array_keys($days) as $date) {
            $days[$date]['planned'] = array_slice($days[$date]['planned'], 0, self::MAX_EVENTS_PER_DAY);
        }

        $monthKey = $monthStart->format('Y-m');
        $monthHistories = $histories->filter(
            fn (TaskHistory $history) => $history->created_at->setTimezone($timezone)->format('Y-m') === $monthKey
        );

        $tasks = Task::query()
            ->with('user:id,name')
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->get(['id', 'user_id', 'title', 'reference', 'status']);

        $molorTasks = $this->buildMolorTasks($tasks, $computeMetrics($tasks));

        $summary = [
            'completed' => $monthHistories->where('to_status', 'done')->count(),
            'today_entries' => $monthHistories->where('to_status', 'today')->count(),
            'rollbacks' => $monthHistories->filter(
                fn (TaskHistory $history) => $history->from_status === 'today' && $history->to_status === 'todo'
            )->count(),
            'planned' => $plannedTasks->filter(
                fn (Task $task) => $task->planned_on->format('Y-m') === $monthKey
            )->count(),
            'top_molor' => $molorTasks[0] ?? null,
        ];

        return Inertia::render('tasks/Calendar', [
            'month' => $monthStart->format('Y-m'),
            'grid' => [
                'start' => $gridStart->toDateString(),
                'end' => $gridEnd->toDateString(),
            ],
            'days' => $days,
            'summary' => $summary,
            'molorTasks' => $molorTasks,
            'users' => User::query()->select('id', 'name')->orderBy('name')->get(),
            'filters' => ['user_id' => $userId],
            'thresholds' => [
                'today' => config('tasks.molor.today_threshold'),
                'rollback' => config('tasks.molor.rollback_threshold'),
                'stale_days' => config('tasks.stale_after_days'),
            ],
        ]);
    }

    /**
     * @return array<string, array{today: array<int, array<string, mixed>>, done: array<int, array<string, mixed>>, rollback: array<int, array<string, mixed>>, planned: array<int, array<string, mixed>>}>
     */
    private function buildDays(
        CarbonImmutable $gridStart,
        CarbonImmutable $gridEnd,
        \Illuminate\Support\Collection $histories,
        string $timezone
    ): array {
        $days = [];

        for ($date = $gridStart; $date->lte($gridEnd); $date = $date->addDay()) {
            $days[$date->toDateString()] = ['today' => [], 'done' => [], 'rollback' => [], 'planned' => []];
        }

        foreach ($histories as $history) {
            $type = match (true) {
                $history->to_status === 'today' => 'today',
                $history->to_status === 'done' => 'done',
                $history->from_status === 'today' && $history->to_status === 'todo' => 'rollback',
                default => null,
            };

            $task = $history->task;

            if ($type === null || $task === null || ! $task->user) {
                continue;
            }

            $date = $history->created_at->setTimezone($timezone)->toDateString();

            $days[$date][$type][] = [
                'id' => $task->id,
                'title' => $task->title,
                'reference' => $task->reference,
                'user' => ['id' => $task->user->id, 'name' => $task->user->name],
                'at' => $history->created_at->setTimezone($timezone)->toIso8601String(),
                'deleted' => $task->trashed(),
            ];
        }

        foreach ($days as $date => $buckets) {
            $days[$date] = array_map(
                fn (array $events) => array_slice($events, 0, self::MAX_EVENTS_PER_DAY),
                $buckets
            );
        }

        return $days;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Task>  $tasks
     * @param  array<int, array{today_count: int, rollback_count: int, stuck_days: int|null, is_molor: bool}>  $metrics
     * @return array<int, array<string, mixed>>
     */
    private function buildMolorTasks(\Illuminate\Support\Collection $tasks, array $metrics): array
    {
        return $tasks
            ->filter(fn (Task $task) => $metrics[$task->id]['is_molor'] ?? false)
            ->map(function (Task $task) use ($metrics): array {
                $metric = $metrics[$task->id] ?? [
                    'today_count' => 0,
                    'rollback_count' => 0,
                ];

                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'status' => $task->status,
                    'user' => ['id' => $task->user->id, 'name' => $task->user->name],
                    'today_count' => $metric['today_count'],
                    'rollback_count' => $metric['rollback_count'],
                ];
            })
            ->sortByDesc(fn (array $task) => $task['today_count'] * 2 + $task['rollback_count'])
            ->take(3)
            ->values()
            ->all();
    }
}
