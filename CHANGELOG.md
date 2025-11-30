# Changelog

All notable changes to `tyro-login` will be documented in this file.

## [1.1.0] - 2025-11-30

### Added
- **Math Captcha** - Simple addition/subtraction captcha for login and registration forms
  - Configurable via `TYRO_LOGIN_CAPTCHA_LOGIN` and `TYRO_LOGIN_CAPTCHA_REGISTER`
  - Customizable number range, labels, and error messages
  - No external dependencies required
- **Login OTP Verification** - Two-factor authentication via email OTP
  - Configurable OTP length (4-8 digits)
  - Configurable expiration time and resend limits
  - Beautiful OTP input with individual digit boxes
  - Resend cooldown and attempt tracking
  - Cache-based storage (no database required)
- **Debug Mode** - Global `TYRO_LOGIN_DEBUG` configuration
  - When enabled, logs OTP codes, verification URLs, and reset URLs
  - Disabled by default for production safety
  - Single toggle for all debug logging

### Changed
- All debug logging now requires `TYRO_LOGIN_DEBUG=true` to be set
- Improved code organization in LoginController and RegisterController

## [1.0.0] - 2025-11-30

### Added
- Initial release
- Login and registration forms with validation
- Three layout options: centered, split-left, split-right
- Dark/light theme with automatic detection and manual toggle
- Configurable branding (logo, colors, app name)
- Remember me functionality
- Cache-based lockout protection after failed login attempts
- Beautiful lockout page with countdown timer
- Email verification for new registrations (optional)
- Password reset with forgot password flow
- Secure signed URLs for verification and password reset tokens
- Configurable redirects after login/logout/registration
- Auto-login after registration option
- Integration with Tyro package for automatic role assignment
- Artisan commands: install, publish, version, doc, star
- Fully configurable page content (titles, subtitles, background text)
- Environment variable support for all configuration options
- Responsive design for all screen sizes
- CSRF protection and secure password handling
- Session regeneration on login
- Development-friendly: verification and reset URLs logged when debug enabled

