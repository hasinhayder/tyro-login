{{-- Include shadcn theme variables --}}
@include('tyro-login::partials.shadcn-theme')

<style>
    *,
    *::before,
    *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        background-color: var(--muted);
        min-height: 100vh;
        line-height: 1.6;
        color: var(--foreground);
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        transition: background-color 0.2s ease, color 0.2s ease;
    }

    /* Auth Container */
    .auth-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
    }

    .auth-container.split-left,
    .auth-container.split-right {
        padding: 0;
    }

    /* Background Panel (for split layouts) */
    .background-panel {
        display: none;
        flex: 1;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        position: relative;
        min-height: 100vh;
    }

    .background-panel::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(17, 24, 39, 0.9) 0%, rgba(17, 24, 39, 0.7) 100%);
    }

    html.dark .background-panel::before {
        background: linear-gradient(135deg, rgba(0, 0, 0, 0.9) 0%, rgba(0, 0, 0, 0.7) 100%);
    }

    .background-panel-content {
        position: relative;
        z-index: 10;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 4rem;
        color: white;
        height: 100%;
    }

    .background-panel-content h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .background-panel-content p {
        font-size: 1.125rem;
        opacity: 0.9;
        max-width: 28rem;
    }

    .auth-container.split-left .background-panel,
    .auth-container.split-right .background-panel {
        display: flex;
    }

    .auth-container.split-right {
        flex-direction: row-reverse;
    }

    /* Form Panel */
    .form-panel {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        width: 100%;
        max-width: 520px;
    }

    .auth-container.split-left .form-panel,
        .auth-container.split-right .form-panel {
        flex: 1;
        max-width: 50%;
        background-color: var(--background);
        min-height: 100vh;
    }

    /* Form Card */
    .form-card {
        width: 100%;
        max-width: 360px;
    }

    .card .form-card,
    .fullscreen .form-card {
        max-width: 420px;
    }

    /* Logo */
    .logo-container {
        text-align: center;
        margin-bottom: 2rem;
    }

    .logo-container img {
        height: {
                {
                $branding['logo_height'] ?? '48px'
            }
        }

        ;
        width: auto;
    }

    .logo-container .app-logo {
        display: inline-block;
    }

    .logo-container .app-logo svg {
        width: 48px;
        height: 48px;
        color: var(--foreground);
    }

    /* Form Header */
    .form-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .form-header h2 {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--foreground);
        margin-bottom: 0.5rem;
        letter-spacing: -0.025em;
    }

    .form-header p {
        color: var(--muted-foreground);
        font-size: 0.9375rem;
    }

    /* Form Elements */
    .form-group {
        margin-bottom: 1.25rem;
    }

    .form-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--foreground);
        margin-bottom: 0.5rem;
    }

    .form-input {
        width: 100%;
        padding: 0.75rem 0.875rem;
        font-size: 0.9375rem;
        font-family: inherit;
        border: 1px solid var(--input);
        border-radius: 0.5rem;
        background-color: var(--background);
        color: var(--foreground);
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .form-input::placeholder {
        color: var(--muted-foreground);
    }

    .form-input:focus {
        outline: none;
        border-color: var(--ring);
        box-shadow: 0 0 0 1px var(--ring);
    }

    .form-input.is-invalid {
        border-color: var(--destructive);
    }

    .form-input.is-invalid:focus {
        box-shadow: 0 0 0 1px var(--destructive);
    }

    /* Checkbox */
    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 0.625rem;
    }

    .checkbox-input {
        width: 1rem;
        height: 1rem;
        border-radius: 0.25rem;
        border: 1.5px solid var(--border);
        background-color: transparent;
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none;
        transition: all 0.15s ease;
        position: relative;
    }

    .checkbox-input:checked {
        background-color: var(--primary);
        border-color: var(--primary);
    }

    .checkbox-input:checked::after {
        content: '';
        position: absolute;
        left: 4px;
        top: 1px;
        width: 5px;
        height: 9px;
        border: solid var(--background);
        border-width: 0 2px 2px 0;
        transform: rotate(45deg);
    }

    html.dark .checkbox-input:checked::after {
        border-color: var(--foreground);
        border-color: #111827;
    }

    .checkbox-label {
        font-size: 0.875rem;
        color: var(--foreground);
        cursor: pointer;
        user-select: none;
    }

    /* Form Options Row */
    .form-options {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
        margin-top: 0.25rem;
    }

    /* Links */
    .form-link {
        font-size: 0.875rem;
        color: var(--foreground);
        text-decoration: underline;
        text-underline-offset: 2px;
        font-weight: 500;
        transition: color 0.15s ease;
    }

    .form-link:hover {
        color: var(--muted-foreground);
    }

    /* Buttons */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        padding: 0.75rem 1.5rem;
        font-size: 0.9375rem;
        font-weight: 500;
        font-family: inherit;
        border-radius: 0.5rem;
        border: none;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .btn-primary {
        background-color: var(--primary);
        color: var(--primary-foreground);
    }

    .btn-primary:hover {
        background-color: var(--primary);
        opacity: 0.9;
    }

    .btn-primary:active {
        transform: scale(0.98);
    }

    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    /* Error Messages */
    .error-message {
        color: var(--destructive);
        font-size: 0.8125rem;
        margin-top: 0.375rem;
    }

    .error-list {
        background-color: color-mix(in srgb, var(--destructive), transparent 90%);
        border: 1px solid var(--destructive);
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }

    .error-list ul {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .error-list li {
        color: var(--destructive);
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
    }

    .error-list li:last-child {
        margin-bottom: 0;
    }

    /* Form Footer */
    .form-footer {
        text-align: center;
        margin-top: 1.5rem;
    }

    .form-footer p {
        color: var(--muted-foreground);
        font-size: 0.9375rem;
    }

    .form-footer .form-link {
        color: var(--foreground);
    }

    /* Theme Toggle */
    .theme-toggle {
        position: fixed;
        top: 1.5rem;
        right: 1.5rem;
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.5rem;
        border: 1px solid var(--border);
        background-color: var(--background);
        color: var(--foreground);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s ease;
        z-index: 100;
    }

    .theme-toggle:hover {
        background-color: var(--muted);
    }

    .theme-toggle svg {
        width: 1.25rem;
        height: 1.25rem;
    }

    .theme-toggle .sun-icon {
        display: none;
    }

    .theme-toggle .moon-icon {
        display: block;
    }

    html.dark .theme-toggle .sun-icon {
        display: block;
    }

    html.dark .theme-toggle .moon-icon {
        display: none;
    }

    /* Responsive */
    @media (max-width: 1024px) {

        .auth-container.split-left .background-panel,
        .auth-container.split-right .background-panel {
            display: none;
        }

        .auth-container.split-left .form-panel,
        .auth-container.split-right .form-panel {
            max-width: 100%;
            min-height: auto;
        }

        .auth-container.split-left,
        .auth-container.split-right {
            padding: 1.5rem;
        }
    }

    @media (max-width: 480px) {
        .form-card {
            max-width: 100%;
        }

        .form-options {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .theme-toggle {
            top: 1rem;
            right: 1rem;
        }
    }

    /* ========================================
       NEW LAYOUT STYLES
       ======================================== */

    /* Fullscreen Background Layout */
    .auth-container.fullscreen {
        padding: 0;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        position: relative;
    }

    .auth-container.fullscreen::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(17, 24, 39, 0.85) 0%, rgba(17, 24, 39, 0.65) 100%);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
    }

    html.dark .auth-container.fullscreen::before {
        background: linear-gradient(135deg, rgba(0, 0, 0, 0.85) 0%, rgba(0, 0, 0, 0.65) 100%);
    }

    .auth-container.fullscreen .form-panel {
        position: relative;
        z-index: 10;
    }

    .auth-container.fullscreen .form-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 1rem;
        padding: 2.5rem;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }

    html.dark .auth-container.fullscreen .form-card {
        background: rgba(26, 26, 26, 0.95);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* Card Layout */
    .auth-container.card {
        background-color: var(--muted);
        position: relative;
        overflow: hidden;
    }

    .auth-container.card::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image:
            radial-gradient(circle at 20% 50%, rgba(17, 24, 39, 0.03) 0%, transparent 50%),
            radial-gradient(circle at 80% 80%, rgba(17, 24, 39, 0.03) 0%, transparent 50%),
            radial-gradient(circle at 40% 20%, rgba(17, 24, 39, 0.02) 0%, transparent 50%);
        background-size: 100% 100%;
    }

    html.dark .auth-container.card::before {
        background-image:
            radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.03) 0%, transparent 50%),
            radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.03) 0%, transparent 50%),
            radial-gradient(circle at 40% 20%, rgba(255, 255, 255, 0.02) 0%, transparent 50%);
    }

    .auth-container.card .form-panel {
        position: relative;
        z-index: 10;
    }

    .auth-container.card .form-card {
        background: var(--background);
        border: 1px solid var(--border);
        border-radius: 1.25rem;
        padding: 3rem 2.5rem;
        box-shadow:
            0 4px 6px -1px rgba(0, 0, 0, 0.05),
            0 10px 15px -3px rgba(0, 0, 0, 0.05),
            0 20px 25px -5px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .auth-container.card .form-card:hover {
        transform: translateY(-4px);
        box-shadow:
            0 10px 15px -3px rgba(0, 0, 0, 0.1),
            0 20px 25px -5px rgba(0, 0, 0, 0.1),
            0 30px 35px -7px rgba(0, 0, 0, 0.1);
    }

    html.dark .auth-container.card .form-card {
        box-shadow:
            0 4px 6px -1px rgba(0, 0, 0, 0.3),
            0 10px 15px -3px rgba(0, 0, 0, 0.3),
            0 20px 25px -5px rgba(0, 0, 0, 0.3);
    }

    html.dark .auth-container.card .form-card:hover {
        box-shadow:
            0 10px 15px -3px rgba(0, 0, 0, 0.5),
            0 20px 25px -5px rgba(0, 0, 0, 0.5),
            0 30px 35px -7px rgba(0, 0, 0, 0.5);
    }

    /* Loading State */
    .btn.loading {
        position: relative;
        color: transparent;
    }

    .btn.loading::after {
        content: '';
        position: absolute;
        width: 1.25rem;
        height: 1.25rem;
        border: 2px solid transparent;
        border-top-color: currentColor;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    html.dark .btn.loading::after {
        border-top-color: #111827;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>