# Tyro Login

<p align="center">
<a href="https://packagist.org/packages/hasinhayder/tyro-login"><img src="https://img.shields.io/packagist/v/hasinhayder/tyro-login.svg?style=flat-square" alt="Latest Version on Packagist"></a>
<a href="https://packagist.org/packages/hasinhayder/tyro-login"><img src="https://img.shields.io/packagist/dt/hasinhayder/tyro-login.svg?style=flat-square" alt="Total Downloads"></a>
<a href="https://github.com/hasinhayder/tyro-login/blob/main/LICENSE"><img src="https://img.shields.io/packagist/l/hasinhayder/tyro-login.svg?style=flat-square" alt="License"></a>
</p>

<p align="center">
<a href="https://hasinhayder.github.io/tyro/tyro-login/">Website</a> |
<a href="https://hasinhayder.github.io/tyro/tyro-login/doc.html">Documentation</a> |
<a href="https://github.com/hasinhayder/tyro-login">GitHub</a>
</p>

**Beautiful, customizable authentication views for Laravel 12** – Tyro Login provides professional, ready-to-use login and registration pages with multiple layout options and seamless integration with the [Tyro](https://github.com/hasinhayder/tyro) package.

## Features

-   **Multiple Layouts** - 5 beautiful layouts: centered, split-left, split-right, fullscreen, and card
-   **Beautiful Design** - Modern, professional UI out of the box
-   **Social Login** - OAuth authentication with Google, Facebook, GitHub, Twitter/X, LinkedIn, Bitbucket, GitLab, and Slack
-   **Highly Configurable** - Customize colors, logos, redirects, and more
-   **Secure by Default** - Lockout protection, CSRF protection, and proper validation
-   **Math Captcha** - Simple addition/subtraction captcha for login and registration
-   **Login OTP** - Two-factor authentication via email OTP codes
-   **Email Verification** - Optional email verification for new registrations
-   **Password Reset** - Built-in forgot password and reset functionality
-   **Beautiful Emails** - Sleek, minimal HTML email templates for OTP, password reset, verification, and welcome emails
-   **Tyro Integration** - Automatic role assignment for new users if Tyro is installed
-   **Dark/Light Theme** - Automatic theme detection with manual toggle
-   **Fully Responsive** - Works perfectly on all devices
-   **Zero Build Step** - No npm or webpack required, just install and use
-   **Debug Mode** - Global debug toggle for development logging

## Requirements

-   PHP 8.2 or higher
-   Laravel 12.0 or higher

## Installation

Install the package via Composer:

```bash
composer require hasinhayder/tyro-login
```

Run the installation command:

```bash
php artisan tyro-login:install
```

For social login support, use:

```bash
php artisan tyro-login:install --with-social
```

That's it! Visit `/login` to see your new authentication pages.

## Configuration

After installation, you can customize the package by editing `config/tyro-login.php`:

### Layout Options

```php
// Available layouts: 'centered', 'split-left', 'split-right', 'fullscreen', 'card'
'layout' => env('TYRO_LOGIN_LAYOUT', 'centered'),

// Background image for split and fullscreen layouts
'background_image' => env('TYRO_LOGIN_BACKGROUND_IMAGE', 'https://...'),
```

### Branding

```php
'branding' => [
    'app_name' => env('TYRO_LOGIN_APP_NAME', 'Laravel'),
    'logo' => env('TYRO_LOGIN_LOGO', null), // URL to your logo
    'logo_height' => env('TYRO_LOGIN_LOGO_HEIGHT', '48px'),
],
```

### Redirects

```php
'redirects' => [
    'after_login' => env('TYRO_LOGIN_REDIRECT_AFTER_LOGIN', '/'),
    'after_logout' => env('TYRO_LOGIN_REDIRECT_AFTER_LOGOUT', '/login'),
    'after_register' => env('TYRO_LOGIN_REDIRECT_AFTER_REGISTER', '/'),
    'after_email_verification' => env('TYRO_LOGIN_REDIRECT_AFTER_EMAIL_VERIFICATION', '/login'),
],
```

### Registration Settings

```php
'registration' => [
    'enabled' => env('TYRO_LOGIN_REGISTRATION_ENABLED', true),
    'auto_login' => env('TYRO_LOGIN_REGISTRATION_AUTO_LOGIN', true),
    'require_email_verification' => env('TYRO_LOGIN_REQUIRE_EMAIL_VERIFICATION', false),
],
```

### Email Verification

When email verification is enabled, users won't be logged in automatically after registration. Instead, they'll be redirected to a verification notice page and a verification link will be generated.

```php
'registration' => [
    'require_email_verification' => env('TYRO_LOGIN_REQUIRE_EMAIL_VERIFICATION', true),
],

'verification' => [
    'expire' => env('TYRO_LOGIN_VERIFICATION_EXPIRE', 60), // Token expires in 60 minutes
],

'redirects' => [
    'after_email_verification' => env('TYRO_LOGIN_REDIRECT_AFTER_EMAIL_VERIFICATION', '/login'),
],
```

**How it works:**

1. User registers - Redirected to verification notice page
2. Verification URL is logged to Laravel logs and error_log (for development)
3. User clicks the link - Email is verified and user is redirected to login page
4. Users can request a new verification email from the notice page
5. If user tries to login with unverified email, they see "Email Not Verified" page

**For Development:** The verification URL is printed to your Laravel logs and error_log, so you can easily test without setting up email.

### Password Reset

Tyro Login includes a complete password reset flow with beautiful, consistent UI.

```php
'password_reset' => [
    'expire' => env('TYRO_LOGIN_PASSWORD_RESET_EXPIRE', 60), // Token expires in 60 minutes
],
```

**How it works:**

1. User clicks "Forgot Password?" on login page
2. User enters email - Reset link is generated
3. Reset URL is logged to Laravel logs and error_log (for development)
4. User clicks the link - Shown password reset form
5. User enters new password - Password updated and user is logged in

**For Development:** The reset URL is printed to your Laravel logs and error_log, so you can easily test without setting up email.

### Tyro Integration

If you have [hasinhayder/tyro](https://github.com/hasinhayder/tyro) installed, Tyro Login can automatically assign a default role to new users:

```php
'tyro' => [
    'assign_default_role' => env('TYRO_LOGIN_ASSIGN_DEFAULT_ROLE', true),
    'default_role_slug' => env('TYRO_LOGIN_DEFAULT_ROLE_SLUG', 'user'),
],
```

### Math Captcha

Add a simple math captcha to your login and/or registration forms to prevent automated submissions:

```php
'captcha' => [
    'enabled_login' => env('TYRO_LOGIN_CAPTCHA_LOGIN', false),
    'enabled_register' => env('TYRO_LOGIN_CAPTCHA_REGISTER', false),
    'label' => 'Security Check',
    'placeholder' => 'Enter the answer',
    'error_message' => 'Incorrect answer. Please try again.',
    'min_number' => 1,
    'max_number' => 10,
],
```

### Login Field

Configure which field users should use to log in:

```php
'login_field' => env('TYRO_LOGIN_FIELD', 'email'),
```

**Options:**
- `'email'` - Users log in with email address (default)
- `'username'` - Users log in with username
- `'phone'` - Users log in with phone number
- `'both'` - Users can log in with either email or username

**Example for phone login:**

```env
TYRO_LOGIN_FIELD=phone
```

**Note:** When using `'phone'` login, ensure your User model has a `'phone'` column in the database. See the [Phone Number Login](#phone-number-login) section below for setup instructions.

### Login OTP Verification

Add two-factor authentication via email OTP. After entering valid credentials, users receive a one-time code:

```php
'otp' => [
    'enabled' => env('TYRO_LOGIN_OTP_ENABLED', false),
    'length' => 4,           // 4-8 digits
    'expire' => 5,           // minutes
    'max_resend' => 3,
    'resend_cooldown' => 60, // seconds
],
```

**Features:**

-   Beautiful OTP input with individual digit boxes
-   Configurable code length (4-8 digits)
-   Resend functionality with cooldown
-   Cache-based storage (no database required)

### Debug Mode

Enable debug logging for development:

```php
'debug' => env('TYRO_LOGIN_DEBUG', false),
```

When enabled, OTP codes, verification URLs, and password reset URLs are logged to `storage/logs/laravel.log`.

### Email Configuration

Tyro Login sends sleek, minimal HTML emails with a clean design. Each email type can be individually enabled or disabled:

```php
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
        'subject' => env('TYRO_LOGIN_EMAIL_WELCOME_SUBJECT', null), // Uses default with app name
    ],
],
```

**Available Emails:**

-   **OTP Email** - Sent when OTP verification is enabled
-   **Password Reset Email** - Sent when user requests password reset
-   **Email Verification Email** - Sent when email verification is required
-   **Welcome Email** - Sent after successful registration (when verification is not required)

**Customizing Email Templates:**

Publish email templates to customize them:

```bash
php artisan tyro-login:publish --emails
```

Templates will be published to `resources/views/vendor/tyro-login/emails/`.

Available template variables:

-   `{{ $name }}` - User's name
-   `{{ $appName }}` - Application name
-   `{{ $otp }}` - OTP code (for OTP email)
-   `{{ $resetUrl }}` - Password reset URL (for password reset email)
-   `{{ $verificationUrl }}` - Verification URL (for verification email)
-   `{{ $loginUrl }}` - Login URL (for welcome email)
-   `{{ $expiresIn }}` - Expiration time in minutes

### Lockout Protection

When enabled, users will be locked out after too many failed login attempts. The lockout state is stored in cache (no database required), and the cache is automatically cleared when the lockout expires.

```php
'lockout' => [
    'enabled' => env('TYRO_LOGIN_LOCKOUT_ENABLED', true),
    'max_attempts' => env('TYRO_LOGIN_LOCKOUT_MAX_ATTEMPTS', 5),
    'duration_minutes' => env('TYRO_LOGIN_LOCKOUT_DURATION', 15),
    'message' => 'Too many failed login attempts. Please try again in :minutes minutes.',
    'title' => 'Account Temporarily Locked',
    'subtitle' => 'For your security, we\'ve temporarily locked your account.',
],
```

**Features:**

-   No database required - uses cache
-   Configurable number of attempts before lockout
-   Configurable lockout duration
-   Customizable lockout page message and title
-   Automatic cache cleanup when lockout expires
-   Real-time countdown timer on lockout page

### Social Login (OAuth)

Tyro Login supports OAuth authentication using Laravel Socialite. Users can sign in with their social media accounts.

**Supported Providers:**
- Google
- Facebook
- GitHub
- Twitter/X
- LinkedIn
- Bitbucket
- GitLab
- Slack

#### Installation

Install with social login support:

```bash
php artisan tyro-login:install --with-social
```

Or add social login to an existing installation:

```bash
composer require laravel/socialite
php artisan vendor:publish --tag=tyro-login-migrations
php artisan migrate
```

#### Configuration

1. **Enable Social Login Globally:**

```env
TYRO_LOGIN_SOCIAL_ENABLED=true
```

2. **Enable Desired Providers:**

```env
TYRO_LOGIN_SOCIAL_GOOGLE=true
TYRO_LOGIN_SOCIAL_GITHUB=true
TYRO_LOGIN_SOCIAL_FACEBOOK=true
```

3. **Configure Provider Credentials:**

Add credentials to `config/services.php`:

```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI'),
],

'github' => [
    'client_id' => env('GITHUB_CLIENT_ID'),
    'client_secret' => env('GITHUB_CLIENT_SECRET'),
    'redirect' => env('GITHUB_REDIRECT_URI'),
],

'facebook' => [
    'client_id' => env('FACEBOOK_CLIENT_ID'),
    'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
    'redirect' => env('FACEBOOK_REDIRECT_URI'),
],

// For Twitter/X (OAuth 2.0)
'twitter' => [
    'client_id' => env('TWITTER_CLIENT_ID'),
    'client_secret' => env('TWITTER_CLIENT_SECRET'),
    'redirect' => env('TWITTER_REDIRECT_URI'),
],

// For LinkedIn (OpenID Connect)
'linkedin-openid' => [
    'client_id' => env('LINKEDIN_CLIENT_ID'),
    'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
    'redirect' => env('LINKEDIN_REDIRECT_URI'),
],

// For Slack (OpenID Connect)
'slack-openid' => [
    'client_id' => env('SLACK_CLIENT_ID'),
    'client_secret' => env('SLACK_CLIENT_SECRET'),
    'redirect' => env('SLACK_REDIRECT_URI'),
],
```

4. **Add Environment Variables:**

```env
# Google
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"

# GitHub
GITHUB_CLIENT_ID=your-client-id
GITHUB_CLIENT_SECRET=your-client-secret
GITHUB_REDIRECT_URI="${APP_URL}/auth/github/callback"

# Facebook
FACEBOOK_CLIENT_ID=your-client-id
FACEBOOK_CLIENT_SECRET=your-client-secret
FACEBOOK_REDIRECT_URI="${APP_URL}/auth/facebook/callback"

# Slack
SLACK_CLIENT_ID=your-client-id
SLACK_CLIENT_SECRET=your-client-secret
SLACK_REDIRECT_URI="${APP_URL}/auth/slack/callback"
```

#### Social Login Behavior

```php
'social' => [
    'enabled' => env('TYRO_LOGIN_SOCIAL_ENABLED', false),
    
    // Link social accounts to existing users (matched by email)
    'link_existing_accounts' => env('TYRO_LOGIN_SOCIAL_LINK_EXISTING', true),
    
    // Automatically create new users from social login
    'auto_register' => env('TYRO_LOGIN_SOCIAL_AUTO_REGISTER', true),
    
    // Automatically verify user email after social login/register
    // Social providers confirm email ownership, so we can trust the email
    'auto_verify_email' => env('TYRO_LOGIN_SOCIAL_AUTO_VERIFY_EMAIL', true),
    
    // Text shown above social buttons
    'divider_text' => env('TYRO_LOGIN_SOCIAL_DIVIDER', 'Or continue with'),
],
```

**How it works:**

1. User clicks a social login button on login/register page
2. User is redirected to the OAuth provider for authentication
3. After approval, user is redirected back to your app
4. If user has linked social account → Log them in
5. If user email exists and linking is enabled → Link social account and log in
6. If user doesn't exist and auto-register is enabled → Create new user and log in

**Automatic Email Verification:**

When users authenticate via social login, their email is automatically marked as verified (if `auto_verify_email` is enabled). This is because OAuth providers confirm email ownership during the authentication process, so we can trust the email address provided.

**Social Accounts Table:**

A migration creates the `social_accounts` table to store:
- `user_id` - Link to your users table
- `provider` - The OAuth provider (google, github, etc.)
- `provider_user_id` - User ID from the provider
- `provider_email` - Email from the provider
- `provider_avatar` - Avatar URL from the provider
- `access_token` / `refresh_token` - OAuth tokens (encrypted)
- `token_expires_at` - Token expiration time

#### Customizing Provider Labels and Icons

```php
'social' => [
    'providers' => [
        'google' => [
            'enabled' => true,
            'label' => 'Google',  // Button text
            'icon' => 'google',   // Icon identifier
        ],
        'github' => [
            'enabled' => true,
            'label' => 'GitHub',
            'icon' => 'github',
        ],
    ],
],
```

## Phone Number Login

Tyro Login supports phone number-based authentication. When enabled, users can log in using their phone numbers instead of email addresses.

### Setup Phone Login

1. **Set the login field in your `.env`:**

```env
TYRO_LOGIN_FIELD=phone
```

2. **Add a phone column to your users table:**

```bash
php artisan make:migration add_phone_to_users_table
```

Migration content:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->unique()->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
```

Run the migration:

```bash
php artisan migrate
```

3. **Add phone to your User model's fillable attributes:**

```php
protected $fillable = [
    'name',
    'email',
    'phone', // Add this
    'password',
];
```

4. **Create a custom authentication provider to handle phone lookups:**

In your `AppServiceProvider.php` (or a custom service provider):

```php
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\EloquentUserProvider;

public function boot(): void
{
    Auth::provider('phone', function ($app, array $config) {
        return new class($app['hash'], $config['model']) extends EloquentUserProvider {
            public function retrieveByCredentials(array $credentials)
            {
                if (empty($credentials) ||
                   (count($credentials) === 1 &&
                    str_contains($this->firstCredentialKey($credentials), 'password'))) {
                    return;
                }

                $query = $this->newModelQuery();

                foreach ($credentials as $key => $value) {
                    if (str_contains($key, 'password')) {
                        continue;
                    }

                    // When email field contains phone number, search by phone
                    if ($key === 'email' && preg_match('/^[0-9+\-\s()]+$/', $value)) {
                        $query->where('phone', $value);
                    } else {
                        $query->where($key, $value);
                    }
                }

                return $query->first();
            }

            protected function firstCredentialKey(array $credentials)
            {
                foreach ($credentials as $key => $value) {
                    return $key;
                }
            }
        };
    });
}
```

5. **Update your auth configuration** in `config/auth.php`:

```php
'providers' => [
    'users' => [
        'driver' => 'phone', // Change from 'eloquent' to 'phone'
        'model' => App\Models\User::class,
    ],
],
```

### How It Works

- The login form will display "Phone" as the label when `login_field` is set to `'phone'`
- Users enter their phone number in the login field
- The custom authentication provider detects phone number patterns and searches by the `phone` column
- Registration form will include a phone field that validates and stores the phone number
- Phone numbers must be unique in the users table

### Example Phone Formats

The phone login supports various formats:
- `01700000000` (Bangladesh format)
- `+8801700000000` (International format)
- `(555) 123-4567` (US format with parentheses and dashes)

## Layout Examples

Tyro Login provides 5 stunning layout options to match your application's branding:

### 1. Centered Layout (Default)

Form appears in the center of the page with a gradient background.

```env
TYRO_LOGIN_LAYOUT=centered
```

### 2. Split-Left Layout

Two-column layout with a background image on the left and the form on the right.

```env
TYRO_LOGIN_LAYOUT=split-left
TYRO_LOGIN_BACKGROUND_IMAGE=https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=1920&q=80
```

### 3. Split-Right Layout

Two-column layout with the form on the left and a background image on the right.

```env
TYRO_LOGIN_LAYOUT=split-right
TYRO_LOGIN_BACKGROUND_IMAGE=https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=1920&q=80
```

### 4. Fullscreen Layout

Full-screen background image with a glassmorphism form overlay featuring frosted glass effect and backdrop blur.

```env
TYRO_LOGIN_LAYOUT=fullscreen
TYRO_LOGIN_BACKGROUND_IMAGE=https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=1920&q=80
```

### 5. Card Layout

Floating card design with subtle radial gradient background patterns and smooth hover animations.

```env
TYRO_LOGIN_LAYOUT=card
```

**All layouts support:**

-   Dark and light themes
-   Fully responsive design
-   Customizable branding
-   All authentication features (OTP, captcha, email verification, etc.)

## Customization

### Publishing Views

To customize the views, publish them to your application:

```bash
php artisan tyro-login:publish --views
```

Views will be published to `resources/views/vendor/tyro-login/`.

### Publishing Email Templates

To customize the email templates:

```bash
php artisan tyro-login:publish --emails
```

Email templates will be published to `resources/views/vendor/tyro-login/emails/`.

### Publishing Everything

```bash
php artisan tyro-login:publish
```

This publishes config, views, email templates, and assets.

## Artisan Commands

Tyro Login provides several artisan commands:

| Command                                   | Description                                        |
| ----------------------------------------- | -------------------------------------------------- |
| `php artisan tyro-login:install`          | Install the package and publish configuration      |
| `php artisan tyro-login:install --with-social` | Install with social login (Laravel Socialite) support |
| `php artisan tyro-login:publish`          | Publish config, views, email templates, and assets |
| `php artisan tyro-login:publish --emails` | Publish only email templates                       |
| `php artisan tyro-login:verify-user`      | Mark a user's email as verified                    |
| `php artisan tyro-login:unverify-user`    | Remove email verification from a user              |
| `php artisan tyro-login:version`          | Display the current Tyro Login version             |
| `php artisan tyro-login:doc`              | Open the documentation in your browser             |
| `php artisan tyro-login:star`             | Open GitHub repository to star the project         |

### User Verification Commands

Tyro Login provides commands to manually verify or unverify user email addresses.

**Verify a single user by email:**
```bash
php artisan tyro-login:verify-user john@example.com
```

**Verify a single user by ID:**
```bash
php artisan tyro-login:verify-user 123
```

**Verify all unverified users:**
```bash
php artisan tyro-login:verify-user --all
```

**Unverify a single user:**
```bash
php artisan tyro-login:unverify-user john@example.com
```

**Unverify all verified users:**
```bash
php artisan tyro-login:unverify-user --all
```

These commands are useful for:
- Manually verifying users during development or testing
- Bulk verification of imported users
- Resetting verification status for testing email flows

## Routes

Tyro Login registers the following routes:

| Method   | URI                           | Name                                   | Description                |
| -------- | ----------------------------- | -------------------------------------- | -------------------------- |
| GET      | `/login`                      | `tyro-login.login`                     | Show login form            |
| POST     | `/login`                      | `tyro-login.login.submit`              | Handle login               |
| GET      | `/register`                   | `tyro-login.register`                  | Show registration form     |
| POST     | `/register`                   | `tyro-login.register.submit`           | Handle registration        |
| GET/POST | `/logout`                     | `tyro-login.logout`                    | Handle logout              |
| GET      | `/lockout`                    | `tyro-login.lockout`                   | Show lockout page          |
| GET      | `/email/verify`               | `tyro-login.verification.notice`       | Show verification notice   |
| GET      | `/email/not-verified`         | `tyro-login.verification.not-verified` | Show unverified email page |
| GET      | `/email/verify/{token}`       | `tyro-login.verification.verify`       | Verify email               |
| POST     | `/email/resend`               | `tyro-login.verification.resend`       | Resend verification email  |
| GET      | `/forgot-password`            | `tyro-login.password.request`          | Show forgot password form  |
| POST     | `/forgot-password`            | `tyro-login.password.email`            | Send reset link            |
| GET      | `/reset-password/{token}`     | `tyro-login.password.reset`            | Show reset form            |
| POST     | `/reset-password`             | `tyro-login.password.update`           | Reset password             |
| GET      | `/otp/verify`                 | `tyro-login.otp.verify`                | Show OTP form              |
| POST     | `/otp/verify`                 | `tyro-login.otp.submit`                | Verify OTP                 |
| POST     | `/otp/resend`                 | `tyro-login.otp.resend`                | Resend OTP                 |
| GET      | `/otp/cancel`                 | `tyro-login.otp.cancel`                | Cancel OTP verification    |
| GET      | `/auth/{provider}/redirect`   | `tyro-login.social.redirect`           | Redirect to OAuth provider |
| GET      | `/auth/{provider}/callback`   | `tyro-login.social.callback`           | Handle OAuth callback      |

### Customizing Route Prefix

```php
'routes' => [
    'prefix' => env('TYRO_LOGIN_ROUTE_PREFIX', 'auth'),
    // Routes will be: /auth/login, /auth/register, etc.
],
```

## Security Features

-   **CSRF Protection** - All forms include CSRF tokens
-   **Lockout Protection** - Temporarily lock accounts after failed attempts (cache-based, no database)
-   **Email Verification** - Optional email verification for new registrations
-   **Secure Password Reset** - Time-limited, signed URLs for password reset
-   **Password Hashing** - Uses Laravel's secure hashing
-   **Session Regeneration** - Prevents session fixation attacks
-   **Input Validation** - Server-side validation with proper error messages

## Integration with Tyro

Tyro Login integrates seamlessly with the [Tyro](https://github.com/hasinhayder/tyro) package:

1. When a new user registers, Tyro Login can automatically assign a default role
2. Configure the default role slug in your config
3. Ensure your User model uses the `HasTyroRoles` trait

```php
// In your User model
use HasinHayder\Tyro\Concerns\HasTyroRoles;

class User extends Authenticatable
{
    use HasTyroRoles;
}
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email hasin@hasin.me instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Credits

-   [Hasin Hayder](https://github.com/hasinhayder)

---

<p align="center">
Made with love for the Laravel community by Hasin Hayder
</p>
