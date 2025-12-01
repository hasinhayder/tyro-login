@extends('tyro-login::layouts.auth')

@section('content')
<div class="auth-container {{ $layout }}" @if($layout==='fullscreen' ) style="background-image: url('{{ $backgroundImage }}');" @endif>
    @if(in_array($layout, ['split-left', 'split-right']))
    <div class="background-panel" style="background-image: url('{{ $backgroundImage }}');">
        <div class="background-panel-content">
            <h1>{{ $otpConfig['background_title'] ?? 'Almost There!' }}</h1>
            <p>{{ $otpConfig['background_description'] ?? 'Enter the verification code we sent to your email to complete the login process.' }}</p>
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

            <!-- Success Message -->
            @if(session('success'))
            <div class="success-message-box">
                <p>{{ session('success') }}</p>
            </div>
            @endif

            <!-- OTP Form -->
            <form method="POST" action="{{ route('tyro-login.otp.submit') }}">
                @csrf

                <!-- OTP Input -->
                <div class="form-group">
                    <label for="otp" class="form-label text-center">{{ $otpConfig['label'] ?? 'Verification Code' }}</label>
                    <div class="otp-input-container">
                        @for($i = 0; $i < $otpLength; $i++) <input type="text" class="otp-digit @error('otp') is-invalid @enderror" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="one-time-code" data-index="{{ $i }}" required>
                            @endfor
                    </div>
                    <input type="hidden" name="otp" id="otp-hidden" value="">
                    @error('otp')
                    <span class="error-message text-center">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary">
                    {{ $otpConfig['submit_button'] ?? 'Verify' }}
                </button>
            </form>

            <!-- Resend Section -->
            <div class="otp-actions">
                @if($canResend)
                <form method="POST" action="{{ route('tyro-login.otp.resend') }}" class="resend-form">
                    @csrf
                    <p class="resend-text">
                        Didn't receive the code?
                        <button type="submit" class="form-link resend-btn">
                            {{ $otpConfig['resend_button'] ?? 'Resend Code' }}
                        </button>
                    </p>
                    @if($resendCount > 0)
                    <p class="resend-count">Resends used: {{ $resendCount }}/{{ $maxResend }}</p>
                    @endif
                </form>
                @else
                <p class="resend-cooldown">
                    Resend available in <span id="cooldown-timer">{{ $remainingCooldown }}</span>s
                </p>
                @endif
            </div>

            <!-- Cancel Link -->
            <div class="form-footer">
                <p>
                    <a href="{{ route('tyro-login.otp.cancel') }}" class="form-link">Cancel and return to login</a>
                </p>
            </div>
        </div>
    </div>
</div>

<style>
    .text-center {
        text-align: center;
        display: block;
        width: 100%;
    }

    .success-message-box {
        background-color: #ecfdf5;
        border: 1px solid #a7f3d0;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1.5rem;
        text-align: center;
    }

    html.dark .success-message-box {
        background-color: #052e16;
        border-color: #166534;
    }

    .success-message-box p {
        color: #059669;
        font-size: 0.9375rem;
        margin: 0;
    }

    html.dark .success-message-box p {
        color: #34d399;
    }

    .otp-input-container {
        display: flex;
        gap: 0.5rem;
        justify-content: center;
    }

    .otp-digit {
        width: 3rem;
        height: 3.5rem;
        text-align: center;
        font-size: 1.5rem;
        font-weight: 600;
        border: 1px solid var(--input-border);
        border-radius: 0.5rem;
        background-color: var(--input-bg);
        color: var(--text-primary);
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .otp-digit:focus {
        outline: none;
        border-color: var(--input-focus-border);
        box-shadow: 0 0 0 1px var(--input-focus-border);
    }

    .otp-digit.is-invalid {
        border-color: var(--error-color);
    }

    .otp-digit.filled {
        border-color: var(--input-focus-border);
        background-color: var(--bg-secondary);
    }

    .otp-actions {
        text-align: center;
        margin-top: 1.5rem;
    }

    .resend-form {
        display: inline;
    }

    .resend-text {
        color: var(--text-secondary);
        font-size: 0.9375rem;
        margin: 0;
    }

    .resend-btn {
        background: none;
        border: none;
        padding: 0;
        font: inherit;
        cursor: pointer;
    }

    .resend-count {
        color: var(--text-muted);
        font-size: 0.8125rem;
        margin-top: 0.5rem;
    }

    .resend-cooldown {
        color: var(--text-secondary);
        font-size: 0.9375rem;
    }

    #cooldown-timer {
        font-weight: 600;
        color: var(--text-primary);
    }

    .form-footer {
        margin-top: 1.5rem;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const digits = document.querySelectorAll('.otp-digit');
        const hiddenInput = document.getElementById('otp-hidden');

        function updateHiddenInput() {
            let otp = '';
            digits.forEach(digit => {
                otp += digit.value;
            });
            hiddenInput.value = otp;
        }

        function updateFilledState() {
            digits.forEach(digit => {
                if (digit.value) {
                    digit.classList.add('filled');
                } else {
                    digit.classList.remove('filled');
                }
            });
        }

        digits.forEach((digit, index) => {
            digit.addEventListener('input', function (e) {
                // Allow only numbers
                this.value = this.value.replace(/[^0-9]/g, '');

                if (this.value && index < digits.length - 1) {
                    digits[index + 1].focus();
                }

                updateHiddenInput();
                updateFilledState();
            });

            digit.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' && !this.value && index > 0) {
                    digits[index - 1].focus();
                }

                // Allow paste
                if (e.key === 'v' && (e.ctrlKey || e.metaKey)) {
                    return;
                }

                // Arrow navigation
                if (e.key === 'ArrowLeft' && index > 0) {
                    e.preventDefault();
                    digits[index - 1].focus();
                }
                if (e.key === 'ArrowRight' && index < digits.length - 1) {
                    e.preventDefault();
                    digits[index + 1].focus();
                }
            });

            digit.addEventListener('paste', function (e) {
                e.preventDefault();
                const pastedData = (e.clipboardData || window.clipboardData).getData('text');
                const numbers = pastedData.replace(/[^0-9]/g, '').split('').slice(0, digits.length);

                numbers.forEach((num, i) => {
                    if (digits[i]) {
                        digits[i].value = num;
                    }
                });

                if (numbers.length > 0) {
                    const lastIndex = Math.min(numbers.length - 1, digits.length - 1);
                    digits[lastIndex].focus();
                }

                updateHiddenInput();
                updateFilledState();
            });

            digit.addEventListener('focus', function () {
                this.select();
            });
        });

        // Focus first digit on load
        if (digits[0]) {
            digits[0].focus();
        }

        // Cooldown timer
        const cooldownEl = document.getElementById('cooldown-timer');
        if (cooldownEl) {
            let remaining = parseInt(cooldownEl.textContent, 10);

            const timer = setInterval(function () {
                remaining--;
                cooldownEl.textContent = remaining;

                if (remaining <= 0) {
                    clearInterval(timer);
                    location.reload();
                }
            }, 1000);
        }
    });
</script>
@endsection