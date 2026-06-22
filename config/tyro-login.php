<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Developer Debug Mode
    |--------------------------------------------------------------------------
    |
    | SECURITY WARNING: Never enable in production!
    |
    | When enabled, sensitive information is logged to help with development:
    | - OTP codes and verification tokens
    | - Password reset links
    | - Email verification URLs
    | - User authentication attempts
    |
    | This data is logged to storage/logs/laravel.log and should ONLY be
    | used in local development environments.
    |
    | Environment: TYRO_LOGIN_DEBUG=true
    |
    */
    'debug' => env('TYRO_LOGIN_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Package Version
    |--------------------------------------------------------------------------
    */
    //     'version' => '2.7.2',

    /*
    |--------------------------------------------------------------------------
    | Authentication Page Layout
    |--------------------------------------------------------------------------
    |
    | Choose from 5 stunning pre-designed layouts for your auth pages:
    |
    | 'centered' (Default)
    |    Clean, minimal design with the form centered on screen
    |    Perfect for: SaaS apps, simple forms, mobile-first design
    |
    | 'split-left'
    |    Two-column layout: background image left, form right
    |    Perfect for: Marketing sites, showcasing product imagery
    |
    | 'split-right'
    |    Two-column layout: form left, background image right
    |    Perfect for: Content-heavy sites, storytelling layouts
    |
    | 'fullscreen'
    |    Immersive full-screen background with glassmorphism form overlay
    |    Perfect for: Landing pages, premium applications, portfolios
    |
    | 'card'
    |    Floating card design with subtle background patterns
    |    Perfect for: Modern web apps, enterprise dashboards
    |
    | 'youtube-video'
    |    Centered form with a full-screen YouTube video background
    |    Perfect for: Creative portfolios, video-centric brands, modern showcases
    |
    | 'animated-birds'
    |    Centered form with a full-screen animated flock of birds background
    |    Perfect for: Creative brands, nature-inspired apps, ambient landing pages
    |
    | 'aurora-waves'
    |    Centered form with flowing aurora-like gradient ribbons in the background
    |    Perfect for: Premium brands, creative agencies, atmospheric product pages
    |
    | 'particle-network'
    |    Centered form with a connected particle constellation background
    |    Perfect for: SaaS, developer tools, tech products
    |
    | 'tidal'
    |    Centered form with a bright seascape of flowing waves and bubbles
    |    Perfect for: Wellness, marine/nature apps, calm creative brands
    |
    | Environment: TYRO_LOGIN_LAYOUT=centered
    |
    */
    'layout' => env('TYRO_LOGIN_LAYOUT', 'centered'),

    /*
    |--------------------------------------------------------------------------
    | Background Image
    |--------------------------------------------------------------------------
    |
    | The background image URL for split layouts.
    | This can be a local path or an external URL.
    |
    */
    'background_image' => env('TYRO_LOGIN_BACKGROUND_IMAGE', 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=1920&q=80'),

    /*
    |--------------------------------------------------------------------------
    | YouTube Video Background
    |--------------------------------------------------------------------------
    |
    | Used when 'layout' is set to 'youtube-video'.
    | The video plays muted in the background with configurable blur and overlay.
    |
    | Note: the background is embedded via youtube-nocookie.com (privacy-enhanced
    | mode) so no tracking cookies are set until playback begins. As a trade-off,
    | the video will not appear in a viewer's YouTube watch history.
    |
    | 'sound' => true requests audio, but browsers may block autoplay-with-audio until
    | the user has interacted with the page, so muted playback (the default) is the
    | only reliably working option for a background video in that case.
    |
    | Environment: TYRO_LOGIN_VIDEO_URL, TYRO_LOGIN_VIDEO_BLUR,
    |              TYRO_LOGIN_VIDEO_OVERLAY_COLOR, TYRO_LOGIN_VIDEO_OVERLAY_OPACITY
    |
    */
    'video_background' => [
        // YouTube video URL or ID (e.g. 'https://www.youtube.com/watch?v=dQw4w9WgXcQ')
        'url' => env('TYRO_LOGIN_VIDEO_URL', 'https://www.youtube.com/watch?v=JFMY5NxXDhw&t=10'),

        // CSS blur amount (e.g. '0px', '4px', '8px')
        'blur' => env('TYRO_LOGIN_VIDEO_BLUR', '0px'),

        // Overlay color (any valid CSS color)
        'overlay_color' => env('TYRO_LOGIN_VIDEO_OVERLAY_COLOR', '#111827'),

        // Overlay opacity (0 to 1, applied on top of the color)
        'overlay_opacity' => env('TYRO_LOGIN_VIDEO_OVERLAY_OPACITY', 0.1),

        // Whether to play video with sound (true = sound on, false = muted)
        'sound' => env('TYRO_LOGIN_VIDEO_SOUND', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Animated Birds Background
    |--------------------------------------------------------------------------
    |
    | Used when 'layout' is set to 'animated-birds'.
    |
    | A single color drives the whole scene: it becomes the sky/background
    | for the canvas animation and the tint of the frosted glass form card.
    |
    | Any valid CSS color works (hex, rgb, hsl, named).
    |
    | Environment: TYRO_LOGIN_BIRDS_COLOR
    |
    */
    'animated_birds' => [
        'color' => env('TYRO_LOGIN_BIRDS_COLOR', '#f7f2ec'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Aurora Waves Background
    |--------------------------------------------------------------------------
    |
    | Used when 'layout' is set to 'aurora-waves'.
    |
    | Soft, flowing gradient ribbons drifting across a dark sky. The primary
    | color drives both the background and the frosted form glass tint.
    |
    | Environment: TYRO_LOGIN_AURORA_COLOR, TYRO_LOGIN_AURORA_SPEED,
    |              TYRO_LOGIN_AURORA_INTENSITY
    |
    */
    'aurora_waves' => [
        // Base color (dark recommended). Any valid CSS color.
        'color' => env('TYRO_LOGIN_AURORA_COLOR', '#0b1020'),

        // Animation speed multiplier (0.5 = slow, 2 = fast)
        'speed' => env('TYRO_LOGIN_AURORA_SPEED', 1),

        // Ribbon opacity/intensity (0 = faint, 1 = vivid)
        'intensity' => env('TYRO_LOGIN_AURORA_INTENSITY', 0.5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Particle Network Background
    |--------------------------------------------------------------------------
    |
    | Used when 'layout' is set to 'particle-network'.
    |
    | Floating nodes connected by faint lines (classic "connected tech" look),
    | with optional mouse interaction. The primary color drives the background.
    |
    | Environment: TYRO_LOGIN_PARTICLE_COLOR, TYRO_LOGIN_PARTICLE_DENSITY,
    |              TYRO_LOGIN_PARTICLE_LINK_DISTANCE, TYRO_LOGIN_PARTICLE_INTERACTIVE
    |
    */
    'particle_network' => [
        // Base color (dark recommended). Any valid CSS color.
        'color' => env('TYRO_LOGIN_PARTICLE_COLOR', '#0f172a'),

        // Particle density (approx count on a 1280x720 viewport; scales with area)
        'density' => env('TYRO_LOGIN_PARTICLE_DENSITY', 80),

        // Max distance (px) at which two nodes are linked by a line
        'link_distance' => env('TYRO_LOGIN_PARTICLE_LINK_DISTANCE', 130),

        // Whether nodes react to the mouse cursor (repel + link)
        'interactive' => env('TYRO_LOGIN_PARTICLE_INTERACTIVE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tidal Background
    |--------------------------------------------------------------------------
    |
    | Used when 'layout' is set to 'tidal'.
    |
    | A bright seascape: layered flowing waves and rising bubbles under a soft
    | sky glow. The primary color drives the water (and sky/bubble tint).
    |
    | Environment: TYRO_LOGIN_TIDAL_COLOR, TYRO_LOGIN_TIDAL_SPEED,
    |              TYRO_LOGIN_TIDAL_BUBBLES
    |
    */
    'tidal' => [
        // Primary water color. Any valid CSS color.
        'color' => env('TYRO_LOGIN_TIDAL_COLOR', '#1f7a8c'),

        // Wave animation speed multiplier (0.5 = slow, 2 = fast)
        'speed' => env('TYRO_LOGIN_TIDAL_SPEED', 1),

        // Show rising bubbles (true/false)
        'bubbles' => env('TYRO_LOGIN_TIDAL_BUBBLES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Brand Identity & Customization
    |--------------------------------------------------------------------------
    |
    | 🎨 Customize the visual identity of your authentication pages
    | Make them feel like they're truly part of your application
    |
    | Pro Tips:
    | - Use high-resolution logos (minimum 200px height recommended)
    | - SVG logos work best for crisp scaling across devices
    | - Keep app names concise for better mobile display
    |
    */
    'branding' => [
        // Your application name shown on all auth pages
        // Defaults to APP_NAME from Laravel config, or 'Laravel' if not set
        'app_name' => env('TYRO_LOGIN_APP_NAME', env('APP_NAME', 'Laravel')),

        // Logo URL (recommended: SVG or high-res PNG)
        // Set to null to use text-based logo with app name
        // Example: 'https://yourapp.com/logo.svg' or '/images/logo.png'
        'logo' => env('TYRO_LOGIN_LOGO', null),

        // Dark mode logo URL (falls back to the light logo when not set)
        // Example: 'https://yourapp.com/logo-dark.svg' or '/images/logo-dark.png'
        'logo_dark' => env('TYRO_LOGIN_LOGO_DARK', null),

        // Logo height for proper display scaling
        // Accepts any valid CSS height value (px, rem, etc.)
        // Common values: '32px', '48px', '3rem'
        'logo_height' => env('TYRO_LOGIN_LOGO_HEIGHT', '48px'),

        // Logo border radius for rounded logos (e.g. '50%' for full circle, '8px' for rounded corners)
        // Default is '0' to maintain backward compatibility
        'logo_border_radius' => env('TYRO_LOGIN_LOGO_BORDER_RADIUS', '0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Settings
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'prefix' => env('TYRO_LOGIN_ROUTE_PREFIX', ''),
        'middleware' => ['web'],
        'login' => 'login',
        'logout' => 'logout',
        'register' => 'register',
    ],

    /*
    |--------------------------------------------------------------------------
    | Redirects
    |--------------------------------------------------------------------------
    |
    | Configure where users should be redirected after various actions.
    |
    */
    'redirects' => [
        'after_login' => env('TYRO_LOGIN_REDIRECT_AFTER_LOGIN', '/'),
        'after_logout' => env('TYRO_LOGIN_REDIRECT_AFTER_LOGOUT', '/login'),
        'after_register' => env('TYRO_LOGIN_REDIRECT_AFTER_REGISTER', '/'),
        'after_email_verification' => env('TYRO_LOGIN_REDIRECT_AFTER_EMAIL_VERIFICATION', '/login'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Registration Settings
    |--------------------------------------------------------------------------
    */
    'registration' => [
        // Whether registration is enabled
        'enabled' => env('TYRO_LOGIN_REGISTRATION_ENABLED', true),

        // Whether to automatically log in the user after registration
        'auto_login' => env('TYRO_LOGIN_REGISTRATION_AUTO_LOGIN', true),

        // Whether to require email verification after registration
        'require_email_verification' => env('TYRO_LOGIN_REQUIRE_EMAIL_VERIFICATION', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tyro Integration
    |--------------------------------------------------------------------------
    |
    | If hasinhayder/tyro is installed, these settings control the integration.
    |
    */
    'tyro' => [
        // Whether to assign a default role to new users
        'assign_default_role' => env('TYRO_LOGIN_ASSIGN_DEFAULT_ROLE', true),

        // The default role slug to assign to new users
        'default_role_slug' => env('TYRO_LOGIN_DEFAULT_ROLE_SLUG', 'user'),
    ],

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    */
    'user_model' => env('TYRO_LOGIN_USER_MODEL', 'App\\Models\\User'),

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    */
    'features' => [
        // Show "Remember Me" checkbox on login form
        'remember_me' => env('TYRO_LOGIN_REMEMBER_ME', true),

        // Show "Forgot Password" link on login form
        'forgot_password' => env('TYRO_LOGIN_FORGOT_PASSWORD', true),

        // Enable magic link feature
        // Environment: TYRO_LOGIN_ENABLE_MAGIC_LINKS=true
        'magic_links_enabled' => env('TYRO_LOGIN_ENABLE_MAGIC_LINKS', false),

        // Disable password-based login entirely
        // Hides password field, remember me, forgot password link, and "or" separator
        // Useful when only passkey or magic link login is desired
        'disable_password' => env('TYRO_LOGIN_DISABLE_PASSWORD', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Passkeys (WebAuthn)
    |--------------------------------------------------------------------------
    |
    | Passwordless login using passkeys via the `laravel/passkeys` package.
    |
    | Requires `composer require laravel/passkeys`, publishing the passkeys
    | migration, and adding the PasskeyAuthenticatable trait to your User model.
    | The fastest way to set all of this up is:
    |
    |     php artisan tyro-login:install --with-passkeys
    |
    | The browser-side ceremony is handled by the `@laravel/passkeys` client,
    | which is auto-loaded from `cdn_url` (no build step required). Override
    | `cdn_url` to self-host the client.
    |
    */
    'passkeys' => [
        // Master switch. Also requires `composer require laravel/passkeys`.
        // Environment: TYRO_LOGIN_PASSKEYS_ENABLED=true
        'enabled' => env('TYRO_LOGIN_PASSKEYS_ENABLED', false),

        // Divider shown under the passkey button on the login page
        'divider_text' => env('TYRO_LOGIN_PASSKEYS_DIVIDER', 'or continue with email'),

        // Login button label
        'login_button_text' => env('TYRO_LOGIN_PASSKEYS_LOGIN_BUTTON', 'Sign in with a passkey'),

        // Setup page texts
        'setup_title' => env('TYRO_LOGIN_PASSKEYS_SETUP_TITLE', 'Create a Passkey'),
        'setup_subtitle' => env('TYRO_LOGIN_PASSKEYS_SETUP_SUBTITLE', 'Set up a passkey for faster, passwordless sign-in.'),
        'setup_button_text' => env('TYRO_LOGIN_PASSKEYS_SETUP_BUTTON', 'Create passkey'),

        // Path of the auth-guarded setup page (relative to the route prefix)
        'route' => env('TYRO_LOGIN_PASSKEYS_ROUTE', 'passkeys-setup'),

        // Path of the auth-guarded manage/remove page (relative to the route prefix)
        'remove_route' => env('TYRO_LOGIN_PASSKEYS_REMOVE_ROUTE', 'remove-passkeys'),

        // Manage/remove page texts
        'remove_title' => env('TYRO_LOGIN_PASSKEYS_REMOVE_TITLE', 'Your Passkeys'),
        'remove_subtitle' => env('TYRO_LOGIN_PASSKEYS_REMOVE_SUBTITLE', 'Review and remove the passkeys linked to your account.'),
        'remove_button_text' => env('TYRO_LOGIN_PASSKEYS_REMOVE_BUTTON', 'Remove'),
        'empty_text' => env('TYRO_LOGIN_PASSKEYS_EMPTY_TEXT', 'You don\'t have any passkeys yet.'),

        // ESM URL for the @laravel/passkeys browser client. Override to self-host.
        'cdn_url' => env('TYRO_LOGIN_PASSKEYS_CDN', 'https://esm.sh/@laravel/passkeys@0.2.0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Rules
    |--------------------------------------------------------------------------
    |
    | Password requirements for registration. Configure complexity rules,
    | length requirements, and other validation settings.
    |
    */
    'password' => [
        // Minimum password length
        'min_length' => env('TYRO_LOGIN_PASSWORD_MIN_LENGTH', 8),

        // Maximum password length (null for no limit)
        'max_length' => env('TYRO_LOGIN_PASSWORD_MAX_LENGTH', null),

        // Require password confirmation
        'require_confirmation' => env('TYRO_LOGIN_PASSWORD_REQUIRE_CONFIRMATION', true),

        // Password complexity requirements
        'complexity' => [
            // Require at least one uppercase letter
            'require_uppercase' => env('TYRO_LOGIN_PASSWORD_REQUIRE_UPPERCASE', false),

            // Require at least one lowercase letter
            'require_lowercase' => env('TYRO_LOGIN_PASSWORD_REQUIRE_LOWERCASE', false),

            // Require at least one number
            'require_numbers' => env('TYRO_LOGIN_PASSWORD_REQUIRE_NUMBERS', false),

            // Require at least one special character
            'require_special_chars' => env('TYRO_LOGIN_PASSWORD_REQUIRE_SPECIAL_CHARS', false),

        ],

        // Common password validation
        'check_common_passwords' => env('TYRO_LOGIN_PASSWORD_CHECK_COMMON', false),

        // Disallow user information in password (email, name parts)
        'disallow_user_info' => env('TYRO_LOGIN_PASSWORD_DISALLOW_USER_INFO', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Login Field
    |--------------------------------------------------------------------------
    |
    | The field used for login. Options: 'email', 'username', 'both'
    |
    */
    'login_field' => env('TYRO_LOGIN_FIELD', 'email'),

    /*
    |--------------------------------------------------------------------------
    | Page Content
    |--------------------------------------------------------------------------
    |
    | Configure the content displayed on different pages.
    |
    */
    'pages' => [
        'login' => [
            'background_title' => env('TYRO_LOGIN_BG_TITLE', 'Welcome Back!'),
            'background_description' => env('TYRO_LOGIN_BG_DESCRIPTION', 'Sign in to access your account and continue where you left off. We\'re glad to see you again.'),
        ],
        'register' => [
            'background_title' => env('TYRO_LOGIN_REGISTER_BG_TITLE', 'Join Us Today!'),
            'background_description' => env('TYRO_LOGIN_REGISTER_BG_DESCRIPTION', 'Create your account and start your journey with us. It only takes a minute to get started.'),
        ],
        'verify_email' => [
            'title' => env('TYRO_LOGIN_VERIFY_EMAIL_TITLE', 'Verify Your Email'),
            'subtitle' => env('TYRO_LOGIN_VERIFY_EMAIL_SUBTITLE', 'We\'ve sent a verification link to your email address.'),
            'background_title' => env('TYRO_LOGIN_VERIFY_EMAIL_BG_TITLE', 'Check Your Email'),
            'background_description' => env('TYRO_LOGIN_VERIFY_EMAIL_BG_DESCRIPTION', 'We\'ve sent a verification link to your email address. Click the link to verify your account.'),
        ],
        'email_not_verified' => [
            'title' => env('TYRO_LOGIN_EMAIL_NOT_VERIFIED_TITLE', 'Email Not Verified'),
            'subtitle' => env('TYRO_LOGIN_EMAIL_NOT_VERIFIED_SUBTITLE', 'Please verify your email address to continue.'),
            'background_title' => env('TYRO_LOGIN_EMAIL_NOT_VERIFIED_BG_TITLE', 'Email Verification Required'),
            'background_description' => env('TYRO_LOGIN_EMAIL_NOT_VERIFIED_BG_DESCRIPTION', 'Your email address needs to be verified before you can access your account.'),
        ],
        'forgot_password' => [
            'title' => env('TYRO_LOGIN_FORGOT_PASSWORD_TITLE', 'Forgot Password?'),
            'subtitle' => env('TYRO_LOGIN_FORGOT_PASSWORD_SUBTITLE', 'Enter your email and we\'ll send you a reset link.'),
            'background_title' => env('TYRO_LOGIN_FORGOT_PASSWORD_BG_TITLE', 'Forgot Your Password?'),
            'background_description' => env('TYRO_LOGIN_FORGOT_PASSWORD_BG_DESCRIPTION', 'No worries! Enter your email and we\'ll send you a link to reset your password.'),
        ],
        'reset_password' => [
            'title' => env('TYRO_LOGIN_RESET_PASSWORD_TITLE', 'Reset Password'),
            'subtitle' => env('TYRO_LOGIN_RESET_PASSWORD_SUBTITLE', 'Enter your new password below.'),
            'background_title' => env('TYRO_LOGIN_RESET_PASSWORD_BG_TITLE', 'Reset Your Password'),
            'background_description' => env('TYRO_LOGIN_RESET_PASSWORD_BG_DESCRIPTION', 'Create a new secure password for your account.'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Verification Settings
    |--------------------------------------------------------------------------
    |
    | Configure email verification token expiration time.
    |
    */
    'verification' => [
        // Token expiration time in minutes
        'expire' => (int) env('TYRO_LOGIN_VERIFICATION_EXPIRE', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Reset Settings
    |--------------------------------------------------------------------------
    |
    | Configure password reset token expiration time.
    |
    */
    'password_reset' => [
        // Token expiration time in minutes
        'expire' => (int) env('TYRO_LOGIN_PASSWORD_RESET_EXPIRE', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Math Captcha Settings
    |--------------------------------------------------------------------------
    |
    | When enabled, a simple math captcha (addition/subtraction) will be shown
    | on the login and/or registration forms. This helps prevent automated
    | submissions without requiring external services.
    |
    */
    'captcha' => [
        // Whether captcha is enabled on login form
        'enabled_login' => env('TYRO_LOGIN_CAPTCHA_LOGIN', false),

        // Whether captcha is enabled on registration form
        'enabled_register' => env('TYRO_LOGIN_CAPTCHA_REGISTER', false),

        // Captcha label text
        'label' => env('TYRO_LOGIN_CAPTCHA_LABEL', 'Security Check'),

        // Captcha placeholder text
        'placeholder' => env('TYRO_LOGIN_CAPTCHA_PLACEHOLDER', 'Enter the answer'),

        // Error message when captcha is incorrect
        'error_message' => env('TYRO_LOGIN_CAPTCHA_ERROR', 'Incorrect answer. Please try again.'),

        // Minimum number for math operation
        'min_number' => env('TYRO_LOGIN_CAPTCHA_MIN', 1),

        // Maximum number for math operation
        'max_number' => env('TYRO_LOGIN_CAPTCHA_MAX', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Login OTP Settings
    |--------------------------------------------------------------------------
    |
    | When enabled, users will receive a one-time password via email after
    | entering valid credentials. They must enter the OTP to complete login.
    | The OTP is stored in cache (no database required).
    |
    */
    'otp' => [
        // Whether OTP verification is enabled for login
        'enabled' => env('TYRO_LOGIN_OTP_ENABLED', false),

        // Number of digits in the OTP (4-8)
        'length' => env('TYRO_LOGIN_OTP_LENGTH', 4),

        // OTP expiration time in minutes
        'expire' => (int) env('TYRO_LOGIN_OTP_EXPIRE', 5),

        // Maximum OTP resend attempts
        'max_resend' => env('TYRO_LOGIN_OTP_MAX_RESEND', 3),

        // Cooldown between resends in seconds
        'resend_cooldown' => env('TYRO_LOGIN_OTP_RESEND_COOLDOWN', 60),

        // Page title
        'title' => env('TYRO_LOGIN_OTP_TITLE', 'Enter Verification Code'),

        // Page subtitle (supports :email placeholder)
        'subtitle' => env('TYRO_LOGIN_OTP_SUBTITLE', 'We\'ve sent a :length-digit code to :email'),

        // Input label
        'label' => env('TYRO_LOGIN_OTP_LABEL', 'Verification Code'),

        // Input placeholder
        'placeholder' => env('TYRO_LOGIN_OTP_PLACEHOLDER', 'Enter code'),

        // Submit button text
        'submit_button' => env('TYRO_LOGIN_OTP_SUBMIT_BUTTON', 'Verify'),

        // Resend button text
        'resend_button' => env('TYRO_LOGIN_OTP_RESEND_BUTTON', 'Resend Code'),

        // Error message when OTP is incorrect
        'error_message' => env('TYRO_LOGIN_OTP_ERROR', 'Invalid or expired verification code.'),

        // Success message when OTP is resent
        'resend_success' => env('TYRO_LOGIN_OTP_RESEND_SUCCESS', 'A new verification code has been sent to your email.'),

        // Error message when max resends reached
        'max_resend_error' => env('TYRO_LOGIN_OTP_MAX_RESEND_ERROR', 'Maximum resend attempts reached. Please try logging in again.'),

        // Background title (for split layouts)
        'background_title' => env('TYRO_LOGIN_OTP_BG_TITLE', 'Almost There!'),

        // Background description (for split layouts)
        'background_description' => env('TYRO_LOGIN_OTP_BG_DESCRIPTION', 'Enter the verification code we sent to your email to complete the login process.'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Two Factor Authentication (2FA)
    |--------------------------------------------------------------------------
    |
    | Secure your account with TOTP based two-factor authentication.
    | Compatible with Google Authenticator, Authy, etc.
    |
    */
    'two_factor' => [
        // Enable/disable 2FA globally
        'enabled' => env('TYRO_LOGIN_2FA_ENABLED', false),

        // Page title for setup
        'setup_title' => env('TYRO_LOGIN_2FA_SETUP_TITLE', 'Two Factor Authentication'),

        // Page subtitle for setup
        'setup_subtitle' => env('TYRO_LOGIN_2FA_SETUP_SUBTITLE', 'Scan the QR code with your authenticator app.'),

        // Page title for challenge
        'challenge_title' => env('TYRO_LOGIN_2FA_CHALLENGE_TITLE', 'Two Factor Authentication'),

        // Challenge subtitle
        'challenge_subtitle' => env('TYRO_LOGIN_2FA_CHALLENGE_SUBTITLE', 'Enter the code from your authenticator app.'),

        // Allow user to skip 2FA setup
        'allow_skip' => env('TYRO_LOGIN_2FA_ALLOW_SKIP', false),

        // Number of days the "skip and ignore" cookie lasts
        'ignore_cookie_days' => env('TYRO_LOGIN_2FA_IGNORE_COOKIE_DAYS', 30),

        // Comma-separated roles that must set up 2FA and cannot skip (e.g. "admin,superadmin")
        'forced_roles' => env('TYRO_LOGIN_2FA_FORCED_ROLES', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Settings
    |--------------------------------------------------------------------------
    |
    | Configure email sending for various authentication actions.
    | Each email type can be individually enabled or disabled.
    | Email templates can be customized by publishing them.
    |
    */
    'emails' => [
        // OTP verification email
        'otp' => [
            'enabled' => env('TYRO_LOGIN_EMAIL_OTP', true),
            'subject' => env('TYRO_LOGIN_EMAIL_OTP_SUBJECT', 'Your Verification Code'),
        ],

        // Password reset email
        'password_reset' => [
            'enabled' => env('TYRO_LOGIN_EMAIL_PASSWORD_RESET', true),
            'subject' => env('TYRO_LOGIN_EMAIL_PASSWORD_RESET_SUBJECT', 'Reset Your Password'),
        ],

        // Email verification email
        'verify_email' => [
            'enabled' => env('TYRO_LOGIN_EMAIL_VERIFY', true),
            'subject' => env('TYRO_LOGIN_EMAIL_VERIFY_SUBJECT', 'Verify Your Email Address'),
        ],

        // Welcome email after registration
        'welcome' => [
            'enabled' => env('TYRO_LOGIN_EMAIL_WELCOME', true),
            'subject' => env('TYRO_LOGIN_EMAIL_WELCOME_SUBJECT', null), // null = uses default with app name
        ],

        // Magic link email
        'magic_link' => [
            'enabled' => env('TYRO_LOGIN_EMAIL_MAGIC_LINK', true),
            'subject' => env('TYRO_LOGIN_EMAIL_MAGIC_LINK_SUBJECT', 'Your Magic Login Link'),
            'expire' => (int) env('TYRO_LOGIN_MAGIC_LINK_EXPIRE', 5), // Minutes
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Login (OAuth)
    |--------------------------------------------------------------------------
    |
    | Enable social login using Laravel Socialite. Users can authenticate
    | using their social media accounts. Requires laravel/socialite package.
    |
    | Supported providers: google, facebook, github, twitter, linkedin, bitbucket, gitlab, slack
    |
    | Each provider requires credentials in config/services.php:
    | 'github' => [
    |     'client_id' => env('GITHUB_CLIENT_ID'),
    |     'client_secret' => env('GITHUB_CLIENT_SECRET'),
    |     'redirect' => env('GITHUB_REDIRECT_URI'),
    | ],
    |
    */
    'social' => [
        // Enable/disable social login globally
        'enabled' => env('TYRO_LOGIN_SOCIAL_ENABLED', false),

        // Available providers (enable/disable each individually)
        'providers' => [
            'google' => [
                'enabled' => env('TYRO_LOGIN_SOCIAL_GOOGLE', false),
                'label' => 'Google',
                'icon' => 'google', // Icon identifier for the button
            ],
            'facebook' => [
                'enabled' => env('TYRO_LOGIN_SOCIAL_FACEBOOK', false),
                'label' => 'Facebook',
                'icon' => 'facebook',
            ],
            'github' => [
                'enabled' => env('TYRO_LOGIN_SOCIAL_GITHUB', false),
                'label' => 'GitHub',
                'icon' => 'github',
            ],
            'twitter' => [
                'enabled' => env('TYRO_LOGIN_SOCIAL_TWITTER', false),
                'label' => 'X',
                'icon' => 'twitter',
            ],
            'linkedin' => [
                'enabled' => env('TYRO_LOGIN_SOCIAL_LINKEDIN', false),
                'label' => 'LinkedIn',
                'icon' => 'linkedin',
            ],
            'bitbucket' => [
                'enabled' => env('TYRO_LOGIN_SOCIAL_BITBUCKET', false),
                'label' => 'Bitbucket',
                'icon' => 'bitbucket',
            ],
            'gitlab' => [
                'enabled' => env('TYRO_LOGIN_SOCIAL_GITLAB', false),
                'label' => 'GitLab',
                'icon' => 'gitlab',
            ],
            'slack' => [
                'enabled' => env('TYRO_LOGIN_SOCIAL_SLACK', false),
                'label' => 'Slack',
                'icon' => 'slack',
            ],
        ],

        // Allow users to link social accounts to existing accounts (matched by email)
        'link_existing_accounts' => env('TYRO_LOGIN_SOCIAL_LINK_EXISTING', true),

        // Automatically create new user if social email doesn't exist
        'auto_register' => env('TYRO_LOGIN_SOCIAL_AUTO_REGISTER', true),

        // Text displayed above social login buttons
        'divider_text' => env('TYRO_LOGIN_SOCIAL_DIVIDER', 'Or continue with'),

        // Automatically verify user email after social login/register
        // Social providers confirm email ownership, so we can trust the email
        'auto_verify_email' => env('TYRO_LOGIN_SOCIAL_AUTO_VERIFY_EMAIL', true),

        // Error messages
        'messages' => [
            'account_not_found' => 'No account found with this email. Please register first.',
            'email_required' => 'Email address is required for social login.',
            'provider_error' => 'An error occurred with the social login provider.',
            'account_disabled' => 'Social login is not available for your account.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Lockout Protection
    |--------------------------------------------------------------------------
    |
    | Protect your application from brute-force attacks
    |
    | Automatically locks out users after too many failed login attempts.
    | This prevents automated bots and attackers from guessing passwords.
    |
    | Technical Details:
    | - Uses Laravel Cache (Redis, Memcached, or file-based)
    | - No database modifications required
    | - Automatically cleans up expired lockouts
    | - IP-based tracking for anonymous users
    |
    | Best Practices:
    | - 3-5 attempts for standard apps, 10+ for admin areas
    | - 15-30 minutes lockout for production, 2-5 for development
    | - Consider longer lockouts for sensitive applications
    |
    */
    'lockout' => [
        // Enable/disable the entire lockout system
        // Set to false only during development/testing
        'enabled' => env('TYRO_LOGIN_LOCKOUT_ENABLED', true),

        // Maximum failed login attempts before lockout
        // Recommended: 3-5 for public apps, 10+ for admin panels
        'max_attempts' => env('TYRO_LOGIN_LOCKOUT_MAX_ATTEMPTS', 5),

        // Lockout duration in minutes
        // Recommended: 15-30 for production, 2-5 for development
        'duration_minutes' => env('TYRO_LOGIN_LOCKOUT_DURATION', 15),

        // Show remaining attempts in error messages
        // Helps users understand how many tries they have left
        'show_attempts_left' => env('TYRO_LOGIN_SHOW_ATTEMPTS_LEFT', false),

        // Automatically redirect to login when lockout expires
        // Set to false if you want users to manually navigate back
        'auto_redirect' => env('TYRO_LOGIN_LOCKOUT_AUTO_REDIRECT', true),

        // Lockout page message (supports :minutes placeholder)
        'message' => env('TYRO_LOGIN_LOCKOUT_MESSAGE', 'Too many failed login attempts. Please try again in :minutes minutes.'),

        // Lockout page title
        'title' => env('TYRO_LOGIN_LOCKOUT_TITLE', 'Account Temporarily Locked'),

        // Lockout page subtitle/explanation
        'subtitle' => env('TYRO_LOGIN_LOCKOUT_SUBTITLE', 'For your security, we\'ve temporarily locked your account.'),
    ],
];
