@extends('tyro-login::layouts.auth')

@section('content')
<div class="auth-container {{ $layout }}" @if($layout==='fullscreen' ) style="background-image: url('{{ $backgroundImage }}');" @endif>
    @if(in_array($layout, ['split-left', 'split-right']))
    <div class="background-panel" style="background-image: url('{{ $backgroundImage }}');">
        <div class="background-panel-content">
            <h1>Security Notice</h1>
            <p>Your account has been temporarily locked for security reasons. Please wait and try again later.</p>
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
                <div class="app-logo">
                    <svg viewBox="0 0 50 52" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M49.626 11.564a.809.809 0 0 1 .028.209v10.972a.8.8 0 0 1-.402.694l-9.209 5.302V39.25c0 .286-.152.55-.4.694L20.42 51.01c-.044.025-.092.041-.14.058-.018.006-.035.017-.054.022a.805.805 0 0 1-.41 0c-.022-.006-.042-.018-.063-.026-.044-.016-.09-.03-.132-.054L.402 39.944A.801.801 0 0 1 0 39.25V6.334c0-.072.01-.142.028-.21.006-.023.02-.044.028-.067.015-.042.029-.085.051-.124.015-.026.037-.047.055-.071.023-.032.044-.065.071-.093.023-.023.053-.04.079-.06.029-.024.055-.05.088-.069h.001l9.61-5.533a.802.802 0 0 1 .8 0l9.61 5.533h.002c.032.02.059.045.088.068.026.02.055.038.078.06.028.029.048.062.072.094.017.024.04.045.054.071.023.04.036.082.052.124.008.023.022.044.028.068a.809.809 0 0 1 .028.209v20.559l8.008-4.611v-10.51c0-.07.01-.141.028-.208.007-.024.02-.045.028-.068.016-.042.03-.085.052-.124.015-.026.037-.047.054-.071.024-.032.044-.065.072-.093.023-.023.052-.04.078-.06.03-.024.056-.05.088-.069h.001l9.611-5.533a.801.801 0 0 1 .8 0l9.61 5.533c.034.02.06.045.09.068.025.02.054.038.077.06.028.029.048.062.072.094.018.024.04.045.054.071.023.039.036.082.052.124.009.023.022.044.028.068zm-1.574 10.718v-9.124l-3.363 1.936-4.646 2.675v9.124l8.01-4.611zm-9.61 16.505v-9.13l-4.57 2.61-13.05 7.448v9.216l17.62-10.144zM1.602 7.719v31.068L19.22 48.93v-9.214l-9.204-5.209-.003-.002-.004-.002c-.031-.018-.057-.044-.086-.066-.025-.02-.054-.036-.076-.058l-.002-.003c-.026-.025-.044-.056-.066-.084-.02-.027-.044-.05-.06-.078l-.001-.003c-.018-.03-.029-.066-.042-.1-.013-.03-.03-.058-.038-.09v-.001c-.01-.038-.012-.078-.016-.117-.004-.03-.012-.06-.012-.09v-.002-21.481L4.965 9.654 1.602 7.72zm8.81-5.994L2.405 6.334l8.005 4.609 8.006-4.61-8.006-4.608zm4.164 28.764l4.645-2.674V7.719l-3.363 1.936-4.646 2.675v20.096l3.364-1.937zM39.243 7.164l-8.006 4.609 8.006 4.609 8.005-4.61-8.005-4.608zm-.801 10.605l-4.646-2.675-3.363-1.936v9.124l4.645 2.674 3.364 1.937v-9.124zM20.02 38.33l11.743-6.704 5.87-3.35-8-4.606-9.211 5.303-8.395 4.833 7.993 4.524z" fill="currentColor" />
                    </svg>
                </div>
                @endif
            </div>

            <!-- Header -->
            <div class="form-header">
                <h2>{{ $title }}</h2>
                <p>{{ $subtitle }}</p>
            </div>

            <!-- Lockout Message -->
            <div class="lockout-message">
                <p>{{ $message }}</p>
            </div>

            <!-- Countdown Timer -->
            @if($releaseTime)
            <div class="countdown-container">
                <div class="countdown-label">Time remaining</div>
                <div class="countdown-timer" id="countdown" data-release="{{ $releaseTime }}">
                    <span class="countdown-value" id="countdown-minutes">{{ str_pad($remainingMinutes, 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="countdown-separator">:</span>
                    <span class="countdown-value" id="countdown-seconds">00</span>
                </div>
            </div>
            @endif

            <!-- Try Again Button -->
            <a href="{{ route('tyro-login.login') }}" class="btn btn-primary" id="try-again-btn">
                Try again
            </a>

            <!-- Help Text -->
            <div class="form-footer">
                <p>
                    If you believe this is an error, please contact support.
                </p>
            </div>
        </div>
    </div>
</div>

<style>
    .lockout-message {
        background-color: var(--error-bg);
        border: 1px solid var(--error-border);
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1.5rem;
        text-align: center;
    }

    .lockout-message p {
        color: var(--error-color);
        font-size: 0.9375rem;
        margin: 0;
        line-height: 1.5;
    }

    .countdown-container {
        text-align: center;
        margin-bottom: 1.5rem;
        padding: 1.25rem;
        background-color: var(--bg-secondary);
        border-radius: 0.5rem;
        border: 1px solid var(--border-color);
    }

    .countdown-label {
        font-size: 0.8125rem;
        color: var(--text-secondary);
        margin-bottom: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .countdown-timer {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.25rem;
    }

    .countdown-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-primary);
        font-variant-numeric: tabular-nums;
        min-width: 3rem;
        text-align: center;
    }

    .countdown-separator {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-secondary);
        animation: blink 1s infinite;
    }

    @keyframes blink {

        0%,
        50% {
            opacity: 1;
        }

        51%,
        100% {
            opacity: 0.3;
        }
    }

    .countdown-expired .countdown-value,
    .countdown-expired .countdown-separator {
        color: var(--text-muted);
        animation: none;
    }

    #try-again-btn {
        text-decoration: none;
    }

    #try-again-btn.disabled {
        opacity: 0.5;
        pointer-events: none;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const countdownEl = document.getElementById('countdown');
        const tryAgainBtn = document.getElementById('try-again-btn');
        const loginUrl = '{{ route('tyro-login.login') }}';
        const autoRedirect = {{ config('tyro-login.lockout.auto_redirect', true) ? 'true' : 'false'
    }};

    if (!countdownEl) return;

    const releaseTime = parseInt(countdownEl.dataset.release, 10);
    const minutesEl = document.getElementById('countdown-minutes');
    const secondsEl = document.getElementById('countdown-seconds');

    function updateCountdown() {
        const now = Math.floor(Date.now() / 1000);
        const remaining = releaseTime - now;

        if (remaining <= 0) {
            // Lockout expired
            minutesEl.textContent = '00';
            secondsEl.textContent = '00';
            countdownEl.classList.add('countdown-expired');
            tryAgainBtn.classList.remove('disabled');

            // Auto redirect if enabled
            if (autoRedirect) {
                window.location.href = loginUrl;
            }
            return;
        }

        const minutes = Math.floor(remaining / 60);
        const seconds = remaining % 60;

        minutesEl.textContent = String(minutes).padStart(2, '0');
        secondsEl.textContent = String(seconds).padStart(2, '0');

        requestAnimationFrame(() => {
            setTimeout(updateCountdown, 1000);
        });
    }

    // Initially disable the button if there's remaining time
    const now = Math.floor(Date.now() / 1000);
    if (releaseTime - now > 0) {
        tryAgainBtn.classList.add('disabled');
    }

    updateCountdown();
    });
</script>
@endsection