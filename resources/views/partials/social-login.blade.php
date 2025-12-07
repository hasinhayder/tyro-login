@php
$enabledProviders = \HasinHayder\TyroLogin\Http\Controllers\SocialAuthController::getEnabledProviders();
$dividerText = config('tyro-login.social.divider_text', 'Or continue with');
@endphp

@if (count($enabledProviders) > 0)
<div class="social-login-container">
    <div class="social-divider">
        <span>{{ $dividerText }}</span>
    </div>

    <div class="social-buttons">
        @foreach ($enabledProviders as $provider => $config)
        <a href="{{ route('tyro-login.social.redirect', ['provider' => $provider, 'action' => $action ?? 'login']) }}" class="social-btn social-btn-{{ $provider }}" title="{{ $config['label'] ?? ucfirst($provider) }}">
            @include('tyro-login::partials.social-icons', ['icon' => $config['icon'] ?? $provider])
        </a>
        @endforeach
    </div>

    @error('social')
    <div class="social-error">
        {{ $message }}
    </div>
    @enderror
</div>

<style>
    .social-login-container {
        margin-top: 1.5rem;
    }

    .social-divider {
        display: flex;
        align-items: center;
        text-align: center;
        margin-bottom: 1.5rem;
    }

    .social-divider::before,
    .social-divider::after {
        content: '';
        flex: 1;
        border-bottom: 1px solid var(--border);
    }

    .social-divider span {
        padding: 0 1rem;
        font-size: 0.8125rem;
        color: var(--muted-foreground);
        white-space: nowrap;
    }

    .social-buttons {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        justify-content: center;
        gap: 0.75rem;
    }

    .social-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 3rem;
        height: 3rem;
        font-size: 0.9375rem;
        font-weight: 500;
        font-family: inherit;
        border-radius: 0.5rem;
        border: 1px solid var(--border);
        background-color: var(--background);
        color: var(--foreground);
        text-decoration: none;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .social-btn:hover {
        background-color: var(--muted);
        border-color: var(--ring);
        transform: translateY(-2px);
    }

    .social-btn:active {
        transform: scale(0.95);
    }

    .social-btn svg {
        width: 1.25rem;
        height: 1.25rem;
        flex-shrink: 0;
    }

    /* Provider-specific colors on hover */
    .social-btn-google:hover {
        border-color: #4285f4;
        background-color: rgba(66, 133, 244, 0.1);
    }

    .social-btn-facebook:hover {
        border-color: #1877f2;
        background-color: rgba(24, 119, 242, 0.1);
    }

    .social-btn-github:hover {
        border-color: #333;
        background-color: rgba(51, 51, 51, 0.1);
    }

    html.dark .social-btn-github:hover {
        border-color: #fff;
        background-color: rgba(255, 255, 255, 0.1);
    }

    .social-btn-twitter:hover {
        border-color: #000;
        background-color: rgba(0, 0, 0, 0.1);
    }

    html.dark .social-btn-twitter:hover {
        border-color: #fff;
        background-color: rgba(255, 255, 255, 0.1);
    }

    .social-btn-linkedin:hover {
        border-color: #0a66c2;
        background-color: rgba(10, 102, 194, 0.1);
    }

    .social-btn-bitbucket:hover {
        border-color: #0052cc;
        background-color: rgba(0, 82, 204, 0.1);
    }

    .social-btn-gitlab:hover {
        border-color: #fc6d26;
        background-color: rgba(252, 109, 38, 0.1);
    }

    .social-btn-slack:hover {
        border-color: #4a154b;
        background-color: rgba(74, 21, 75, 0.1);
    }

    .social-btn-apple:hover {
        border-color: #000;
        background-color: rgba(0, 0, 0, 0.1);
    }

    html.dark .social-btn-apple:hover {
        border-color: #fff;
        background-color: rgba(255, 255, 255, 0.1);
    }

    .social-btn-wordpress:hover {
        border-color: #21759b;
        background-color: rgba(33, 117, 155, 0.1);
    }

    .social-btn-auth0:hover {
        border-color: #eb5424;
        background-color: rgba(235, 84, 36, 0.1);
    }

    .social-btn-clerk:hover {
        border-color: #6C47FF;
        background-color: rgba(108, 71, 255, 0.1);
    }

    .social-btn-steam:hover {
        border-color: #171a21;
        background-color: rgba(23, 26, 33, 0.1);
    }

    html.dark .social-btn-steam:hover {
        border-color: #fff;
        background-color: rgba(255, 255, 255, 0.1);
    }

    .social-btn-discord:hover {
        border-color: #5865F2;
        background-color: rgba(88, 101, 242, 0.1);
    }

    .social-error {
        margin-top: 1rem;
        padding: 0.75rem 1rem;
        background-color: color-mix(in srgb, var(--destructive), transparent 90%);
        border: 1px solid var(--destructive);
        border-radius: 0.5rem;
        color: var(--destructive);
        font-size: 0.875rem;
        text-align: center;
    }
</style>
@endif