# Tyro Login

<p align="center">
<a href="https://packagist.org/packages/hasinhayder/tyro-login"><img src="https://img.shields.io/packagist/v/hasinhayder/tyro-login.svg?style=flat-square" alt="Latest Version on Packagist"></a>
<a href="https://packagist.org/packages/hasinhayder/tyro-login"><img src="https://img.shields.io/packagist/dt/hasinhayder/tyro-login.svg?style=flat-square" alt="Total Downloads"></a>
<a href="https://github.com/hasinhayder/tyro-login/blob/main/LICENSE"><img src="https://img.shields.io/packagist/l/hasinhayder/tyro-login.svg?style=flat-square" alt="License"></a>
</p>

**Beautiful, customizable authentication views for Laravel 12** – Tyro Login provides professional, ready-to-use login and registration pages with multiple layout options and seamless integration with the [Tyro](https://github.com/hasinhayder/tyro) package.

## ✨ Features

- 🎨 **Multiple Layouts** - Centered, split-left, and split-right layouts
- 🖼️ **Beautiful Design** - Modern, professional UI out of the box
- 🔧 **Highly Configurable** - Customize colors, logos, redirects, and more
- 🔐 **Secure by Default** - Rate limiting, lockout protection, CSRF protection, and proper validation
- 🔗 **Tyro Integration** - Automatic role assignment for new users if Tyro is installed
- 🌓 **Dark/Light Theme** - Automatic theme detection with manual toggle
- 📱 **Fully Responsive** - Works perfectly on all devices
- ⚡ **Zero Build Step** - No npm or webpack required, just install and use

## 📦 Installation

Install the package via Composer:

```bash
composer require hasinhayder/tyro-login
```

Run the installation command:

```bash
php artisan tyro-login:install
```

That's it! Visit `/login` to see your new authentication pages.

## ⚙️ Configuration

After installation, you can customize the package by editing `config/tyro-login.php`:

### Layout Options

```php
// Available layouts: 'centered', 'split-left', 'split-right'
'layout' => env('TYRO_LOGIN_LAYOUT', 'centered'),

// Background image for split layouts
'background_image' => env('TYRO_LOGIN_BACKGROUND_IMAGE', 'https://...'),
```

### Branding

```php
'branding' => [
    'app_name' => env('TYRO_LOGIN_APP_NAME', 'Laravel'),
    'logo' => env('TYRO_LOGIN_LOGO', null), // URL to your logo
    'logo_height' => env('TYRO_LOGIN_LOGO_HEIGHT', '48px'),
    'primary_color' => env('TYRO_LOGIN_PRIMARY_COLOR', '#4f46e5'),
    'primary_hover_color' => env('TYRO_LOGIN_PRIMARY_HOVER_COLOR', '#4338ca'),
],
```

### Redirects

```php
'redirects' => [
    'after_login' => env('TYRO_LOGIN_REDIRECT_AFTER_LOGIN', '/'),
    'after_logout' => env('TYRO_LOGIN_REDIRECT_AFTER_LOGOUT', '/login'),
    'after_register' => env('TYRO_LOGIN_REDIRECT_AFTER_REGISTER', '/'),
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

### Tyro Integration

If you have [hasinhayder/tyro](https://github.com/hasinhayder/tyro) installed, Tyro Login can automatically assign a default role to new users:

```php
'tyro' => [
    'assign_default_role' => env('TYRO_LOGIN_ASSIGN_DEFAULT_ROLE', true),
    'default_role_slug' => env('TYRO_LOGIN_DEFAULT_ROLE_SLUG', 'user'),
],
```

### Rate Limiting

```php
'rate_limiting' => [
    'enabled' => env('TYRO_LOGIN_RATE_LIMITING', true),
    'max_attempts' => env('TYRO_LOGIN_MAX_ATTEMPTS', 5),
    'decay_minutes' => env('TYRO_LOGIN_DECAY_MINUTES', 1),
],
```

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
- No database required – uses cache
- Configurable number of attempts before lockout
- Configurable lockout duration
- Customizable lockout page message and title
- Automatic cache cleanup when lockout expires
- Real-time countdown timer on lockout page

## 🎨 Layout Examples

### Centered Layout (Default)
Form appears in the center of the page with a gradient background.

### Split-Left Layout
Two-column layout with a background image on the left and the form on the right.

### Split-Right Layout
Two-column layout with the form on the left and a background image on the right.

Set the layout in your `.env` file:

```env
TYRO_LOGIN_LAYOUT=split-left
```

## 🛠️ Customization

### Publishing Views

To customize the views, publish them to your application:

```bash
php artisan tyro-login:publish --views
```

Views will be published to `resources/views/vendor/tyro-login/`.

### Publishing Everything

```bash
php artisan tyro-login:publish
```

This publishes config, views, and assets.

### Custom Styling via Environment

You can customize colors without publishing files:

```env
TYRO_LOGIN_PRIMARY_COLOR=#4f46e5
TYRO_LOGIN_PRIMARY_HOVER_COLOR=#4338ca
TYRO_LOGIN_BACKGROUND_IMAGE=https://example.com/image.jpg
```

## 📍 Routes

Tyro Login registers the following routes:

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/login` | `tyro-login.login` | Show login form |
| POST | `/login` | `tyro-login.login.submit` | Handle login |
| GET | `/register` | `tyro-login.register` | Show registration form |
| POST | `/register` | `tyro-login.register.submit` | Handle registration |
| GET/POST | `/logout` | `tyro-login.logout` | Handle logout |
| GET | `/lockout` | `tyro-login.lockout` | Show lockout page |

### Customizing Route Prefix

```php
'routes' => [
    'prefix' => env('TYRO_LOGIN_ROUTE_PREFIX', 'auth'),
    // Routes will be: /auth/login, /auth/register, etc.
],
```

## 🔒 Security Features

- **CSRF Protection** - All forms include CSRF tokens
- **Rate Limiting** - Configurable brute-force protection
- **Lockout Protection** - Temporarily lock accounts after failed attempts (cache-based, no database)
- **Password Hashing** - Uses Laravel's secure hashing
- **Session Regeneration** - Prevents session fixation attacks
- **Input Validation** - Server-side validation with proper error messages

## 🤝 Integration with Tyro

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

## 📝 Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## 🤝 Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## 🔐 Security

If you discover any security-related issues, please email security@example.com instead of using the issue tracker.

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## 👨‍💻 Credits

- [Hasin Hayder](https://github.com/hasinhayder)
- [All Contributors](../../contributors)

---

<p align="center">
Made with ❤️ for the Laravel community
</p>
