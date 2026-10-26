<?php

// Registration is intentionally disabled in config/fortify.php, so the
// register routes do not exist. Keep the intent documented instead of
// deleting the coverage entirely.
beforeEach(function () {
    $this->markTestSkipped('Registration is disabled in config/fortify.php.');
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});
