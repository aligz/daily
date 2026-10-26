<?php

use App\Enums\UserRole;
use App\Models\User;

it('defaults new users to developer', function () {
    $user = User::factory()->create();

    expect($user->role)->toBe(UserRole::Developer)
        ->and($user->isHead())->toBeFalse();
});

it('casts the role column to the enum', function () {
    $user = User::factory()->head()->create();

    expect($user->role)->toBe(UserRole::Head)
        ->and($user->isHead())->toBeTrue();
});

it('does not allow mass assignment of the role', function () {
    $user = User::create([
        'name' => 'Aji',
        'email' => 'aji@example.test',
        'password' => 'password',
        'role' => 'head',
    ]);

    expect($user->fresh()->role)->toBe(UserRole::Developer);
});

it('changes the role through an explicit query builder update', function () {
    $user = User::factory()->create();

    User::query()->whereKey($user->id)->update(['role' => 'head']);

    expect($user->fresh()->role)->toBe(UserRole::Head)
        ->and($user->fresh()->isHead())->toBeTrue();
});
