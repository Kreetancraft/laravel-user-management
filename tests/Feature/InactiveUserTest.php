<?php

use Illuminate\Support\Facades\DB;
use Kreetancraft\UserManagement\Models\User;

beforeEach(function () {
    seedRolesAndPermissions();
});

test('active user can log in', function () {
    $user = User::factory()->create([
        'password' => bcrypt('correct-password'),
        'is_active' => true,
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);

    $response->assertRedirect();
    $this->assertAuthenticatedAs($user);
});

test('deactivated user cannot log in and gets validation error', function () {
    $user = User::factory()->create([
        'password' => bcrypt('correct-password'),
        'is_active' => false,
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);

    $response->assertSessionHasErrors([
        'email' => __('Your account has been deactivated. Please contact an administrator.'),
    ]);
    $this->assertGuest();
});

test('deactivating a user immediately deletes their sessions', function () {
    $user = User::factory()->create([
        'is_active' => true,
    ]);

    // Create a mock active session in the database sessions table
    DB::table('sessions')->insert([
        'id' => 'mock-session-id',
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0',
        'payload' => base64_encode('session-payload'),
        'last_activity' => time(),
    ]);

    $this->assertDatabaseHas('sessions', [
        'user_id' => $user->id,
    ]);

    // Deactivate the user
    $user->is_active = false;
    $user->save();

    // Verify session row is gone
    $this->assertDatabaseMissing('sessions', [
        'user_id' => $user->id,
    ]);
});
