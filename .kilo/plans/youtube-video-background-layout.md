# YouTube Video Background Layout — Feature Plan

## Overview

Add a 6th layout system (`youtube-video`) where the login form sits centered on the screen with a YouTube video playing in the background. The video is muted (no sound), and the user can configure blur, overlay color, and overlay opacity.

---

## 1. Configuration (`config/tyro-login.php`)

### New Config Section

Add after the `'background_image'` key (line 73):

```php
/*
|--------------------------------------------------------------------------
| YouTube Video Background
|--------------------------------------------------------------------------
|
| Used when 'layout' is set to 'youtube-video'.
| The video plays muted in the background with configurable blur and overlay.
|
| Environment: TYRO_LOGIN_VIDEO_URL, TYRO_LOGIN_VIDEO_BLUR,
|              TYRO_LOGIN_VIDEO_OVERLAY_COLOR, TYRO_LOGIN_VIDEO_OVERLAY_OPACITY
|
*/
'video_background' => [
    // YouTube video URL or ID (e.g. 'https://www.youtube.com/watch?v=dQw4w9WgXcQ')
    'url' => env('TYRO_LOGIN_VIDEO_URL', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'),

    // CSS blur amount (e.g. '0px', '4px', '8px')
    'blur' => env('TYRO_LOGIN_VIDEO_BLUR', '4px'),

    // Overlay color (any valid CSS color)
    'overlay_color' => env('TYRO_LOGIN_VIDEO_OVERLAY_COLOR', 'rgba(17, 24, 39, 0.85)'),

    // Overlay opacity (0 to 1, applied on top of the color)
    'overlay_opacity' => env('TYRO_LOGIN_VIDEO_OVERLAY_OPACITY', 0.85),
],
```

### Update Layout Comment Block

Update the `layout` config comment (lines 37-58) to document the 6th layout option:

```
| 'youtube-video'
|    Centered form with a full-screen YouTube video background
|    Perfect for: Creative portfolios, video-centric brands, modern showcases
```

---

## 2. Controller Changes (6 files)

All view-rendering methods across these controllers currently pass `'layout'`, `'branding'`, `'backgroundImage'` to the view. Each must now also pass the `videoBackground` config.

### Files to modify:

| Controller | Methods to update |
|---|---|
| `src/Http/Controllers/LoginController.php` | `showLoginForm()`, `showLockout()`, `showOtpForm()` |
| `src/Http/Controllers/RegisterController.php` | `showRegistrationForm()` |
| `src/Http/Controllers/PasswordResetController.php` | `showForgotPasswordForm()`, `showResetPasswordForm()` |
| `src/Http/Controllers/VerificationController.php` | `showNotice()`, `showEmailNotVerified()` |
| `src/Http/Controllers/TwoFactorController.php` | `showChallenge()`, `showSetup()`, `showRecoveryCodes()` |
| `src/Http/Controllers/SocialAuthController.php` | (check if any views are rendered directly) |

### Pattern for each controller method:

Add this key to the existing `view()` data array:

```php
'videoBackground' => config('tyro-login.video_background'),
```

---

## 3. View Changes (11 Blade files)

Every auth page view follows this pattern at the top of the `@section('content')`:

```blade
<div class="auth-container {{ $layout }}" @if($layout==='fullscreen') style="background-image: url('{{ $backgroundImage }}');" @endif>
    @if(in_array($layout, ['split-left', 'split-right']))
        <div class="background-panel" ...>
    @endif
```

### Template changes needed:

**a) All 11 views** — Add `youtube-video` to the `auth-container` div:

```blade
<div class="auth-container {{ $layout }}"
    @if($layout==='fullscreen') style="background-image: url('{{ $backgroundImage }}');" @endif
    @if($layout==='youtube-video') id="tyro-youtube-container" @endif>
```

**b) All 11 views** — After the closing `</div>` of `auth-container`, add the video embed partial:

```blade
@if($layout==='youtube-video')
    @include('tyro-login::partials.youtube-video')
@endif
```

**c) Views list** (all 11 need the above changes):

1. `login.blade.php`
2. `register.blade.php`
3. `forgot-password.blade.php`
4. `reset-password.blade.php`
5. `verify-email.blade.php`
6. `email-not-verified.blade.php`
7. `otp-verify.blade.php`
8. `lockout.blade.php`
9. `two-factor-challenge.blade.php`
10. `two-factor-setup.blade.php`
11. `two-factor-recovery-codes.blade.php`

---

## 4. New Partial View: `resources/views/partials/youtube-video.blade.php`

This partial renders the YouTube video iframe, overlay, and the JavaScript for the IFrame API.

### Structure:

```
<div id="tyro-video-background">
    <!-- Video iframe placeholder -->
    <div id="tyro-youtube-player"></div>
    <!-- Color overlay -->
    <div class="tyro-video-overlay"></div>
</div>
```

### CSS (in styles.blade.php, covered in section 5):

- `#tyro-video-background`: fixed/absolute, full viewport, z-index: 0
- `#tyro-youtube-player`: absolute, full size, pointer-events: none
- `.tyro-video-overlay`: absolute, full size, uses the configured color + opacity

### JavaScript (inline in the partial):

