<?php

use HasinHayder\TyroLogin\Tests\Fixtures\User;
use Illuminate\Support\Facades\Hash;

it('shows the login form', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
    $response->assertSee('Sign in to your account');
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

    $response->assertRedirect(config('tyro-login.redirects.after_login', '/dashboard'));
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

it('can logout', function () {
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
