<?php

use HasinHayder\TyroLogin\Tests\Fixtures\User;
use Illuminate\Support\Facades\Hash;

it('shows the registration form', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
    $response->assertSee('Create an account');
});

it('can register a new user', function () {
    $response = $this->post('/register', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect(config('tyro-login.redirects.after_register', '/'));
    
    $this->assertDatabaseHas('users', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
    ]);
    
    // Should be logged in after registration (auto_login is true by default)
    $this->assertAuthenticated();
});

it('validates required fields on registration', function () {
    $response = $this->post('/register', []);

    $response->assertSessionHasErrors(['name', 'email', 'password']);
});

it('validates email is unique', function () {
    User::forceCreate([
        'name' => 'Existing User',
        'email' => 'existing@example.com',
        'password' => Hash::make('password'),
    ]);

    $response = $this->post('/register', [
        'name' => 'New User',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors('email');
});

it('validates password confirmation matches', function () {
    $response = $this->post('/register', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'password123',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertSessionHasErrors('password');
});

it('validates minimum password length', function () {
    $response = $this->post('/register', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
    ]);

    $response->assertSessionHasErrors('password');
});

it('hashes the password on registration', function () {
    $this->post('/register', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $user = User::where('email', 'newuser@example.com')->first();
    
    expect($user->password)->not->toBe('password123');
    expect(Hash::check('password123', $user->password))->toBeTrue();
});

it('validates password maximum length when configured', function () {
    config(['tyro-login.password.max_length' => 10]);

    $response = $this->post('/register', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'verylongpassword',
        'password_confirmation' => 'verylongpassword',
    ]);

    $response->assertSessionHasErrors('password');
});

it('validates password requires uppercase when configured', function () {
    config(['tyro-login.password.complexity.require_uppercase' => true]);

    $response = $this->post('/register', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'lowercase123',
        'password_confirmation' => 'lowercase123',
    ]);

    $response->assertSessionHasErrors('password');
});

it('validates password requires numbers when configured', function () {
    config(['tyro-login.password.complexity.require_numbers' => true]);

    $response = $this->post('/register', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'nonumbers',
        'password_confirmation' => 'nonumbers',
    ]);

    $response->assertSessionHasErrors('password');
});

it('validates password requires special characters when configured', function () {
    config(['tyro-login.password.complexity.require_special_chars' => true]);

    $response = $this->post('/register', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'nospecial123',
        'password_confirmation' => 'nospecial123',
    ]);

    $response->assertSessionHasErrors('password');
});


it('validates against common passwords when configured', function () {
    config(['tyro-login.password.check_common_passwords' => true]);

    $response = $this->post('/register', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('password');
});

it('validates password contains user info when configured', function () {
    config(['tyro-login.password.disallow_user_info' => true]);

    $response = $this->post('/register', [
        'name' => 'John Doe',
        'email' => 'johndoe@example.com',
        'password' => 'johndoe123',
        'password_confirmation' => 'johndoe123',
    ]);

    $response->assertSessionHasErrors('password');
});

it('validates password contains email username when configured', function () {
    config(['tyro-login.password.disallow_user_info' => true]);

    $response = $this->post('/register', [
        'name' => 'Jane Smith',
        'email' => 'janesmith@example.com',
        'password' => 'janesmith123',
        'password_confirmation' => 'janesmith123',
    ]);

    $response->assertSessionHasErrors('password');
});

it('redirects to login when registration is disabled', function () {
    config(['tyro-login.registration.enabled' => false]);

    $response = $this->get('/register');

    $response->assertRedirect(route('tyro-login.login'));
});
