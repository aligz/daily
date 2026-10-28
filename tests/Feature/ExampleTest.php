<?php

use App\Models\User;

test('redirects guests to the feature request board', function () {
    $this->get(route('home'))
        ->assertRedirect(route('feature-requests.index'));
});

test('redirects to tasks index for authenticated user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertRedirect(route('tasks.index'));
});
