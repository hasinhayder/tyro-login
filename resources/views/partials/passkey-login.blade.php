@php
$passkeysInstalled = class_exists(\Laravel\Passkeys\Passkeys::class);
$passkeysEnabled = config('tyro-login.passkeys.enabled', false) && $passkeysInstalled;
$passwordDisabled = $features['disable_password'] ?? false;
$dividerText = config('tyro-login.passkeys.divider_text', 'or continue with email');
$loginButtonText = config('tyro-login.passkeys.login_button_text', 'Sign in with a passkey');
$cdnUrl = config('tyro-login.passkeys.cdn_url', 'https://esm.sh/@laravel/passkeys@0.2.0');
$afterLoginUrl = config('tyro-login.redirects.after_login', '/');
@endphp

@if($passkeysEnabled)
<div class="passkey-login-wrap" id="passkey-login-wrap" style="margin-bottom: 1.5rem;">
    <button type="button" class="btn btn-primary passkey-btn" id="passkey-login-btn">
        <svg class="passkey-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <circle cx="8" cy="15" r="4"></circle>
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.85 12.15 19 4"></path>
            <path stroke-linecap="round" stroke-linejoin="round" d="M18 5l2 2"></path>
            <path stroke-linecap="round" stroke-linejoin="round" d="m15 8 2 2"></path>
        </svg>
        <span class="passkey-btn-label">{{ $loginButtonText }}</span>
    </button>

    @if(! $passwordDisabled)
    <div class="passkey-divider" id="passkey-divider">
        <span>{{ $dividerText }}</span>
    </div>
    @endif

    <div class="passkey-error" id="passkey-login-error" role="alert" hidden></div>
</div>

<style>
    .passkey-btn {
        gap: 0.5rem;
    }
    .passkey-icon {
        width: 1.25rem;
        height: 1.25rem;
        flex-shrink: 0;
    }
    .passkey-btn.is-loading {
        opacity: 0.7;
        pointer-events: none;
    }
    .passkey-divider {
        display: flex;
        align-items: center;
        text-align: center;
        margin-top: 1.5rem;
    }
    .passkey-divider::before,
    .passkey-divider::after {
        content: '';
        flex: 1;
        border-bottom: 1px solid var(--border);
    }
    .passkey-divider span {
        padding: 0 1rem;
        font-size: 0.8125rem;
        color: var(--muted-foreground);
        white-space: nowrap;
    }
    .passkey-error {
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

<script type="module">
    (async function () {
        var wrap = document.getElementById('passkey-login-wrap');
        var btn = document.getElementById('passkey-login-btn');
        var divider = document.getElementById('passkey-divider');
        var errBox = document.getElementById('passkey-login-error');
        var afterLoginUrl = @js($afterLoginUrl);

        var Passkeys;
        try {
            var mod = await import(@js($cdnUrl));
            Passkeys = mod.Passkeys;
        } catch (e) {
            if (wrap) wrap.style.display = 'none';
            return;
        }

        // Hide everything if the browser can't do WebAuthn.
        var supported = Passkeys && Passkeys.isSupported && Passkeys.isSupported();
        if (!supported) {
            if (wrap) wrap.style.display = 'none';
            return;
        }

        function showError(message) {
            if (!errBox) return;
            errBox.textContent = message;
            errBox.hidden = false;
        }
        function clearError() {
            if (!errBox) return;
            errBox.hidden = true;
            errBox.textContent = '';
        }

        btn.addEventListener('click', async function () {
            clearError();
            var label = btn.querySelector('.passkey-btn-label');
            var original = label ? label.textContent : '';
            btn.classList.add('is-loading');
            if (label) label.textContent = 'Authenticating...';
            try {
                var res = await Passkeys.verify();
                if (res && res.redirect) {
                    window.location.href = res.redirect;
                } else {
                    window.location.href = afterLoginUrl;
                }
            } catch (e) {
                if (label) label.textContent = original;
                btn.classList.remove('is-loading');
                var name = (e && (e.name || e.constructor && e.constructor.name)) || '';
                if (/cancel/i.test(name)) {
                    // user dismissed the prompt - no error banner
                    return;
                }
                showError((e && e.message) || 'Passkey sign-in failed. Please try again.');
            }
        });

        // Conditional UI: surface saved passkeys in the login input autofill.
        if (Passkeys.autofill) {
            Passkeys.autofill().then(function (res) {
                if (res && res.redirect) {
                    window.location.href = res.redirect;
                }
            }).catch(function () {});
        }
    })();
</script>
@endif
