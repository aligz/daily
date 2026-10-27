<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia;

it('shows the guide to signed-in users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('guide'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Guide')
        );
});

it('sends guests to the login page', function () {
    $this->get(route('guide'))->assertRedirect(route('login'));
});
