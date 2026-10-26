<?php

use App\Models\Routine;
use App\Models\RoutineEntry;
use App\Models\RoutineNote;
use App\Models\User;
use Illuminate\Database\QueryException;

it('is active from starts_on until the day before archived_on', function (string $date, bool $expected) {
    $routine = Routine::factory()->create([
        'starts_on' => '2026-10-05',
        'archived_on' => '2026-10-20',
    ]);

    expect($routine->isActiveOn($date))->toBe($expected);
})->with([
    'before start' => ['2026-10-04', false],
    'start day' => ['2026-10-05', true],
    'middle' => ['2026-10-12', true],
    'day before archive' => ['2026-10-19', true],
    'archive day' => ['2026-10-20', false],
    'after archive' => ['2026-10-21', false],
]);

it('stays active forever when it was never archived', function () {
    $routine = Routine::factory()->create([
        'starts_on' => '2026-10-05',
        'archived_on' => null,
    ]);

    expect($routine->isActiveOn('2026-10-05'))->toBeTrue()
        ->and($routine->isActiveOn('2030-01-01'))->toBeTrue();
});

it('scopes routines that are active on a given date', function () {
    $user = User::factory()->create();

    $future = Routine::factory()->for($user)->create(['starts_on' => '2026-10-20']);
    $active = Routine::factory()->for($user)->create(['starts_on' => '2026-10-01']);
    $archived = Routine::factory()->for($user)->create([
        'starts_on' => '2026-10-01',
        'archived_on' => '2026-10-15',
    ]);

    $ids = Routine::query()->activeOn('2026-10-15')->pluck('id');

    expect($ids)->toHaveCount(1)
        ->and($ids->first())->toBe($active->id)
        ->and($ids)->not->toContain($future->id)
        ->and($ids)->not->toContain($archived->id);
});

it('keeps a single entry per routine per date', function () {
    $entry = RoutineEntry::factory()->create(['check_date' => '2026-10-26']);

    expect(fn () => RoutineEntry::factory()->create([
        'routine_id' => $entry->routine_id,
        'check_date' => '2026-10-26',
    ]))->toThrow(QueryException::class);
});

it('cascades routines, entries and notes when the user is deleted', function () {
    $user = User::factory()->create();
    $routine = Routine::factory()->for($user)->create();

    RoutineEntry::factory()->for($routine)->create();
    RoutineNote::create([
        'user_id' => $user->id,
        'note_date' => '2026-10-26',
        'body' => 'Deploy staging kena timeout.',
    ]);

    $user->delete();

    expect(Routine::count())->toBe(0)
        ->and(RoutineEntry::count())->toBe(0)
        ->and(RoutineNote::count())->toBe(0);
});
