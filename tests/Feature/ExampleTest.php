<?php

use App\Models\User;

test('returns a successful response for guest', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

test('redirects to tasks index for authenticated user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertRedirect(route('tasks.index'));
});
