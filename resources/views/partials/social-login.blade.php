@php
    $enabledProviders = \HasinHayder\TyroLogin\Http\Controllers\SocialAuthController::getEnabledProviders();
    $dividerText = config('tyro-login.social.divider_text', 'Or continue with');
@endphp

@if(count($enabledProviders) > 0)
<div class="social-login-container">
    <div class="social-divider">
        <span>{{ $dividerText }}</span>
    </div>

    <div class="social-buttons">
        @foreach($enabledProviders as $provider => $config)
            <a href="{{ route('tyro-login.social.redirect', ['provider' => $provider, 'action' => $action ?? 'login']) }}" 
               class="social-btn social-btn-{{ $provider }}"
               title="{{ $config['label'] ?? ucfirst($provider) }}">
                @include('tyro-login::partials.social-icons', ['icon' => $config['icon'] ?? $provider])
                <span>{{ $config['label'] ?? ucfirst($provider) }}</span>
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
        border-bottom: 1px solid var(--border-color);
    }

    .social-divider span {
        padding: 0 1rem;
        font-size: 0.8125rem;
        color: var(--text-secondary);
        white-space: nowrap;
    }

    .social-buttons {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .social-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        width: 100%;
        padding: 0.75rem 1rem;
        font-size: 0.9375rem;
        font-weight: 500;
        font-family: inherit;
        border-radius: 0.5rem;
        border: 1px solid var(--border-color);
        background-color: var(--bg-primary);
        color: var(--text-primary);
        text-decoration: none;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .social-btn:hover {
        background-color: var(--bg-secondary);
        border-color: var(--input-focus-border);
    }

    .social-btn:active {
        transform: scale(0.98);
    }

    .social-btn svg {
        width: 1.25rem;
        height: 1.25rem;
        flex-shrink: 0;
    }

    /* Provider-specific colors on hover */
    .social-btn-google:hover {
        border-color: #4285f4;
    }

    .social-btn-facebook:hover {
        border-color: #1877f2;
    }

    .social-btn-github:hover {
        border-color: #333;
    }

    html.dark .social-btn-github:hover {
        border-color: #fff;
    }

    .social-btn-twitter:hover {
        border-color: #000;
    }

    html.dark .social-btn-twitter:hover {
        border-color: #fff;
    }

    .social-btn-linkedin:hover {
        border-color: #0a66c2;
    }

    .social-btn-bitbucket:hover {
        border-color: #0052cc;
    }

    .social-btn-gitlab:hover {
        border-color: #fc6d26;
    }

    .social-error {
        margin-top: 1rem;
        padding: 0.75rem 1rem;
        background-color: var(--error-bg);
        border: 1px solid var(--error-border);
        border-radius: 0.5rem;
        color: var(--error-color);
        font-size: 0.875rem;
        text-align: center;
    }

    /* Grid layout for many providers */
    @media (min-width: 480px) {
        .social-buttons.grid-layout {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
        }

        .social-buttons.grid-layout .social-btn span {
            display: none;
        }

        .social-buttons.grid-layout .social-btn {
            padding: 0.875rem;
        }
    }
</style>
@endif
