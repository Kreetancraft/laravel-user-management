<?php

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Kreetancraft\UserManagement\Listeners\RecordUserLogin;
use Kreetancraft\UserManagement\Models\User;

test('a successful login stamps last_login_at and last_login_ip on the user', function () {
    $user = User::factory()->create([
        'password' => bcrypt('correct-password'),
        'last_login_at' => null,
        'last_login_ip' => null,
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);

    $response->assertRedirect();

    $user->refresh();
    expect($user->last_login_at)->not->toBeNull()
        ->and($user->last_login_ip)->not->toBeNull();
});

test('a failed login does not touch last_login_at', function () {
    $user = User::factory()->create([
        'password' => bcrypt('correct-password'),
        'last_login_at' => null,
        'last_login_ip' => null,
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    expect($user->fresh()->last_login_at)->toBeNull()
        ->and($user->fresh()->last_login_ip)->toBeNull();
});

test('the listener is registered on the Login event', function () {
    Event::fake();

    Event::assertListening(
        Login::class,
        RecordUserLogin::class,
    );
});
