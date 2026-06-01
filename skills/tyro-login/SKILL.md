---
name: tyro-login
version: 2.8.1
domain: authentication
mindset: framework-maintainer
description: Laravel authentication infrastructure package providing guards, providers, sessions, tokens, verification, and security for the Tyro ecosystem.
maintained-since: 2024
consumer-count: thousands
composer: hasinhayder/tyro-login
namespace: HasinHayder\TyroLogin
php: ^8.2
laravel: ^12.0 || ^13.0
---

# Tyro Login Skill

This is a **framework-maintainer** skill for the `hasinhayder/tyro-login` authentication package.

It teaches AI models how to create, review, refactor, and maintain Tyro Login with the mindset of a framework maintainer — protecting public APIs, preserving backward compatibility, and designing for ecosystem growth over a 10+ year maintenance horizon.

This is NOT an application-development skill.
This is NOT a login page generator.
This is NOT a starter kit.
This is authentication infrastructure.

---

## Activation Triggers

The skill activates when any of these patterns appear in the task context:

### File Paths

```
src/**/*.php
config/tyro-login.php
routes/web.php
resources/views/**/*.blade.php
database/migrations/*.php
tests/**/*.php
composer.json
skills/tyro-login/**
```

### Class References

```
HasinHayder\TyroLogin\*
HasinHayder\TyroLogin\Providers\TyroLoginServiceProvider
HasinHayder\TyroLogin\Http\Controllers\*
HasinHayder\TyroLogin\Models\*
HasinHayder\TyroLogin\Console\Commands\*
HasinHayder\TyroLogin\Mail\*
HasinHayder\TyroLogin\Casts\EncryptedOrPlaintext
HasinHayder\TyroLogin\Traits\HasTwoFactorAuth
HasinHayder\TyroLogin\Helpers\InvitationHelper
```

### Config and Environment Keys

```
config('tyro-login.*')
env('TYRO_LOGIN_*')
```

---

## Consistency First

Eight immutable principles that govern every decision in this package:

| # | Principle | Rationale |
|---|---|---|
| 1 | **Config-first design** | Every feature must be toggleable via config. Never hardcode behavior. |
| 2 | **Cache over database for transient state** | OTP codes, lockout counters, password reset tokens, magic links, verification tokens — all use cache, not the database. |
| 3 | **Soft integration for optional packages** | Use `class_exists()` and `method_exists()` at runtime. Never add optional packages to `require` in composer.json. |
| 4 | **Backward compatible encryption** | New data is encrypted. Legacy data is read as plaintext and encrypted on write. The `EncryptedOrPlaintext` cast handles this transition. |
| 5 | **POST-only state mutations** | Login, logout, register, OTP verification, 2FA confirmation, password updates — all require POST. No GET mutations. |
| 6 | **Session regeneration on auth transitions** | Every authentication boundary transition — login, logout, OTP step-up, 2FA step-up — must regenerate the session ID. |
| 7 | **Configurable User Model everywhere** | Never reference `App\Models\User` directly. Always use `config('tyro-login.user_model')` in relationships, traits, and providers. |
| 8 | **Environment-driven defaults** | Every config value must read from an environment variable with a sensible default. The config file is the single source of truth; controllers never call `env()` directly. |

---

## Quick Reference

### File Map

| File | Purpose |
|---|---|
| `src/Providers/TyroLoginServiceProvider.php` | Package bootstrap — config merge, route registration, view loading, migration loading, publish commands, auth redirection |
| `src/Http/Controllers/LoginController.php` | Login form, authenticate, lockout, OTP flow, magic links, logout |
| `src/Http/Controllers/RegisterController.php` | Registration form, user creation, captcha, password validation, Tyro role assignment |
| `src/Http/Controllers/TwoFactorController.php` | TOTP setup, QR code generation, TOTP verification, recovery codes, 2FA challenge |
| `src/Http/Controllers/VerificationController.php` | Email verification notice, token generation, verification, resend |
| `src/Http/Controllers/PasswordResetController.php` | Forgot password form, reset link generation, password reset |
| `src/Http/Controllers/SocialAuthController.php` | OAuth redirect, callback, social user linking, auto-registration |
| `src/Models/SocialAccount.php` | OAuth provider account linked to user |
| `src/Models/InvitationLink.php` | Referral link for invitation system |
| `src/Models/InvitationReferral.php` | Referral tracking record |
| `src/Casts/EncryptedOrPlaintext.php` | Eloquent cast — reads encrypted or legacy plaintext, always writes encrypted |
| `src/Traits/HasTwoFactorAuth.php` | User model trait — 2FA casts and helper methods |
| `src/Helpers/InvitationHelper.php` | Static utility for invitation validation and referral tracking |
| `src/Mail/OtpMail.php` | One-time password email |
| `src/Mail/PasswordResetMail.php` | Password reset email |
| `src/Mail/VerifyEmailMail.php` | Email verification email |
| `src/Mail/WelcomeMail.php` | Welcome email after registration |
| `src/Mail/MagicLinkMail.php` | Magic link login email |
| `config/tyro-login.php` | Package configuration (631 lines, all env-controllable) |
| `routes/web.php` | All authentication route definitions |
| `database/migrations/*.php` | Schema migrations for social accounts, 2FA columns, invitation system |

