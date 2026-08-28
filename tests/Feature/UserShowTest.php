<?php

use Kreetancraft\UserManagement\Livewire\ShowUser;
use Kreetancraft\UserManagement\Models\User;
use Kreetancraft\UserManagement\Models\UserLoginHistory;
use Livewire\Livewire;
use Torann\GeoIP\Facades\GeoIP;
use Torann\GeoIP\Location;

beforeEach(function () {
    seedRolesAndPermissions();
});

test('guest is redirected to login', function () {
    $user = User::factory()->create();
    $this->get(route(config('user-management.routes.names.users.show', 'admin.users.show'), $user))->assertRedirect(route(config('user-management.routes.names.login', 'login')));
});

test('user without view-users permission is forbidden', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();
    $this->actingAs($user);

    $this->get(route(config('user-management.routes.names.users.show', 'admin.users.show'), $target))->assertForbidden();
});

test('super admin can view user details', function () {
    actingAsSuperAdmin();
    $target = User::factory()->create();

    $this->get(route(config('user-management.routes.names.users.show', 'admin.users.show'), $target))
        ->assertOk()
        ->assertSeeLivewire(ShowUser::class);
});

test('login resolves location via GeoIP and saves to history', function () {
    GeoIP::shouldReceive('getLocation')
        ->once()
        ->with('1.2.3.4')
        ->andReturn(new Location([
            'ip' => '1.2.3.4',
            'iso_code' => 'JP',
            'country' => 'Japan',
            'city' => 'Tokyo',
            'state' => '13',
            'state_name' => 'Tokyo',
            'postal_code' => '100-0001',
            'lat' => 35.6762,
            'lon' => 139.6503,
            'timezone' => 'Asia/Tokyo',
            'continent' => 'AS',
            'currency' => 'JPY',
            'default' => false,
        ]));

    $user = User::factory()->create([
        'password' => bcrypt('password'),
    ]);

    // Force remote address to 1.2.3.4
    $this->call('POST', '/login', [
        'email' => $user->email,
        'password' => 'password',
    ], [], [], ['REMOTE_ADDR' => '1.2.3.4']);

    $this->assertDatabaseHas('user_login_histories', [
        'user_id' => $user->id,
        'ip_address' => '1.2.3.4',
        'city' => 'Tokyo',
        'country' => 'Japan',
        'country_code' => 'JP',
    ]);
});

test('user show component renders history table', function () {
    actingAsSuperAdmin();

    $target = User::factory()->create();
    UserLoginHistory::create([
        'user_id' => $target->id,
        'ip_address' => '8.8.8.8',
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'city' => 'Mountain View',
        'state' => 'CA',
        'country' => 'United States',
        'country_code' => 'US',
    ]);

    Livewire::test(ShowUser::class, ['user' => $target])
        ->assertSee('8.8.8.8')
        ->assertSee('Mountain View, CA, United States')
        ->assertSee('Chrome')
        ->assertSee('Windows');
});
