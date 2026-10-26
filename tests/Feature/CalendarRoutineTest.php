<?php

use App\Models\Routine;
use App\Models\RoutineEntry;
use App\Models\RoutineNote;
use App\Models\User;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia;

function calendarToday(): string
{
    return CarbonImmutable::now(config('tasks.display_timezone'))->toDateString();
}

it('sends the signed-in user checklist to the calendar', function () {
    $user = User::factory()->create();
    $routine = Routine::factory()->for($user)->create([
        'title' => 'Review pull request',
        'starts_on' => '2026-10-01',
    ]);

    RoutineEntry::factory()->for($routine)->create([
        'check_date' => calendarToday(),
        'status' => 'done',
    ]);

    $this->actingAs($user)
        ->get(route('tasks.calendar', ['month' => '2026-10']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('tasks/Calendar')
            ->where('checklist.owner.id', $user->id)
            ->where('checklist.read_only', false)
            ->where('checklist.date', calendarToday())
            ->has('checklist.items', 1)
            ->where('checklist.items.0.title', 'Review pull request')
            ->where('checklist.items.0.status', 'done')
            ->where('checklist.stats.total', 1)
            ->where('checklist.stats.done', 1)
            ->where('checklist.stats.percent', 100)
            ->where('checklist.note', null));
});

it('includes the daily note', function () {
    $user = User::factory()->create();
    RoutineNote::create([
        'user_id' => $user->id,
        'note_date' => calendarToday(),
        'body' => 'Deploy staging kena timeout.',
    ]);

    $this->actingAs($user)
        ->get(route('tasks.calendar'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('checklist.note.body', 'Deploy staging kena timeout.'));
});

it('keeps the developer checklist personal when filtering another user', function () {
    $developer = User::factory()->create();
    $other = User::factory()->create();

    Routine::factory()->for($developer)->create(['title' => 'Mine']);
    Routine::factory()->for($other)->create(['title' => 'Theirs']);

    $this->actingAs($developer)
        ->get(route('tasks.calendar', ['user_id' => $other->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('checklist.owner.id', $developer->id)
            ->where('checklist.read_only', false)
            ->has('checklist.items', 1)
            ->where('checklist.items.0.title', 'Mine'));
});

it('lets a head read a developer checklist read-only', function () {
    $head = User::factory()->head()->create();
    $developer = User::factory()->create();

    Routine::factory()->for($developer)->create(['title' => 'Deploy staging']);

    $this->actingAs($head)
        ->get(route('tasks.calendar', ['user_id' => $developer->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('checklist.owner.id', $developer->id)
            ->where('checklist.read_only', true)
            ->has('checklist.items', 1)
            ->where('checklist.items.0.title', 'Deploy staging'));
});

it('excludes archived routines from today items but keeps them listed', function () {
    $user = User::factory()->create();

    Routine::factory()->for($user)->create(['title' => 'Active']);
    Routine::factory()->for($user)->archived()->create(['title' => 'Old']);

    $this->actingAs($user)
        ->get(route('tasks.calendar'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('checklist.items', 1)
            ->where('checklist.items.0.title', 'Active')
            ->has('checklist.archived', 1)
            ->where('checklist.archived.0.title', 'Old'));
});

it('sends per-date routine progress for the grid and panel', function () {
    $user = User::factory()->create();
    $routine = Routine::factory()->for($user)->create([
        'title' => 'Review pull request',
        'starts_on' => '2026-10-01',
    ]);

    RoutineEntry::factory()->for($routine)->create([
        'check_date' => '2026-10-20',
        'status' => 'done',
    ]);
    RoutineEntry::factory()->for($routine)->create([
        'check_date' => '2026-10-21',
        'status' => 'skipped',
    ]);

    RoutineNote::create([
        'user_id' => $user->id,
        'note_date' => '2026-10-21',
        'body' => 'Blocked on staging.',
    ]);

    $this->actingAs($user)
        ->get(route('tasks.calendar', ['month' => '2026-10']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('checklist_by_date.2026-10-20.total', 1)
            ->where('checklist_by_date.2026-10-20.done', 1)
            ->where('checklist_by_date.2026-10-21.skipped', 1)
            ->where('checklist_by_date.2026-10-21.note', 'Blocked on staging.')
            ->has('checklist_by_date.2026-10-20.items', 1)
            ->where('checklist_by_date.2026-10-20.items.0.title', 'Review pull request')
            ->missing('checklist_by_date.2026-09-29'));
});

it('sends team progress only to heads', function () {
    $head = User::factory()->head()->create();
    $developer = User::factory()->create();

    Routine::factory()->for($developer)->create();
    Routine::factory()->for($head)->create();

    $this->actingAs($head)
        ->get(route('tasks.calendar'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('checklist_team', 1)
            ->where('checklist_team.0.id', $developer->id)
            ->where('checklist_team.0.total', 1)
            ->where('checklist_team.0.done', 0));

    $this->actingAs($developer)
        ->get(route('tasks.calendar'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('checklist_team', 0));
});
