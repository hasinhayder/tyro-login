@extends('tyro-login::layouts.auth')

@section('content')
<div class="auth-container {{ $layout }}" @if($layout==='fullscreen') style="background-image: url('{{ $backgroundImage }}');" @endif @if($layout==='youtube-video') id="tyro-youtube-container" @endif>
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

            <!-- Success / error alerts -->
            @if(session('success'))
            <div class="alert alert-success" style="padding: 0.875rem 1rem; margin-bottom: 1.5rem; background-color: #d1fae5; border: 1px solid #6ee7b7; border-radius: 0.5rem; color: #065f46; font-size: 0.9375rem;">
                {{ session('success') }}
            </div>
            @endif

            <div id="passkey-setup-error" class="alert alert-error" role="alert" style="display:none; padding: 0.875rem 1rem; margin-bottom: 1.5rem; background-color: #fef2f2; border: 1px solid #fca5a5; border-radius: 0.5rem; color: #991b1b; font-size: 0.9375rem;"></div>

            <!-- Passkey name (optional) -->
            <div class="form-group">
                <label for="passkey-name" class="form-label">Passkey name (optional)</label>
                <input type="text" id="passkey-name" class="form-input" placeholder="e.g. MacBook Pro" autocomplete="off">
            </div>

            <button type="button" class="btn btn-primary" id="passkey-create-btn">
                <svg style="width:1.25rem;height:1.25rem;display:inline-block;vertical-align:middle;margin-right:0.5rem;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <circle cx="8" cy="15" r="4"></circle>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.85 12.15 19 4"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 5l2 2"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" d="m15 8 2 2"></path>
                </svg>
                <span class="passkey-create-label">{{ $buttonText }}</span>
            </button>

            <div class="form-footer" style="margin-top: 1.5rem;">
                <p>
                    <a href="{{ config('tyro-login.redirects.after_login', '/') }}" class="form-link">Back to app</a>
                </p>
            </div>
        </div>
    </div>
</div>

@include('tyro-login::partials.backgrounds')

<script type="module">
    (async function () {
        var btn = document.getElementById('passkey-create-btn');
        var nameInput = document.getElementById('passkey-name');
        var errBox = document.getElementById('passkey-setup-error');
        var afterLoginUrl = @js($afterLoginUrl);
        var cdnUrl = @js($cdnUrl);

        var Passkeys;
        try {
            var mod = await import(cdnUrl);
            Passkeys = mod.Passkeys;
        } catch (e) {
            showError('The passkey client could not be loaded. Check your network connection and try again.');
            btn.disabled = true;
            return;
        }

        if (!Passkeys || !Passkeys.isSupported || !Passkeys.isSupported()) {
            showError('Passkeys are not supported in this browser. Use a modern browser over HTTPS.');
            btn.disabled = true;
            return;
        }

        function showError(message) {
            errBox.textContent = message;
            errBox.style.display = 'block';
        }
        function clearError() {
            errBox.style.display = 'none';
            errBox.textContent = '';
        }

        btn.addEventListener('click', async function () {
            clearError();
            var label = btn.querySelector('.passkey-create-label');
            var original = label ? label.textContent : '';
            btn.classList.add('loading');
            btn.disabled = true;
            if (label) label.textContent = 'Working...';

            try {
                var name = (nameInput && nameInput.value ? nameInput.value : null);
                await Passkeys.register({ name: name });
                window.location.href = afterLoginUrl;
            } catch (e) {
                btn.classList.remove('loading');
                btn.disabled = false;
                if (label) label.textContent = original;

                var errName = (e && (e.name || (e.constructor && e.constructor.name))) || '';
                if (/cancel/i.test(errName)) {
                    return; // user dismissed the prompt
                }
                if (/exists/i.test(errName)) {
                    showError('A passkey for this account is already registered on this device.');
                    return;
                }
                showError((e && e.message) || 'Passkey creation failed. Please try again.');
            }
        });
    })();
</script>
@endsection
