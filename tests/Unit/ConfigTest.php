<?php

it('has the correct default configuration', function () {
    expect(config('tyro-login.version'))->toBe('1.0.0');
    expect(config('tyro-login.layout'))->toBe('centered');
    expect(config('tyro-login.registration.enabled'))->toBeTrue();
    expect(config('tyro-login.registration.auto_login'))->toBeTrue();
    expect(config('tyro-login.features.remember_me'))->toBeTrue();
    expect(config('tyro-login.features.forgot_password'))->toBeTrue();
    expect(config('tyro-login.password.min_length'))->toBe(8);
    expect(config('tyro-login.rate_limiting.enabled'))->toBeTrue();
    expect(config('tyro-login.rate_limiting.max_attempts'))->toBe(5);
});

it('has correct redirect defaults', function () {
    expect(config('tyro-login.redirects.after_login'))->toBe('/');
    expect(config('tyro-login.redirects.after_logout'))->toBe('/login');
    expect(config('tyro-login.redirects.after_register'))->toBe('/');
});

it('has correct tyro integration defaults', function () {
    expect(config('tyro-login.tyro.assign_default_role'))->toBeTrue();
    expect(config('tyro-login.tyro.default_role_slug'))->toBe('user');
});

it('has correct lockout defaults', function () {
    expect(config('tyro-login.lockout.enabled'))->toBeTrue();
    expect(config('tyro-login.lockout.max_attempts'))->toBe(5);
    expect(config('tyro-login.lockout.duration_minutes'))->toBe(15);
    expect(config('tyro-login.lockout.message'))->toContain(':minutes');
    expect(config('tyro-login.lockout.title'))->toBe('Account Temporarily Locked');
});
