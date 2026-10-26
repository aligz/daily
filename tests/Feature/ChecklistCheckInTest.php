<?php

use App\Models\Routine;
use App\Models\RoutineEntry;
use App\Models\User;
use Carbon\CarbonImmutable;

function wibToday(): string
{
    return CarbonImmutable::now(config('tasks.display_timezone'))->toDateString();
}

it('marks a routine as done for today', function () {
    $user = User::factory()->create();
    $routine = Routine::factory()->for($user)->create();

    $this->actingAs($user)
        ->patch(route('checklist.check', $routine), [
            'date' => wibToday(),
            'status' => 'done',
        ])
        ->assertRedirect();

    expect(RoutineEntry::count())->toBe(1)
        ->and(RoutineEntry::first()->status)->toBe('done')
        ->and(RoutineEntry::first()->checked_at)->not->toBeNull();
});

it('keeps a single entry when checking twice', function () {
    $user = User::factory()->create();
    $routine = Routine::factory()->for($user)->create();

    foreach (['done', 'done'] as $status) {
        $this->actingAs($user)->patch(route('checklist.check', $routine), [
            'date' => wibToday(),
            'status' => $status,
        ]);
    }

    expect(RoutineEntry::count())->toBe(1);
});

it('marks a routine as skipped', function () {
    $user = User::factory()->create();
    $routine = Routine::factory()->for($user)->create();

    $this->actingAs($user)->patch(route('checklist.check', $routine), [
        'date' => wibToday(),
        'status' => 'skipped',
    ]);

    expect(RoutineEntry::first()->status)->toBe('skipped');
});

it('clears the entry when the status is pending', function () {
    $user = User::factory()->create();
    $routine = Routine::factory()->for($user)->create();

    RoutineEntry::factory()->for($routine)->create(['check_date' => wibToday()]);

    $this->actingAs($user)->patch(route('checklist.check', $routine), [
        'date' => wibToday(),
        'status' => 'pending',
    ]);

    expect(RoutineEntry::count())->toBe(0);
});

it('allows checking yesterday', function () {
    $user = User::factory()->create();
    $routine = Routine::factory()->for($user)->create(['starts_on' => '2026-01-01']);

    $yesterday = CarbonImmutable::parse(wibToday(), config('tasks.display_timezone'))
        ->subDay()
        ->toDateString();

    $this->actingAs($user)
        ->patch(route('checklist.check', $routine), [
            'date' => $yesterday,
            'status' => 'done',
        ])
        ->assertRedirect();

    expect(RoutineEntry::count())->toBe(1);
});

it('rejects dates that are not today or yesterday', function (string $offset) {
    $user = User::factory()->create();
    $routine = Routine::factory()->for($user)->create(['starts_on' => '2026-01-01']);

    $date = CarbonImmutable::parse(wibToday(), config('tasks.display_timezone'))
        ->addDays((int) $offset)
        ->toDateString();

    $this->actingAs($user)
        ->patch(route('checklist.check', $routine), [
            'date' => $date,
            'status' => 'done',
        ])
        ->assertStatus(422);

    expect(RoutineEntry::count())->toBe(0);
})->with([
    'tomorrow' => ['1'],
    'two days ago' => ['-2'],
]);

it('rejects an archived routine', function () {
    $user = User::factory()->create();
    $routine = Routine::factory()->for($user)->archived()->create();

    $this->actingAs($user)
        ->patch(route('checklist.check', $routine), [
            'date' => wibToday(),
            'status' => 'done',
        ])
        ->assertStatus(422);
});

it('forbids checking a routine owned by someone else', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $routine = Routine::factory()->for($owner)->create();

    $this->actingAs($other)
        ->patch(route('checklist.check', $routine), [
            'date' => wibToday(),
            'status' => 'done',
        ])
        ->assertForbidden();

    expect(RoutineEntry::count())->toBe(0);
});

it('validates the status value', function () {
    $user = User::factory()->create();
    $routine = Routine::factory()->for($user)->create();

    $this->actingAs($user)
        ->patch(route('checklist.check', $routine), [
            'date' => wibToday(),
            'status' => 'nope',
        ])
        ->assertSessionHasErrors('status');
});
