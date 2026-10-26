<?php

namespace Database\Factories;

use App\Models\Routine;
use App\Models\RoutineEntry;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RoutineEntry>
 */
class RoutineEntryFactory extends Factory
{
    protected $model = RoutineEntry::class;

    public function definition(): array
    {
        return [
            'routine_id' => Routine::factory(),
            'check_date' => CarbonImmutable::now(config('tasks.display_timezone'))->toDateString(),
            'status' => RoutineEntry::STATUS_DONE,
            'checked_at' => now(),
        ];
    }

    public function skipped(): static
    {
        return $this->state(fn (): array => [
            'status' => RoutineEntry::STATUS_SKIPPED,
        ]);
    }
}
