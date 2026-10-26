<?php

use App\Actions\Checklist\ComputeChecklistStreak;
use App\Models\Routine;
use App\Models\RoutineEntry;
use App\Models\User;
use Carbon\CarbonImmutable;

function streakFor(User $user, string $today = '2026-10-26'): int
{
    return app(ComputeChecklistStreak::class)($user->id, $today);
}

function checkOn(Routine $routine, string $date, string $status = 'done'): RoutineEntry
{
    return RoutineEntry::factory()->for($routine)->create([
        'check_date' => $date,
        'status' => $status,
    ]);
}

it('counts consecutive completed days', function () {
    $user = User::factory()->create();
    $routine = Routine::factory()->for($user)->create(['starts_on' => '2026-10-20']);

    checkOn($routine, '2026-10-24');
    checkOn($routine, '2026-10-25');
    checkOn($routine, '2026-10-26');

    expect(streakFor($user))->toBe(3);
});

it('ignores an unfinished today but keeps the past streak', function () {
    $user = User::factory()->create();
    $routine = Routine::factory()->for($user)->create(['starts_on' => '2026-10-20']);

    checkOn($routine, '2026-10-24');
    checkOn($routine, '2026-10-25');

    expect(streakFor($user))->toBe(2);
});

it('counts today when everything is done', function () {
    $user = User::factory()->create();
    $routine = Routine::factory()->for($user)->create(['starts_on' => '2026-10-20']);

    checkOn($routine, '2026-10-26');

    expect(streakFor($user))->toBe(1);
});

it('breaks the streak on a missed day', function () {
    $user = User::factory()->create();
    $routine = Routine::factory()->for($user)->create(['starts_on' => '2026-10-20']);

    checkOn($routine, '2026-10-24');
    checkOn($routine, '2026-10-25');
    checkOn($routine, '2026-10-26');

    RoutineEntry::query()
        ->whereDate('check_date', '2026-10-25')
        ->delete();

    expect(streakFor($user))->toBe(1);
});

it('treats skipped routines as resolved', function () {
    $user = User::factory()->create();
    $routine = Routine::factory()->for($user)->create(['starts_on' => '2026-10-20']);

    checkOn($routine, '2026-10-24', 'skipped');
    checkOn($routine, '2026-10-25', 'skipped');
    checkOn($routine, '2026-10-26', 'done');

    expect(streakFor($user))->toBe(3);
});

it('does not break the past when a routine is created today', function () {
    $user = User::factory()->create();
    $routine = Routine::factory()->for($user)->create(['starts_on' => '2026-10-20']);

    checkOn($routine, '2026-10-24');
    checkOn($routine, '2026-10-25');

    Routine::factory()->for($user)->create(['starts_on' => '2026-10-26']);

    expect(streakFor($user))->toBe(2);
});

it('stops counting a routine once it is archived', function () {
    $user = User::factory()->create();

    $kept = Routine::factory()->for($user)->create(['starts_on' => '2026-10-20']);
    $archived = Routine::factory()->for($user)->create([
        'starts_on' => '2026-10-20',
        'archived_on' => '2026-10-24',
    ]);

    foreach (['2026-10-22', '2026-10-23', '2026-10-24', '2026-10-25', '2026-10-26'] as $date) {
        checkOn($kept, $date);
    }

    checkOn($archived, '2026-10-22');
    checkOn($archived, '2026-10-23');

    expect(streakFor($user))->toBe(5);
});

it('returns zero when the user has no routines', function () {
    $user = User::factory()->create();

    expect(streakFor($user))->toBe(0);
});

it('exposes the streak through the calendar props', function () {
    $user = User::factory()->create();
    $routine = Routine::factory()->for($user)->create(['starts_on' => '2026-10-20']);

    $today = CarbonImmutable::now(config('tasks.display_timezone'))->toDateString();
    $yesterday = CarbonImmutable::parse($today)->subDay()->toDateString();

    checkOn($routine, $yesterday);

    expect($routine->isActiveOn($today))->toBeTrue();

    $this->actingAs($user)
        ->get(route('tasks.calendar'))
        ->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->where('checklist.stats.streak', 1));
});
