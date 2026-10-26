<?php

namespace App\Actions\Tasks;

use App\Models\Task;
use App\Models\TaskHistory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ComputeTaskMetrics
{
    /**
     * Aggregate today/rollback counts and staleness for the given tasks.
     *
     * @param  Collection<int, Task>  $tasks
     * @return array<int, array{today_count: int, rollback_count: int, stuck_days: int|null, is_molor: bool, is_stale: bool}>
     */
    public function __invoke(Collection $tasks): array
    {
        $taskIds = $tasks->pluck('id')->all();

        if ($taskIds === []) {
            return [];
        }

        $aggregates = TaskHistory::query()
            ->whereIn('task_id', $taskIds)
            ->selectRaw(
                "task_id,
                SUM(CASE WHEN to_status = 'today' THEN 1 ELSE 0 END) AS today_count,
                SUM(CASE WHEN from_status = 'today' AND to_status = 'todo' THEN 1 ELSE 0 END) AS rollback_count,
                MAX(created_at) AS last_moved_at"
            )
            ->groupBy('task_id')
            ->get()
            ->keyBy('task_id');

        $thresholds = config('tasks.molor');
        $staleAfterDays = (int) config('tasks.stale_after_days');
        $timezone = config('tasks.display_timezone');

        return $tasks
            ->mapWithKeys(function (Task $task) use ($aggregates, $thresholds, $staleAfterDays, $timezone): array {
                $aggregate = $aggregates->get($task->id);
                $todayCount = (int) ($aggregate->today_count ?? 0);
                $rollbackCount = (int) ($aggregate->rollback_count ?? 0);

                $stuckDays = null;
                $lastMovedAt = $aggregate->last_moved_at ?? null;

                if ($lastMovedAt && in_array($task->status, ['backlog', 'todo'], true)) {
                    $lastMoved = CarbonImmutable::parse($lastMovedAt, 'UTC')->setTimezone($timezone);
                    $stuckDays = max(0, (int) floor($lastMoved->diffInDays(now($timezone))));
                }

                return [
                    $task->id => [
                        'today_count' => $todayCount,
                        'rollback_count' => $rollbackCount,
                        'stuck_days' => $stuckDays,
                        'is_molor' => $rollbackCount >= $thresholds['rollback_threshold']
                            || $todayCount >= $thresholds['today_threshold'],
                        'is_stale' => $stuckDays !== null && $stuckDays >= $staleAfterDays,
                    ],
                ];
            })
            ->all();
    }
}
