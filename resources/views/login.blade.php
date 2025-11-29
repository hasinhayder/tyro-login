@extends('tyro-login::layouts.auth')

@section('content')
<div class="auth-container {{ $layout }}">
    @if(in_array($layout, ['split-left', 'split-right']))
    <div class="background-panel" style="background-image: url('{{ $backgroundImage }}');">
        <div class="background-panel-content">
            <h1>Welcome Back!</h1>
            <p>Sign in to access your account and continue where you left off. We're glad to see you again.</p>
        </div>
    </div>
    @endif

    <div class="form-panel">
        <div class="form-card">
            <!-- Logo -->
            <div class="logo-container">
                @if($branding['logo'] ?? false)
                    <img src="{{ $branding['logo'] }}" alt="{{ $branding['app_name'] ?? config('app.name') }}">
                @else
                    <span class="app-name">{{ $branding['app_name'] ?? config('app.name', 'Laravel') }}</span>
                @endif
            </div>

            <!-- Header -->
            <div class="form-header">
                <h2>Sign in to your account</h2>
                <p>Enter your credentials to access your dashboard</p>
            </div>

            <!-- Error Messages -->
            @if ($errors->any())
            <div class="error-list">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- Login Form -->
            <form method="POST" action="{{ route('tyro-login.login.submit') }}">
                @csrf

                <!-- Email Field -->
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input @error('email') is-invalid @enderror" 
                        value="{{ old('email') }}" 
                        required 
                        autocomplete="email" 
                        autofocus
                        placeholder="you@example.com"
                    >
                    @error('email')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input @error('password') is-invalid @enderror" 
                        required 
                        autocomplete="current-password"
                        placeholder="Enter your password"
                    >
                    @error('password')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="form-options">
                    @if($features['remember_me'] ?? true)
                    <div class="checkbox-group">
                        <input 
                            type="checkbox" 
                            id="remember" 
                            name="remember" 
                            class="checkbox-input"
                            {{ old('remember') ? 'checked' : '' }}
                        >
                        <label for="remember" class="checkbox-label">Remember me</label>
                    </div>
                    @else
                    <div></div>
                    @endif

                    @if(($features['forgot_password'] ?? true) && Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="form-link">Forgot password?</a>
                    @endif
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary">
                    Sign In
                </button>
            </form>

            <!-- Register Link -->
            @if($registrationEnabled ?? true)
            <div class="form-footer">
                <p>
                    Don't have an account? 
                    <a href="{{ route('tyro-login.register') }}" class="form-link">Create one now</a>
                </p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
