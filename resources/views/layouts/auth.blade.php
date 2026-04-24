<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">

    <title>{{ $branding['app_name'] ?? config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    @include('tyro-login::partials.styles')
</head>

<body>
    <!-- Theme Toggle Button -->
    <button type="button" class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle theme">
        <svg class="sun-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
        <svg class="moon-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
        </svg>
    </button>

    @yield('content')

    <script>
        // Theme management
        function getTheme() {
            if (localStorage.getItem('tyro-login-theme')) {
                return localStorage.getItem('tyro-login-theme');
            }
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }

        function setTheme(theme) {
            localStorage.setItem('tyro-login-theme', theme);
            document.documentElement.classList.remove('light', 'dark');
            document.documentElement.classList.add(theme);
        }

        function toggleTheme() {
            const currentTheme = getTheme();
            setTheme(currentTheme === 'dark' ? 'light' : 'dark');
        }

        // Apply theme on load
        setTheme(getTheme());

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem('tyro-login-theme')) {
                setTheme(e.matches ? 'dark' : 'light');
            }
        });

        // Form validation enhancement
        document.addEventListener('DOMContentLoaded', function () {
            const forms = document.querySelectorAll('form');

            forms.forEach(form => {
                form.addEventListener('submit', function (e) {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn && !submitBtn.classList.contains('loading')) {
                        submitBtn.dataset.originalText = submitBtn.textContent;
                        submitBtn.textContent = 'Working...';
                        submitBtn.classList.add('loading');
                        submitBtn.disabled = true;
                    }
                });
            });

            // Real-time validation feedback
            const inputs = document.querySelectorAll('.form-input');
            inputs.forEach(input => {
                input.addEventListener('blur', function () {
                    if (this.value && this.checkValidity()) {
                        this.classList.remove('is-invalid');
                    }
                });

                input.addEventListener('input', function () {
                    if (this.classList.contains('is-invalid') && this.checkValidity()) {
                        this.classList.remove('is-invalid');
                        const errorEl = this.parentNode.querySelector('.error-message');
                        if (errorEl) {
                            errorEl.remove();
                        }
                    }
                });
            });
        });
    </script>
</body>

</html>