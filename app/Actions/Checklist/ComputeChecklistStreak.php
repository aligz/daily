<?php

namespace App\Actions\Checklist;

use App\Models\Routine;
use App\Models\RoutineEntry;
use Carbon\CarbonImmutable;

class ComputeChecklistStreak
{
    /**
     * Number of consecutive days (ending today) where every routine that was
     * active that day is either done or skipped.
     *
     * Today itself does not break the streak while it is still in progress.
     */
    public function __invoke(int $userId, string $today, ?int $lookbackDays = null): int
    {
        $lookback = $lookbackDays ?? (int) config('checklist.streak_lookback_days');

        $routines = Routine::query()
            ->where('user_id', $userId)
            ->orderBy('position')
            ->get();

        if ($routines->isEmpty()) {
            return 0;
        }

        $start = CarbonImmutable::parse($today, config('tasks.display_timezone'))
            ->subDays($lookback - 1)
            ->toDateString();

        $entries = RoutineEntry::query()
            ->whereIn('routine_id', $routines->pluck('id'))
            ->whereBetween('check_date', [$start, $today])
            ->get()
            ->groupBy(fn (RoutineEntry $entry) => $entry->check_date->toDateString());

        $streak = 0;

        for (
            $date = CarbonImmutable::parse($today, config('tasks.display_timezone'));
            $date->toDateString() >= $start;
            $date = $date->subDay()
        ) {
            $key = $date->toDateString();
            $active = $routines->filter(fn (Routine $routine) => $routine->isActiveOn($key));

            if ($active->isEmpty()) {
                break;
            }

            $dayEntries = $entries->get($key, collect())->keyBy('routine_id');

            $resolved = $active->filter(fn (Routine $routine) => in_array(
                $dayEntries->get($routine->id)?->status,
                [RoutineEntry::STATUS_DONE, RoutineEntry::STATUS_SKIPPED],
                true,
            ))->count();

            if ($resolved === $active->count()) {
                $streak++;

                continue;
            }

            if ($key !== $today) {
                break;
            }
        }

        return $streak;
    }
}
