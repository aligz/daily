<?php

use App\Models\Routine;
use App\Models\RoutineEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia;

function todayWib(): string
{
    return CarbonImmutable::now(config('tasks.display_timezone'))->toDateString();
}

it('creates a routine that starts today', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('routines.store'), ['title' => '  Review pull request  '])
        ->assertRedirect();

    $routine = Routine::query()->first();

    expect($routine)->not->toBeNull()
        ->and($routine->user_id)->toBe($user->id)
        ->and($routine->title)->toBe('Review pull request')
        ->and($routine->starts_on->toDateString())->toBe(todayWib())
        ->and($routine->archived_on)->toBeNull()
        ->and($routine->position)->toBe(1.0);
});

it('requires a title when creating a routine', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('routines.store'), ['title' => ''])
        ->assertSessionHasErrors('title');

    expect(Routine::count())->toBe(0);
});

it('stops creating routines at the configured limit', function () {
    config(['checklist.max_routines' => 2]);

    $user = User::factory()->create();
    Routine::factory()->count(2)->for($user)->create();

    $this->actingAs($user)
        ->post(route('routines.store'), ['title' => 'One too many'])
        ->assertSessionHasErrors('title');

    expect(Routine::count())->toBe(2);
});

it('updates the title of an own routine', function () {
    $user = User::factory()->create();
    $routine = Routine::factory()->for($user)->create(['title' => 'Old title']);

    $this->actingAs($user)
        ->patch(route('routines.update', $routine), ['title' => 'New title'])
        ->assertRedirect();

    expect($routine->fresh()->title)->toBe('New title');
});

it('forbids updating a routine owned by someone else', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $routine = Routine::factory()->for($owner)->create();

    $this->actingAs($other)
        ->patch(route('routines.update', $routine), ['title' => 'Hijacked'])
        ->assertForbidden();

    expect($routine->fresh()->title)->not->toBe('Hijacked');
});

it('archives a routine starting today so it disappears immediately', function () {
    $user = User::factory()->create();
    $routine = Routine::factory()->for($user)->create(['starts_on' => '2026-10-01']);

    $this->actingAs($user)
        ->delete(route('routines.destroy', $routine))
        ->assertRedirect();

    $routine->refresh();

    expect($routine->archived_on?->toDateString())->toBe(todayWib())
        ->and($routine->isActiveOn(todayWib()))->toBeFalse()
        ->and($routine->isActiveOn('2026-10-02'))->toBeTrue();
});

it('does not archive the same routine twice', function () {
    $user = User::factory()->create();
    $routine = Routine::factory()->for($user)->archived()->create();

    $this->actingAs($user)
        ->delete(route('routines.destroy', $routine))
        ->assertNotFound();
});

it('forbids archiving a routine owned by someone else', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $routine = Routine::factory()->for($owner)->create();

    $this->actingAs($other)
        ->delete(route('routines.destroy', $routine))
        ->assertForbidden();

    expect($routine->fresh()->archived_on)->toBeNull();
});

it('redirects guests to the login page', function () {
    $this->post(route('routines.store'), ['title' => 'Nope'])
        ->assertRedirect(route('login'));
});

it('moves a routine up and down', function () {
    $user = User::factory()->create();
    $first = Routine::factory()->for($user)->create(['title' => 'First', 'position' => 1]);
    $second = Routine::factory()->for($user)->create(['title' => 'Second', 'position' => 2]);

    $this->actingAs($user)
        ->patch(route('routines.move', $second), ['direction' => 'up'])
        ->assertRedirect();

    expect($second->fresh()->position)->toBe(1.0)
        ->and($first->fresh()->position)->toBe(2.0);

    $this->actingAs($user)
        ->patch(route('routines.move', $second), ['direction' => 'down'])
        ->assertRedirect();

    expect($second->fresh()->position)->toBe(2.0)
        ->and($first->fresh()->position)->toBe(1.0);
});

it('ignores moving the first routine further up', function () {
    $user = User::factory()->create();
    $first = Routine::factory()->for($user)->create(['position' => 1]);
    Routine::factory()->for($user)->create(['position' => 2]);

    $this->actingAs($user)
        ->patch(route('routines.move', $first), ['direction' => 'up'])
        ->assertRedirect();

    expect($first->fresh()->position)->toBe(1.0);
});

it('exposes today routine progress to the board', function () {
    $user = User::factory()->create();
    $routine = Routine::factory()->for($user)->create();

    RoutineEntry::factory()->for($routine)->create([
        'check_date' => todayWib(),
        'status' => 'done',
    ]);

    $this->actingAs($user)
        ->get(route('tasks.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('routine.total', 1)
            ->where('routine.done', 1)
            ->where('routine.skipped', 0));
});
