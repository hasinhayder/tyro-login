@extends('tyro-login::layouts.auth')

@section('content')
<div class="auth-container {{ $layout }}" @if($layout==='fullscreen' ) style="background-image: url('{{ $backgroundImage }}');" @endif>
    @if(in_array($layout, ['split-left', 'split-right']))
    <div class="background-panel" style="background-image: url('{{ $backgroundImage }}');">
        <div class="background-panel-content">
            <h1>{{ $title }}</h1>
            <p>{{ $subtitle }}</p>
        </div>
    </div>
    @endif

    <div class="form-panel">
        <div class="form-card">
            <!-- Header -->
            <div class="form-header">
                <h2>{{ $title }}</h2>
                <p>{{ $subtitle }}</p>
            </div>

            <form method="POST" action="{{ route('tyro-login.two-factor.verify') }}">
                @csrf

                <!-- OTP Input Section -->
                <div id="otp-section" style="@error('recovery_code') display: none; @enderror">
                    <div class="form-group">
                        <label class="form-label text-center">Authentication Code</label>
                         <div class="otp-input-container">
                        @for($i = 0; $i < 6; $i++) <input type="text" class="otp-digit @error('code') is-invalid @enderror" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="one-time-code" data-index="{{ $i }}">
                            @endfor
                        </div>
                        <input type="hidden" name="code" id="otp-hidden" value="">
                        @error('code')
                        <span class="error-message text-center">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- Recovery Code Section -->
                 <div id="recovery-section" style="@error('recovery_code') display: block; @else display: none; @enderror">
                    <div class="form-group">
                        <label for="recovery_code" class="form-label">Recovery Code</label>
                        <input type="text" name="recovery_code" id="recovery_code" class="form-control" placeholder="123abcde-...-...">
                        @error('recovery_code')
                        <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary mt-4">
                    Verify
                </button>
            </form>

            <div class="mt-6 text-center">
                <button type="button" id="toggle-recovery" class="btn btn-ghost w-full">
                    @error('recovery_code')
                        Use an authentication code
                    @else
                        Use a recovery code
                    @enderror
                </button>
            </div>
            
             <div class="mt-4 text-center">
                 <form method="POST" action="{{ route('tyro-login.logout') }}">
                     @csrf
                    <button type="submit" class="btn btn-ghost w-full" style="opacity: 0.7;">
                        Cancel & Logout
                    </button>
                 </form>
            </div>
        </div>
    </div>
</div>

<style>
    .otp-input-container {
        display: flex;
        gap: 0.5rem;
        justify-content: center;
        margin-bottom: 1rem;
    }
    
    .otp-digit {
        width: 3rem;
        height: 3.5rem;
        text-align: center;
        font-size: 1.5rem;
        font-weight: 600;
        border: 1px solid var(--input);
        border-radius: 0.5rem;
        background-color: var(--background);
        color: var(--foreground);
        transition: border-color 0.15s ease;
    }
    .otp-digit:focus {
        outline: none;
        border-color: var(--ring);
        box-shadow: 0 0 0 1px var(--ring);
    }
    .text-center { text-align: center; }
    .mt-4 { margin-top: 1rem; }
    .mt-6 { margin-top: 1.5rem; }
    .text-sm { font-size: 0.875rem; }
    .text-xs { font-size: 0.75rem; }
    .text-gray-500 { color: var(--muted-foreground); }
    .text-gray-400 { color: var(--muted-foreground); opacity: 0.8; }
    .hover\:text-gray-700:hover { color: var(--foreground); }
    .underline { text-decoration: underline; text-underline-offset: 4px; }
    .cursor-pointer { cursor: pointer; }
    .bg-transparent { background: transparent; }
    .border-0 { border: 0; }
    .form-control {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid var(--input);
        border-radius: 0.375rem;
        background: var(--background);
        color: var(--foreground); 
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const otpSection = document.getElementById('otp-section');
        const recoverySection = document.getElementById('recovery-section');
        const toggleBtn = document.getElementById('toggle-recovery');
        const digits = document.querySelectorAll('.otp-digit');
        const hiddenInput = document.getElementById('otp-hidden');

        toggleBtn.addEventListener('click', function() {
            if (recoverySection.style.display === 'none') {
                recoverySection.style.display = 'block';
                otpSection.style.display = 'none';
                toggleBtn.textContent = 'Use an authentication code';
                hiddenInput.value = ''; // clear otp
            } else {
                recoverySection.style.display = 'none';
                otpSection.style.display = 'block';
                toggleBtn.textContent = 'Use a recovery code';
                document.getElementById('recovery_code').value = ''; // clear recovery
            }
        });

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
                if (hiddenInput.value.length === digits.length) {
                    this.closest('form').requestSubmit();
                }
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
