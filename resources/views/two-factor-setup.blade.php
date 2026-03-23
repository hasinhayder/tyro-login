@extends('tyro-login::layouts.auth')

@section('content')
<div class="auth-container {{ $layout }}" @if($layout==='fullscreen' ) style="background-image: url('{{ $backgroundImage }}');" @endif>
    @if(in_array($layout, ['split-left', 'split-right']))
    <div class="background-panel" style="background-image: url('{{ $backgroundImage }}');">
        <div class="background-panel-content">
            <h1>{{ config('tyro-login.two_factor.setup_title', 'Two Factor Authentication') }}</h1>
            <p>{{ config('tyro-login.two_factor.setup_subtitle', 'Scan the QR code with your authenticator app.') }}</p>
        </div>
    </div>
    @endif

    <div class="form-panel">
        <div class="form-card">
            <!-- Header -->
            <div class="form-header">
                <h2>{{ config('tyro-login.two_factor.setup_title', 'Two Factor Authentication') }}</h2>
                <p>{{ config('tyro-login.two_factor.setup_subtitle', 'Scan the QR code with your authenticator app.') }}</p>
            </div>

            <div class="qr-container text-center mb-6">
                {!! $qrCodeSvg !!}
                <div class="secret-key mt-4">
                    <p class="text-sm text-gray-500">Or enter this code manually:</p>
                    <code class="block font-mono bg-gray-100 p-2 rounded mt-1">{{ $secretKey }}</code>
                </div>
            </div>

            <!-- OTP Form -->
            <form method="POST" action="{{ route('tyro-login.two-factor.confirm') }}">
                @csrf

                <!-- OTP Input -->
                <div class="form-group">
                    <label for="code" class="form-label text-center">Enter the code from your app</label>
                    <div class="otp-input-container">
                        @for($i = 0; $i < 6; $i++) <input type="text" class="otp-digit @error('code') is-invalid @enderror" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="one-time-code" data-index="{{ $i }}" required>
                            @endfor
                    </div>
                    <input type="hidden" name="code" id="otp-hidden" value="">
                    @error('code')
                    <span class="error-message text-center">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary">
                    Verify & Enable
                </button>
            </form>

            @if(config('tyro-login.two_factor.allow_skip', false))
            <div class="skip-actions mt-4">
                <form method="POST" action="{{ route('tyro-login.two-factor.skip') }}" class="skip-inline-form">
                    @csrf
                    <button type="submit" class="skip-link">Skip this time</button>
                </form>
                <span class="skip-separator">|</span>
                <form method="POST" action="{{ route('tyro-login.two-factor.ignore') }}" class="skip-inline-form">
                    @csrf
                    <button type="submit" class="skip-link">Skip and ignore</button>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
    .qr-container svg {
        margin: 0 auto;
        max-width: 200px;
        height: auto;
    }
    .text-center {
        text-align: center;
        width: 100%;
    }
    .mt-4 { margin-top: 1rem; }
    .mb-6 { margin-bottom: 1.5rem; }
    .text-sm { font-size: 0.875rem; }
    .text-gray-500 { color: var(--muted-foreground); }
    .hover\:text-gray-700:hover { color: var(--foreground); }
    .font-medium { font-weight: 500; }
    .block { display: block; }
    .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
    .bg-gray-100 { background-color: var(--muted); }
    .p-2 { padding: 0.5rem; }
    .rounded { border-radius: 0.375rem; }
    .w-full { width: 100%; }
    
    .skip-actions {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    .skip-inline-form { display: inline; }
    .skip-link {
        background: none;
        border: none;
        padding: 0;
        cursor: pointer;
        font-size: 0.875rem;
        color: var(--muted-foreground);
        text-decoration: none;
        text-underline-offset: 3px;
    }
    .skip-link:hover { color: var(--foreground); }
    .skip-separator {
        font-size: 0.7rem;
        color: var(--muted-foreground);
        opacity: 0.5;
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
        border: 1px solid var(--input);
        border-radius: 0.375rem;
        background-color: var(--background);
        color: var(--foreground);
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .otp-digit:focus {
        outline: none;
        border-color: var(--ring);
        box-shadow: 0 0 0 1px var(--ring);
    }

    .otp-digit.is-invalid {
        border-color: var(--destructive);
    }

    .otp-digit.filled {
        border-color: var(--ring);
        background-color: var(--muted);
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

        digits.forEach((digit, index) => {
            digit.addEventListener('input', function (e) {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value && index < digits.length - 1) {
                    digits[index + 1].focus();
                }
                updateHiddenInput();
            });

            digit.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' && !this.value && index > 0) {
                    digits[index - 1].focus();
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
                
                updateHiddenInput();
            });
        });
    });
</script>
@endsection