### Config Key Index

| Key Group | Env Prefix | Description |
|---|---|---|
| `tyro-login.debug` | `TYRO_LOGIN_DEBUG` | Enables sensitive debug logging |
| `tyro-login.layout` | `TYRO_LOGIN_LAYOUT` | View layout — centered, split-left, split-right, fullscreen, card |
| `tyro-login.branding.*` | `TYRO_LOGIN_*` | App name, logo, logo_dark, logo_height |
| `tyro-login.routes.*` | — | Route prefix, middleware, name overrides |
| `tyro-login.redirects.*` | — | Post-login/logout/register/verification redirects |
| `tyro-login.registration.*` | `TYRO_LOGIN_REGISTRATION_*` | Registration enabled, auto_login, require_email_verification |
| `tyro-login.tyro.*` | — | Tyro package integration (role assignment) |
| `tyro-login.user_model` | — | Customizable User model class |
| `tyro-login.features.*` | `TYRO_LOGIN_FEATURE_*` | Feature toggles — remember_me, forgot_password, magic_links, disable_password |
| `tyro-login.password.*` | — | Password policy — min/max length, complexity, common password check |
| `tyro-login.login_field` | — | Login field — email, username, or both |
| `tyro-login.pages.*` | — | Per-page content — titles, subtitles, background copy |
| `tyro-login.verification.*` | — | Token expiration in minutes |
| `tyro-login.password_reset.*` | — | Token expiration in minutes |
| `tyro-login.captcha.*` | — | Math captcha settings per form |
| `tyro-login.otp.*` | — | OTP settings — length, expire, resend limits |
| `tyro-login.two_factor.*` | — | TOTP 2FA — setup, challenge, forced roles, ignore cookie |
| `tyro-login.emails.*` | — | Per-email-type enable/subject configuration |
| `tyro-login.social.*` | — | OAuth providers — 8 providers with per-provider settings |
| `tyro-login.lockout.*` | — | Brute-force protection — max_attempts, duration, show_attempts_left |

### Route Name Index

