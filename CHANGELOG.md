# Changelog

All notable changes to `tyro-login` will be documented in this file.

## [2.0.0] - 2025-12-07

### Added

-   **Time-Based Two-Factor Authentication (TOTP)** - Secure 2FA usingauthenticator apps (Google Authenticator, Authy, etc.)
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
