# Changelog

All notable changes to `tyro-login` will be documented in this file.

## [2.12.0] - 2026-06-23

### Added

-   **Passkeys (WebAuthn) Passwordless Login** - FIDO2/WebAuthn passwordless authentication using Laravel's native passkeys package
    -   Passwordless login with biometrics, PIN, or security keys
    -   Passkey setup and management interface
    -   Compatible with Laravel's first-party passkeys package

## [2.11.1] - 2026-06-21

### Changed

-   Updated default YouTube video background URL

## [2.11.0] - 2026-06-21

### Added

-   **Tidal Background** - New `tidal` layout option featuring an animated underwater/wave-inspired background for authentication pages

## [2.10.0] - 2026-06-21

### Added

-   **Premium Layouts** - Added three stunning, premium layouts for auth pages:
    -   `animated-birds`: Translucent glassmorphism form card over a self-contained, interactive canvas flock of birds.
    -   `aurora-waves`: Ambient floating aurora-like ribbon animations on a dark canvas.
    -   `particle-network`: High-tech connected node constellation network with interactive mouse tracking.
-   **Branding Adjustments** - Added a global `logo_border_radius` option to the config to allow setting custom border radius (e.g. `'50%'` for fully circular logos) without breaking backward compatibility.

## [2.9.2] - 2026-06-19

### Changed

-   YouTube video background: removed deprecated embed params (`showinfo`, `modestbranding`)
-   Documented `youtube-nocookie` privacy tradeoff and sound/autoplay limitation

## [2.9.1] - 2026-06-19

### Fixed

-   PHP 8.4 compatibility: use explicit nullable type for `SocialAuthController::handlePostLoginRedirect` fallback parameter

## [2.9.0] - 2026-06-19

### Added

-   **YouTube Video Background** - New layout option with configurable YouTube video as authentication page background
    -   Configurable blur, overlay color, opacity, and audio settings

## [2.8.2] - 2026-06-05

### Fixed

-   Corrected broken GitHub Pages links in documentation

## [2.8.1] - 2026-06-01

### Added

-   **AI Skill Rules** - Added 6 new AI skill rule files:
    -   Captcha, invitation, password-policy, registration, verification, password-reset
-   Aligned all existing skill rules with actual codebase patterns

## [2.8.0] - 2026-06-01

### Added

-   **AI Skill Feature** - New AI-powered skill system for Tyro Login package maintainers to streamline development workflows

## [2.7.1] - 2026-05-18

### Fixed

-   2FA setup routes duplication for Tyro-Dashboard workflow
-   Expired magic links now show an error banner on login instead of silently failing
-   Passwordless mode subtitle display fix

## [2.7.0] - 2026-04-25

### Added

-   **Passwordless Mode** - Completely disable password-based login via `TYRO_LOGIN_DISABLE_PASSWORD=true`
-   **Auto-Submit OTP & 2FA** - OTP and 2FA forms now auto-submit after filling the last digit for a seamless experience

### Fixed

-   Magic link and password reset button text visibility issues

## [2.6.1] - 2026-04-21

### Fixed

-   Suspended users can no longer log in

## [2.6.0] - 2026-04-19

### Added

-   Dark-mode logo support for authentication pages

## [2.5.0] - 2026-04-14

### Added

-   `tyro-login:update-config` Artisan command for updating configuration
-   `tyro-login:update-style` Artisan command for updating style files

## [2.4.3] - 2026-04-13

### Fixed

-   2FA checks now properly apply to social login and magic link login flows

## [2.4.2] - 2026-03-24

### Added

-   **Forced 2FA by Role** - Option to force specific user roles to set up 2FA via `TYRO_LOGIN_2FA_FORCED_ROLES` config
    -   Requires 2FA for high-risk accounts while allowing others to skip it

## [2.4.1] - 2026-03-24

### Added

-   **2FA Skip Option** - Users can now skip and ignore 2FA setup for a configurable number of days
    -   Cookie-based mechanism remembers their choice
    -   Configurable via `TYRO_LOGIN_2FA_ALLOW_SKIP`

