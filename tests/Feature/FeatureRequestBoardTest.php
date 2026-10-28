<?php

use App\Models\Division;
use App\Models\FeatureRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

function featureRequestFor(Division $division, ?User $user = null, array $overrides = []): FeatureRequest
{
    return FeatureRequest::create(array_merge([
        'user_id' => $user?->id,
        'division_id' => $division->id,
        'requester_name' => 'Rina',
        'title' => 'Approve in one screen',
        'description' => 'Approvals live on three screens.',
        'change_reason' => 'It wastes a day per request.',
        'proposed_action' => 'Merge the steps into one screen.',
        'effect_on_organization' => 'Finance saves a day per request.',
        'effect_of_not_approving' => 'Requests miss the weekly cutoff.',
        'priority' => 'medium',
        'status' => 'new',
        'deadline' => now()->addWeek()->toDateString(),
    ], $overrides));
}

test('guests can read the board without seeing reporter emails', function () {
    $division = Division::create(['name' => 'Marketing']);
    featureRequestFor($division, User::factory()->create());

    $this->get(route('feature-requests.index'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('feature-requests/Index')
                ->has('featureRequests', 1)
                ->has('divisions', 1)
                ->missing('featureRequests.0.user.email')
        );
});

test('guests can open a request without reporter emails', function () {
    $division = Division::create(['name' => 'Marketing']);
    $featureRequest = featureRequestFor($division, User::factory()->create());

    $this->get(route('feature-requests.show', $featureRequest))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('feature-requests/Show')
                ->where('isPublic', false)
                ->missing('featureRequest.user.email')
        );
});

test('a request without a reporter is marked public', function () {
    $division = Division::create(['name' => 'Marketing']);
    $featureRequest = featureRequestFor($division, null, [
        'priority' => null,
        'deadline' => null,
    ]);

    $this->get(route('feature-requests.show', $featureRequest))
        ->assertInertia(
            fn ($page) => $page
                ->where('isPublic', true)
                ->where('featureRequest.user', null)
                ->where('featureRequest.priority', null)
                ->where('featureRequest.deadline', null)
        );
});

test('guests cannot change a feature request', function () {
    $featureRequest = featureRequestFor(Division::create(['name' => 'Marketing']));

    $this->put(route('feature-requests.update', $featureRequest), ['status' => 'planning'])
        ->assertRedirect(route('login'));

    $this->delete(route('feature-requests.destroy', $featureRequest))
        ->assertRedirect(route('login'));

    assertDatabaseHas('feature_requests', [
        'id' => $featureRequest->id,
        'status' => 'new',
    ]);
});

test('triage can set priority, deadline, status, and notes', function () {
    $user = User::factory()->create();
    $featureRequest = featureRequestFor(Division::create(['name' => 'Marketing']), null, [
        'priority' => null,
        'deadline' => null,
    ]);
    $deadline = now()->addMonth()->toDateString();

    actingAs($user)->put(route('feature-requests.update', $featureRequest), [
        'status' => 'planning',
        'priority' => 'high',
        'deadline' => $deadline,
        'notes' => 'Worth doing next sprint.',
    ])->assertRedirect();

    assertDatabaseHas('feature_requests', [
        'id' => $featureRequest->id,
        'status' => 'planning',
        'priority' => 'high',
        'deadline' => $deadline,
        'notes' => 'Worth doing next sprint.',
    ]);
});

test('a partial status update leaves triage values alone', function () {
    $user = User::factory()->create();
    $featureRequest = featureRequestFor(Division::create(['name' => 'Marketing']), $user);

    actingAs($user)->put(route('feature-requests.update', $featureRequest), [
        'status' => 'development',
        'notes' => 'In progress.',
    ])->assertRedirect();

    assertDatabaseHas('feature_requests', [
        'id' => $featureRequest->id,
        'status' => 'development',
        'priority' => 'medium',
        'deadline' => $featureRequest->deadline->toDateString(),
    ]);
});