| Route Name | Method | URI | Controller | Purpose |
|---|---|---|---|---|
| `tyro-login.login` | GET | `login` | LoginController@showLoginForm | Display login form |
| `tyro-login.login` | POST | `login` | LoginController@login | Submit login |
| `tyro-login.logout` | GET/POST | `logout` | LoginController@logout | Logout (POST only processes) |
| `tyro-login.register` | GET | `register` | RegisterController@showRegistrationForm | Display registration form |
| `tyro-login.register` | POST | `register` | RegisterController@register | Submit registration |
| `tyro-login.password.request` | GET | `password/reset` | PasswordResetController@showForgotPasswordForm | Forgot password form |
| `tyro-login.password.email` | POST | `password/reset` | PasswordResetController@sendResetLink | Send reset link |
| `tyro-login.password.reset` | GET | `password/reset/{token}` | PasswordResetController@showResetForm | Display reset form |
| `tyro-login.password.update` | POST | `password/reset/{token}` | PasswordResetController@reset | Submit new password |
| `tyro-login.verification.notice` | GET | `email/verify` | VerificationController@showVerificationNotice | Verification notice |
| `tyro-login.verification.verify` | GET | `email/verify/{id}/{hash}` | VerificationController@verify | Verify email |
| `tyro-login.verification.resend` | POST | `email/verification-notification` | VerificationController@resend | Resend verification |
| `tyro-login.verification.not-verified` | GET | `email/not-verified` | VerificationController@showEmailNotVerified | Not-verified notice |
| `tyro-login.otp.form` | GET | `otp/verify` | LoginController@showOtpForm | OTP input form |
| `tyro-login.otp.verify` | POST | `otp/verify` | LoginController@verifyOtp | Verify OTP code |
| `tyro-login.otp.resend` | POST | `otp/resend` | LoginController@resendOtp | Resend OTP |
| `tyro-login.otp.cancel` | POST | `otp/cancel` | LoginController@cancelOtp | Cancel OTP flow |
| `tyro-login.2fa.challenge` | GET | `2fa/challenge` | TwoFactorController@showChallenge | 2FA challenge form |
| `tyro-login.2fa.verify` | POST | `2fa/challenge` | TwoFactorController@verify | Verify 2FA code |
| `tyro-login.2fa.setup` | GET | `2fa/setup` | TwoFactorController@showSetup | 2FA setup page |
| `tyro-login.2fa.confirm` | POST | `2fa/setup` | TwoFactorController@confirm | Confirm TOTP setup |
| `tyro-login.2fa.skip` | POST | `2fa/skip` | TwoFactorController@skip | Skip 2FA setup |
| `tyro-login.2fa.ignore` | POST | `2fa/ignore` | TwoFactorController@ignore | Ignore 2FA with cookie |
| `tyro-login.2fa.recovery-codes` | GET | `2fa/recovery-codes` | TwoFactorController@showRecoveryCodes | Display recovery codes |
| `tyro-login.social.redirect` | GET | `login/{provider}` | SocialAuthController@redirect | OAuth provider redirect |
| `tyro-login.social.callback` | GET | `login/{provider}/callback` | SocialAuthController@callback | OAuth callback |
| `tyro-login.magic-link.request` | POST | `magic-link` | LoginController@requestMagicLink | Request magic link |
| `tyro-login.magic-link.login` | GET | `magic-link/{token}` | LoginController@magicLogin | Login via magic link |
| `tyro-login.lockout` | GET | `lockout` | LoginController@showLockout | Lockout page |

### Command Index

| Signature | Description |
|---|---|
| `tyro-login:install` | Full installation wizard |
| `tyro-login:publish` | Publish specific resources |
| `tyro-login:publish-style` | Publish shadcn theme or styles |
| `tyro-login:update-config` | Refresh config with latest defaults |
| `tyro-login:update-style` | Update published styles |
| `tyro-login:version` | Show version information |
| `tyro-login:doc` | Open documentation in browser |
| `tyro-login:star` | Open GitHub to star the repo |
| `tyro-login:verify-user` | Manually verify a user's email |
| `tyro-login:unverify-user` | Remove email verification |
| `tyro-login:reset-2fa` | Reset two-factor authentication |
| `tyro-login:magic-links` | Manage magic links (create, list, remove, flush) |
| `tyro-login:invite-links` | Manage invitation links (create, list, remove, flush) |
| `tyro-login:setup-ai-skill` | Install the Tyro Login AI skill for a chosen agent (Claude, Copilot, Codex, Gemini, Kilo, Laravel Boost, or all) with universal agents.md support |

### Environment Variable Index

| Env Var | Config Key | Type | Default |
|---|---|---|---|
| `TYRO_LOGIN_DEBUG` | `tyro-login.debug` | `bool` | `false` |
| `TYRO_LOGIN_LAYOUT` | `tyro-login.layout` | `string` | `'centered'` |
| `TYRO_LOGIN_APP_NAME` | `tyro-login.branding.app_name` | `string` | `env('APP_NAME', 'Tyro Login')` |
| `TYRO_LOGIN_REGISTRATION_ENABLED` | `tyro-login.registration.enabled` | `bool` | `true` |
| `TYRO_LOGIN_FEATURE_REMEMBER_ME` | `tyro-login.features.remember_me` | `bool` | `true` |
| `TYRO_LOGIN_FEATURE_FORGOT_PASSWORD` | `tyro-login.features.forgot_password` | `bool` | `true` |
| `TYRO_LOGIN_FEATURE_MAGIC_LINKS` | `tyro-login.features.magic_links_enabled` | `bool` | `false` |
| `TYRO_LOGIN_FEATURE_DISABLE_PASSWORD` | `tyro-login.features.disable_password` | `bool` | `false` |
| `TYRO_LOGIN_LOGIN_FIELD` | `tyro-login.login_field` | `string` | `'email'` |
| `TYRO_LOGIN_OTP_LENGTH` | `tyro-login.otp.length` | `int` | `6` |
| `TYRO_LOGIN_OTP_EXPIRE` | `tyro-login.otp.expire` | `int` | `10` |
| `TYRO_LOGIN_LOCKOUT_MAX_ATTEMPTS` | `tyro-login.lockout.max_attempts` | `int` | `5` |
| `TYRO_LOGIN_LOCKOUT_DURATION` | `tyro-login.lockout.duration` | `int` | `60` |