- Load YouTube IFrame API (`https://www.youtube.com/iframe_api`)
- `onYouTubeIframeAPIReady()` — create player with:
  - Extracted video ID from URL
  - `mute: 1` (no sound)
  - `autoplay: 1`
  - `loop: 1` (with playlist param equal to video ID)
  - `controls: 0`, `showinfo: 0`, `rel: 0`
  - `disablekb: 1`, `modestbranding: 1`
- Resize handler to make video cover the viewport (similar to `background-size: cover`)
- Cleanup on page navigation (destroy player)

---

## 5. CSS Changes (`resources/views/partials/styles.blade.php`)

Add after the `.card` layout styles (after line 578):

```css
/* YouTube Video Background Layout */
.auth-container.youtube-video {
    padding: 0;
    position: relative;
    overflow: hidden;
    background-color: #000;
}

.auth-container.youtube-video .form-panel {
    position: relative;
    z-index: 10;
}

.auth-container.youtube-video .form-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 1rem;
    padding: 2.5rem;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

html.dark .auth-container.youtube-video .form-card {
    background: rgba(26, 26, 26, 0.95);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Video background container */
#tyro-video-background {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 0;
    overflow: hidden;
}

#tyro-youtube-player {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 100vw;
    height: 56.25vw; /* 16:9 aspect ratio */
    min-height: 100vh;
    min-width: 177.78vh; /* 16:9 aspect ratio */
}

/* Overlay on top of video */
.tyro-video-overlay {
    position: absolute;
    inset: 0;
    z-index: 1;
    /* Color and opacity applied inline via style attribute */
}
```

### Responsive:
```css
@media (max-width: 768px) {
    .auth-container.youtube-video .form-card {
        padding: 1.5rem;
    }
}
```

---

## 6. YouTube URL Parsing Helper (optional utility)

Add a helper function to extract the YouTube video ID from various URL formats:

| Input Format | Extracted ID |
|---|---|
| `https://www.youtube.com/watch?v=VIDEO_ID` | `VIDEO_ID` |
| `https://youtu.be/VIDEO_ID` | `VIDEO_ID` |
| `https://www.youtube.com/embed/VIDEO_ID` | `VIDEO_ID` |
| `VIDEO_ID` (raw) | `VIDEO_ID` |

This can be implemented as a JavaScript function in the partial, or as a small PHP helper in a service class.

**Recommendation**: Keep it in JavaScript within the partial since it's presentation logic. The video ID extraction only needs to happen on the client side for the IFrame API.

---

## 7. File Change Summary

| # | File | Change Type | Description |
|---|---|---|---|
| 1 | `config/tyro-login.php` | **Edit** | Add `video_background` config section; update layout comment |
| 2 | `resources/views/partials/styles.blade.php` | **Edit** | Add CSS for `.youtube-video` layout |
| 3 | `resources/views/partials/youtube-video.blade.php` | **Create** | New partial: iframe + overlay + JS |
| 4 | `resources/views/login.blade.php` | **Edit** | Add youtube-video conditions |
| 5 | `resources/views/register.blade.php` | **Edit** | Same |
| 6 | `resources/views/forgot-password.blade.php` | **Edit** | Same |
| 7 | `resources/views/reset-password.blade.php` | **Edit** | Same |
| 8 | `resources/views/verify-email.blade.php` | **Edit** | Same |
| 9 | `resources/views/email-not-verified.blade.php` | **Edit** | Same |
| 10 | `resources/views/otp-verify.blade.php` | **Edit** | Same |
| 11 | `resources/views/lockout.blade.php` | **Edit** | Same |
| 12 | `resources/views/two-factor-challenge.blade.php` | **Edit** | Same |
| 13 | `resources/views/two-factor-setup.blade.php` | **Edit** | Same |
| 14 | `resources/views/two-factor-recovery-codes.blade.php` | **Edit** | Same |
| 15 | `src/Http/Controllers/LoginController.php` | **Edit** | Pass `videoBackground` to 3 view methods |
| 16 | `src/Http/Controllers/RegisterController.php` | **Edit** | Pass `videoBackground` |
| 17 | `src/Http/Controllers/PasswordResetController.php` | **Edit** | Pass `videoBackground` to 2 methods |
| 18 | `src/Http/Controllers/VerificationController.php` | **Edit** | Pass `videoBackground` to 2 methods |
| 19 | `src/Http/Controllers/TwoFactorController.php` | **Edit** | Pass `videoBackground` to 3 methods |
| 20 | `src/Http/Controllers/SocialAuthController.php` | **Edit** | Pass `videoBackground` (if applicable) |

---

## 8. Edge Cases & Considerations

- **No video URL configured**: If `video_background.url` is empty/null, fall back to a solid dark background (configurable via a fallback CSS background-color)
- **Invalid YouTube URL**: The JS parser will fail gracefully — player will show "Video unavailable" in the embed, which is acceptable (still looks like a dark background)
- **YouTube IFrame API not loaded** (ad blocker, network failure): The overlay remains, showing a solid dark background. Always acceptable.
- **Mobile/touch devices**: The video plays muted automatically (policy-compliant), pointer-events disabled on the iframe so taps go to the form
- **Performance**: The iframe is loaded only when `layout === 'youtube-video'`, so no performance impact on other layouts
- **YouTube embed restrictions**: Some videos may have embedding disabled; this is acceptable UX — a dark box with "Video unavailable" shows