## [2.4.0] - 2026-03-18

### Added

-   Laravel 13 support

## [2.3.4] - 2026-03-15

### Fixed

-   All tests passing consistently

## [2.3.3] - 2026-03-15

### Fixed

-   OTP code generation improvement with proper integer type casting for config values

## [2.3.2] - 2026-03-15

### Fixed

-   Social login now automatically creates an account if the user doesn't exist

## [2.3.1] - 2026-02-06

### Fixed

-   Referral tracking logic bug fix

## [2.3.0] - 2026-02-05

### Added

-   **Invitation/Referral System** - Complete invitation and referral link management
    -   Automatic referral tracking during registration
    -   CLI commands for managing invitation links
    -   Models for data persistence

## [2.2.1] - 2026-02-02

### Fixed

-   Migration loading issue

## [2.2.0] - 2026-01-30

### Added

-   **Magic Link Login UI** - User interface for passwordless magic link login

## [2.1.0] - 2026-01-22

### Added

-   **Magic Link Artisan Command** - CLI command for generating and managing magic login links

## [2.0.0] - 2025-12-07

### Added

-   **Time-Based Two-Factor Authentication (TOTP)** - Secure 2FA using authenticator apps (Google Authenticator, Authy, etc.)
    -   Secure setup flow with QR code and manual entry key
    -   Encryption for 2FA secrets and recovery codes in database
    -   Backup recovery codes management (view, copy, download, regenerate)
    -   Configurable forced setup for new/existing users
    -   Option to allow skipping 2FA setup (`TYRO_LOGIN_2FA_ALLOW_SKIP`)
    -   Seamless integration with login flow (Challenge screen)
    -   "Partial Login" security state during setup/challenge to prevent bypass
    -   Compatible with `pragmarx/google2fa`
    -   Customizable UI matching the application theme
    -   New `HasTwoFactorAuth` trait for User model (optional)
    -   New `tyro-login:reset-2fa` console command to reset 2FA for users

### Important Changes

-   **Database Schema** - Requires migration to add `two_factor_secret`, `two_factor_recovery_codes`, and `two_factor_confirmed_at` columns to `users` table.

## [1.6.0] - 2025-12-04

### Security Improvements

-   **Encrypted OAuth Tokens** - Social login tokens now encrypted at rest
    -   Custom `EncryptedOrPlaintext` cast for seamless migration from plaintext to encrypted tokens
    -   Backward compatible - existing plaintext tokens work immediately and are encrypted on next update
    -   Protects against unauthorized access if database is compromised
-   **Cryptographically Secure OTP Generation** - OTP codes now use `random_int()` instead of `rand()`
    -   Eliminates predictable patterns in OTP generation
    -   Resistant to statistical analysis attacks
    -   More efficient implementation
-   **Session Regeneration in OTP Flow** - Prevents session fixation attacks
    -   Session ID regenerated after logout before OTP verification
    -   User data preserved correctly across regeneration
    -   Eliminates session hijacking risk during two-factor authentication
-   **CSRF Protection on Logout** - Logout now requires POST request with CSRF token
    -   Prevents logout CSRF attacks via malicious links
    -   Removed duplicate logout route definitions
    -   **Breaking Change:** GET logout links must be updated to POST forms
-   **Improved Debug Logging** - Enhanced privacy and compliance
    -   Email addresses now masked in debug logs (e.g., `use***@example.com`)
    -   Security-sensitive URLs (verification, password reset) no longer logged
    -   OTP codes not logged (only metadata)
    -   Structured logging format for better parsing

### Changed

-   Logout route now POST-only (was GET/POST) for security
-   Debug logging format changed to structured arrays
-   OAuth token storage upgraded from plaintext to encrypted

### Removed

-   Duplicate logout route definition
-   TODO comments and dead code in LoginController

### Migration Guide

**Logout Links:** Update any custom logout links from GET to POST:

```html
<!-- Before (will no longer work) -->
<a href="{{ route('tyro-login.logout') }}">Logout</a>

<!-- After (correct implementation) -->
<form method="POST" action="{{ route('tyro-login.logout') }}">
    @csrf
    <button type="submit">Logout</button>
</form>
```

**OAuth Tokens:** No manual migration needed - existing plaintext tokens automatically work and are encrypted on next update.

## [1.5.0] - 2025-12-03

### Added

-   **shadcn Theme Customization** - Full shadcn/ui theme variable support for easy customization
    -   Uses standard shadcn CSS variables (oklch color format) for seamless compatibility
    -   New `shadcn-theme.blade.php` partial file for isolated theme variables
    -   Easy theme publishing with `php artisan tyro-login:publish-style --theme-only`
    -   Visual theme editing support via [tweakcn.com](https://tweakcn.com)
-   **Publish Style Command** - New Artisan command to publish and customize styles
    -   `php artisan tyro-login:publish-style` - Publish complete styles (theme + components)
    -   `php artisan tyro-login:publish-style --theme-only` - Publish only theme variables
-   **Automatic Email Verification via Social Login** - Users who authenticate via social login now have their email automatically marked as verified
    -   OAuth providers confirm email ownership, so we can trust the email address
    -   Configurable via `TYRO_LOGIN_SOCIAL_AUTO_VERIFY_EMAIL` (enabled by default)
    -   Works for new users, returning users, and existing users linking social accounts
-   **User Verification Commands** - New Artisan commands to manually manage user email verification
    -   `php artisan tyro-login:verify-user {email|id}` - Verify a single user
    -   `php artisan tyro-login:verify-user --all` - Verify all unverified users
    -   `php artisan tyro-login:unverify-user {email|id}` - Unverify a single user
    -   `php artisan tyro-login:unverify-user --all` - Unverify all verified users

## [1.4.0] - 2025-12-02

### Added

-   **Social Login (OAuth)** - Sign in with popular social providers using Laravel Socialite
    -   Support for Google, Facebook, GitHub, Twitter/X, LinkedIn, Bitbucket, GitLab, and Slack
    -   Beautiful social login buttons integrated into login and registration pages
    -   Automatic account linking when email matches existing user
    -   Auto-register new users from social login (configurable)
    -   Secure token storage with encryption
    -   Easy installation with `--with-social` flag
-   **Social Accounts Table** - New migration for storing social account connections
    -   Links social provider accounts to users
    -   Stores provider user ID, email, and avatar
    -   Encrypted access and refresh tokens
-   **Social Login Configuration** - Granular control over social login behavior
    -   `TYRO_LOGIN_SOCIAL_ENABLED` - Global toggle for social login
    -   Individual provider toggles (e.g., `TYRO_LOGIN_SOCIAL_GOOGLE`)
    -   `TYRO_LOGIN_SOCIAL_LINK_EXISTING` - Link to existing accounts by email
    -   `TYRO_LOGIN_SOCIAL_AUTO_REGISTER` - Auto-create users from social login
    -   Customizable button labels and icons per provider
-   **New Install Option** - `php artisan tyro-login:install --with-social` for social login setup

### Changed

-   Updated install command to optionally include Laravel Socialite
-   Login and registration views now display social login buttons when enabled

## [1.3.0] - 2025-12-01

### Added

-   **New Layout Styles** - Two stunning new layout options
    -   `fullscreen` - Full-screen background with glassmorphism form overlay featuring frosted glass effect and backdrop blur
    -   `card` - Floating card design with subtle radial gradient background patterns and smooth hover animations
-   **Enhanced Form Widths** - Optimized form widths for better readability across all layouts
    -   Standard layouts (centered, split): max-width 360px
    -   New layouts (fullscreen, card): max-width 420px for better visual balance

### Changed

-   Layout configuration now supports 5 options: `centered`, `split-left`, `split-right`, `fullscreen`, and `card`
-   Background image configuration now applies to both split layouts and the new fullscreen layout
-   Updated all documentation to reflect new layout options

## [1.2.0] - 2025-12-01

### Added

-   **Beautiful Email Templates** - Sleek, minimal HTML email templates with clean design
    -   OTP verification email with large, readable code display
    -   Password reset email with secure reset button and link fallback
    -   Email verification email with verification link
    -   Welcome email for new registrations with feature highlights
-   **Email Configuration** - Each email type can be individually enabled/disabled
    -   `TYRO_LOGIN_EMAIL_OTP` - Toggle OTP emails
    -   `TYRO_LOGIN_EMAIL_PASSWORD_RESET` - Toggle password reset emails
    -   `TYRO_LOGIN_EMAIL_VERIFY` - Toggle verification emails
    -   `TYRO_LOGIN_EMAIL_WELCOME` - Toggle welcome emails
-   **Customizable Email Subjects** - Configure email subjects via environment variables
-   **Email Template Publishing** - Publish only email templates with `--emails` flag
    -   `php artisan tyro-login:publish --emails`
-   **Email Verification Redirect** - New config option `TYRO_LOGIN_REDIRECT_AFTER_EMAIL_VERIFICATION` to control where users are redirected after verifying their email (defaults to `/login`)
-   **Unverified Email Login Handling** - When `TYRO_LOGIN_REQUIRE_EMAIL_VERIFICATION` is enabled, users with unverified emails attempting to login are now shown a dedicated "Email Not Verified" page instead of being logged in
-   **New "Email Not Verified" Page** - Beautiful dedicated page for unverified users with option to resend verification email
-   **Customizable Page Content** - New config options for `email_not_verified` page titles and descriptions

### Changed

-   Email verification no longer auto-logs in users - they are redirected to login page after verification
-   Login attempts with unverified email no longer resend verification email automatically (users can manually resend from the page)
-   Emails are now sent automatically when features are enabled (OTP, verification, password reset)
-   Welcome email is sent after registration when email verification is not required
-   Removed placeholder comments about email integration - emails are now fully functional

## [1.1.0] - 2025-11-30

### Added

-   **Math Captcha** - Simple addition/subtraction captcha for login and registration forms
    -   Configurable via `TYRO_LOGIN_CAPTCHA_LOGIN` and `TYRO_LOGIN_CAPTCHA_REGISTER`
    -   Customizable number range, labels, and error messages
    -   No external dependencies required
-   **Login OTP Verification** - Two-factor authentication via email OTP
    -   Configurable OTP length (4-8 digits)
    -   Configurable expiration time and resend limits
    -   Beautiful OTP input with individual digit boxes
    -   Resend cooldown and attempt tracking
    -   Cache-based storage (no database required)
-   **Debug Mode** - Global `TYRO_LOGIN_DEBUG` configuration
    -   When enabled, logs OTP codes, verification URLs, and reset URLs
    -   Disabled by default for production safety
    -   Single toggle for all debug logging

### Changed

-   All debug logging now requires `TYRO_LOGIN_DEBUG=true` to be set
-   Improved code organization in LoginController and RegisterController

## [1.0.0] - 2025-11-30

### Added

-   Initial release
-   Login and registration forms with validation
-   Three layout options: centered, split-left, split-right
-   Dark/light theme with automatic detection and manual toggle
-   Configurable branding (logo, colors, app name)
-   Remember me functionality
-   Cache-based lockout protection after failed login attempts
-   Beautiful lockout page with countdown timer
-   Email verification for new registrations (optional)
-   Password reset with forgot password flow
-   Secure signed URLs for verification and password reset tokens
-   Configurable redirects after login/logout/registration
-   Auto-login after registration option
-   Integration with Tyro package for automatic role assignment
-   Artisan commands: install, publish, version, doc, star
-   Fully configurable page content (titles, subtitles, background text)
-   Environment variable support for all configuration options
-   Responsive design for all screen sizes
-   CSRF protection and secure password handling
-   Session regeneration on login
-   Development-friendly: verification and reset URLs logged when debug enabled
