<?php

use App\Models\Division;
use App\Models\FeatureRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

function publicRequestPayload(Division $division, array $overrides = []): array
{
    return array_merge([
        'division_id' => $division->id,
        'requester_name' => 'Rina',
        'requester_email' => 'rina@example.com',
        'requester_phone' => '0812',
        'title' => 'Move approval to one screen',
        'description' => 'Approvals are split across three screens today.',
        'change_reason' => 'The current flow wastes a day per request.',
        'proposed_action' => 'Merge the three approval steps into one screen.',
        'effect_on_organization' => 'Finance and operations both save a day.',
        'effect_of_not_approving' => 'Requests keep missing the weekly cutoff.',
    ], $overrides);
}

test('guests can open the create form', function () {
    Division::create(['name' => 'Marketing']);

    $this->get(route('feature-requests.create'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('feature-requests/Create')
                ->has('divisions', 1)
        );
});

test('a guest can submit a change request', function () {
    $division = Division::create(['name' => 'Marketing']);

    $this->post(route('feature-requests.store'), publicRequestPayload($division))
        ->assertRedirect();

    assertDatabaseHas('feature_requests', [
        'division_id' => $division->id,
        'user_id' => null,
        'status' => 'new',
        'priority' => null,
        'deadline' => null,
        'title' => 'Move approval to one screen',
        'description' => 'Approvals are split across three screens today.',
        'change_reason' => 'The current flow wastes a day per request.',
        'proposed_action' => 'Merge the three approval steps into one screen.',
        'effect_on_organization' => 'Finance and operations both save a day.',
        'effect_of_not_approving' => 'Requests keep missing the weekly cutoff.',
    ]);
});

test('a signed-in user is recorded as the reporter and sets triage fields', function () {
    $division = Division::create(['name' => 'Marketing']);
    $user = User::factory()->create();

    actingAs($user)->post(route('feature-requests.store'), publicRequestPayload($division, [
        'priority' => 'high',
        'deadline' => now()->addWeek()->toDateString(),
    ]))->assertRedirect();

    assertDatabaseHas('feature_requests', [
        'user_id' => $user->id,
        'priority' => 'high',
        'deadline' => now()->addWeek()->toDateString(),
    ]);
});

test('triage values injected by a guest are ignored', function () {
    $division = Division::create(['name' => 'Marketing']);

    $this->post(route('feature-requests.store'), publicRequestPayload($division, [
        'priority' => 'urgent',
        'deadline' => now()->addWeek()->toDateString(),
    ]))->assertRedirect();

    assertDatabaseHas('feature_requests', [
        'priority' => null,
        'deadline' => null,
    ]);
});

test('a submitted request redirects to its detail page', function () {
    $division = Division::create(['name' => 'Marketing']);

    $response = $this->post(route('feature-requests.store'), publicRequestPayload($division));

    $featureRequest = FeatureRequest::firstOrFail();

    $response->assertRedirect(route('feature-requests.show', $featureRequest->id));
});

test('every required field is enforced', function (string $field) {
    $division = Division::create(['name' => 'Marketing']);
    $payload = publicRequestPayload($division);
    unset($payload[$field]);

    $this->post(route('feature-requests.store'), $payload)
        ->assertSessionHasErrors($field);

    assertDatabaseCount('feature_requests', 0);
})->with([
    'requester_name',
    'title',
    'description',
    'division_id',
    'change_reason',
    'proposed_action',
    'effect_on_organization',
    'effect_of_not_approving',
]);

test('the division must exist', function () {
    $this->post(route('feature-requests.store'), publicRequestPayload(
        Division::create(['name' => 'Marketing']),
        ['division_id' => 9999],
    ))->assertSessionHasErrors('division_id');

    assertDatabaseCount('feature_requests', 0);
});
