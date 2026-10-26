<?php

use App\Models\RoutineNote;
use App\Models\User;
use Carbon\CarbonImmutable;

function noteToday(): string
{
    return CarbonImmutable::now(config('tasks.display_timezone'))->toDateString();
}

it('saves a note for today', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('checklist.note.update'), ['body' => '  Deploy staging kena timeout.  '])
        ->assertRedirect();

    expect(RoutineNote::count())->toBe(1)
        ->and(RoutineNote::first()->body)->toBe('Deploy staging kena timeout.')
        ->and(RoutineNote::first()->note_date->toDateString())->toBe(noteToday());
});

it('updates the existing note instead of creating another row', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('checklist.note.update'), ['body' => 'First']);
    $this->actingAs($user)->put(route('checklist.note.update'), ['body' => 'Second']);

    expect(RoutineNote::count())->toBe(1)
        ->and(RoutineNote::first()->body)->toBe('Second');
});

it('deletes the note when the body is empty', function () {
    $user = User::factory()->create();
    RoutineNote::create([
        'user_id' => $user->id,
        'note_date' => noteToday(),
        'body' => 'Temporary',
    ]);

    $this->actingAs($user)
        ->put(route('checklist.note.update'), ['body' => '   '])
        ->assertRedirect();

    expect(RoutineNote::count())->toBe(0);
});

it('rejects a note longer than the configured limit', function () {
    config(['checklist.note_max_length' => 10]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('checklist.note.update'), ['body' => str_repeat('a', 11)])
        ->assertSessionHasErrors('body');

    expect(RoutineNote::count())->toBe(0);
});

it('keeps notes private between users', function () {
    $first = User::factory()->create();
    $second = User::factory()->create();

    $this->actingAs($first)->put(route('checklist.note.update'), ['body' => 'Mine']);
    $this->actingAs($second)->put(route('checklist.note.update'), ['body' => 'Yours']);

    expect(RoutineNote::count())->toBe(2)
        ->and(RoutineNote::query()->where('user_id', $first->id)->value('body'))->toBe('Mine')
        ->and(RoutineNote::query()->where('user_id', $second->id)->value('body'))->toBe('Yours');
});
