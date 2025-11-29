<?php

use HasinHayder\TyroLogin\Tests\Fixtures\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

it('shows the login form', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
    $response->assertSee('Log in to your account');
});

it('can login with valid credentials', function () {
    $user = User::forceCreate([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirect(config('tyro-login.redirects.after_login', '/'));
    $this->assertAuthenticated();
});

it('fails login with invalid credentials', function () {
    User::forceCreate([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('validates required fields on login', function () {
    $response = $this->post('/login', []);

    $response->assertSessionHasErrors(['email', 'password']);
});

it('can logout via POST', function () {
    $user = User::forceCreate([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user);

    $response = $this->post('/logout');

    $response->assertRedirect(config('tyro-login.redirects.after_logout', '/login'));
    $this->assertGuest();
});

it('can logout via GET', function () {
    $user = User::forceCreate([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user);

    $response = $this->get('/logout');

    $response->assertRedirect(config('tyro-login.redirects.after_logout', '/login'));
    $this->assertGuest();
});

it('remembers user when remember me is checked', function () {
    $user = User::forceCreate([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password',
        'remember' => true,
    ]);

    $response->assertRedirect();
    $this->assertAuthenticated();
    
    // Check that the remember token was set
    $user->refresh();
    expect($user->remember_token)->not->toBeNull();
});

it('locks out user after max failed attempts', function () {
    config(['tyro-login.lockout.enabled' => true]);
    config(['tyro-login.lockout.max_attempts' => 3]);
    config(['tyro-login.lockout.duration_minutes' => 15]);

    User::forceCreate([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    // Fail login 3 times
    for ($i = 0; $i < 3; $i++) {
        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);
    }

    // Next attempt should redirect to lockout
    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertRedirect(route('tyro-login.lockout'));
});

it('shows lockout page when locked out', function () {
    // Manually set lockout in cache
    $releaseTime = now()->addMinutes(15)->timestamp;
    Cache::put('tyro-login:lockout:127.0.0.1', $releaseTime, now()->addMinutes(15));

    $response = $this->get('/lockout');

    $response->assertStatus(200);
    $response->assertSee('Account Temporarily Locked');
});

it('redirects to login from lockout when lockout expires', function () {
    // Set an expired lockout
    $releaseTime = now()->subMinutes(1)->timestamp;
    Cache::put('tyro-login:lockout:127.0.0.1', $releaseTime, now()->addMinutes(15));

    $response = $this->get('/lockout');

    $response->assertRedirect(route('tyro-login.login'));
});

it('redirects to lockout from login when locked out', function () {
    // Set an active lockout
    $releaseTime = now()->addMinutes(15)->timestamp;
    Cache::put('tyro-login:lockout:127.0.0.1', $releaseTime, now()->addMinutes(15));

    $response = $this->get('/login');

    $response->assertRedirect(route('tyro-login.lockout'));
});

it('clears lockout on successful login', function () {
    config(['tyro-login.lockout.enabled' => true]);

    $user = User::forceCreate([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    // Set some failed attempts
    Cache::put('tyro-login:lockout-attempts:127.0.0.1', 2, now()->addMinutes(20));

    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirect('/');
    $this->assertAuthenticated();
    
    // Attempts should be cleared
    expect(Cache::get('tyro-login:lockout-attempts:127.0.0.1'))->toBeNull();
});
