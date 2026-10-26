<?php

namespace Database\Factories;

use App\Models\Routine;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Routine>
 */
class RoutineFactory extends Factory
{
    protected $model = Routine::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->words(3, true),
            'position' => 0,
            'starts_on' => $this->today(),
            'archived_on' => null,
        ];
    }

    public function archived(?string $date = null): static
    {
        return $this->state(fn (): array => [
            'archived_on' => $date ?? $this->today(),
        ]);
    }

    private function today(): string
    {
        return CarbonImmutable::now(config('tasks.display_timezone'))->toDateString();
    }
}