---

## Rule Files

| Tier | File | Covers |
|---|---|---|
| 0 | [rules/framework-mindset.md](rules/framework-mindset.md) | Framework-maintainer philosophy — the foundational decision-making framework for every rule below |
| 0 | [rules/security.md](rules/security.md) | Session protection, credential handling, rate limiting, CSRF, encryption, lockout |
| 0 | [rules/two-factor.md](rules/two-factor.md) | TOTP setup, verification, recovery codes, 2FA challenge flow, dual-cast-aware methods |
| 1 | [rules/service-provider.md](rules/service-provider.md) | Package lifecycle — register, boot, publishing, auto-discovery |
| 1 | [rules/routes.md](rules/routes.md) | Route definitions, middleware groups, naming conventions |
| 1 | [rules/models-and-casts.md](rules/models-and-casts.md) | Data layer — Eloquent models, relationships, EncryptedOrPlaintext cast, HasTwoFactorAuth trait |
| 1 | [rules/social-login.md](rules/social-login.md) | OAuth provider redirect, callback, social user linking, auto-registration, email verification |
| 1 | [rules/suspension.md](rules/suspension.md) | User suspension checks on login, social auth, and magic link flows |
| 2 | [rules/config-and-env.md](rules/config-and-env.md) | Config file structure, env var protocol, type casting, documentation |
| 2 | [rules/controllers.md](rules/controllers.md) | Controller patterns, validation, responses, multi-step auth flows, view data convention |
| 2 | [rules/mailables.md](rules/mailables.md) | Mailable classes, email templates, queueing, toggleable emails |
| 2 | [rules/email-templates.md](rules/email-templates.md) | Email Blade template structure, design conventions, email preview |
| 2 | [rules/commands.md](rules/commands.md) | Artisan command patterns, user lookup, interactive prompts |
| 2 | [rules/views-and-themes.md](rules/views-and-themes.md) | View structure, layout system, shadcn theme, view publishing |
| 2 | [rules/otp.md](rules/otp.md) | One-time password generation, delivery, verification, and resend limits |
| 2 | [rules/magic-login.md](rules/magic-login.md) | Magic link token generation, delivery, redemption, and expiration |
| 2 | [rules/captcha.md](rules/captcha.md) | Math captcha generation, session storage, per-form configuration, validation |
| 2 | [rules/invitation.md](rules/invitation.md) | Invitation links, referral tracking, self-referral prevention, InvitationHelper |
| 2 | [rules/password-policy.md](rules/password-policy.md) | Password complexity, common passwords, user-info disallowal, confirmation |
| 2 | [rules/registration.md](rules/registration.md) | Registration flow, auto-login, role assignment, post-registration branching |
| 2 | [rules/verification.md](rules/verification.md) | Email verification tokens, signed URLs, cache storage, resend logic |
| 2 | [rules/password-reset.md](rules/password-reset.md) | Password reset tokens, signed URLs, cache storage, user enumeration prevention |
| 3 | [rules/integration-boundaries.md](rules/integration-boundaries.md) | Soft dependencies, Tyro integration, Socialite, backward compatibility, deprecation |

---

## How to Apply

1. **Identify the scope** — Determine which component(s) the task touches (controllers, models, config, routes, etc.)
2. **Load the relevant rule files** — Read the rule files for the affected components
3. **Apply tier 0 rules first** — Framework mindset and security rules always take precedence
4. **Check cross-references** — Rules reference related rules when concerns overlap (e.g., controllers → security, models → backward compatibility)
5. **Validate against existing patterns** — The codebase itself is the source of truth; rules codify existing patterns, they do not invent new ones
6. **When rules conflict** — Lower-numbered tier wins; within a file, earlier rules take precedence
