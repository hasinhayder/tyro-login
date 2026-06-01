# Views and Themes

**Tier:** 2 — Implementation
**Applies to:** All Blade templates under `resources/views/`, `resources/views/layouts/auth.blade.php`, `resources/views/partials/`, email templates
**Cross-references:** [service-provider.md](service-provider.md) (view publishing, view namespaces), [controllers.md](controllers.md) (passing data to views), [config-and-env.md](config-and-env.md) (page content config)

Rules for Blade view structure, the config-driven layout system, the shadcn theme system, and view publishing conventions.

---

## Config-Driven Layout System via Partial Includes

### Why It Matters

Consumers need to choose between different visual layouts (centered, split-left, split-right, fullscreen, card) without overriding the controller logic or duplicating Blade templates. A config-driven layout parameter that controls which partial is included keeps the controller agnostic to presentation details.

### Incorrect

```php
// Layout hardcoded in controller — consumer must override the controller
public function showLoginForm(): View
{
    return view('tyro-login::login', [
        'layout' => 'centered',
        // ...
    ]);
}
```

### Correct

```php
// Layout from config — consumer changes it without touching code
public function showLoginForm(): View
{
    return view('tyro-login::login', [
        'layout' => config('tyro-login.layout', 'centered'),
        // ...
    ]);
}
```

```blade
{{-- The auth layout handles layout inclusion based on config --}}
@extends('tyro-login::layouts.auth')

@section('content')
    {{-- Page content --}}
@endsection
```

```blade
{{-- layouts/auth.blade.php — includes the correct layout partial --}}
@includeWhen($layout === 'centered', 'tyro-login::partials.layout-centered')
@includeWhen($layout === 'split-left', 'tyro-login::partials.layout-split-left')
@includeWhen($layout === 'split-right', 'tyro-login::partials.layout-split-right')
@includeWhen($layout === 'fullscreen', 'tyro-login::partials.layout-fullscreen')
@includeWhen($layout === 'card', 'tyro-login::partials.layout-card')
```

### Notes

- The layout config (`tyro-login.layout`) controls which layout partial is used.
- Each layout variant is a separate partial file.
- The controller passes `$layout` to the view; the view delegates to the partial.
- New layouts can be added by creating a new partial and updating the config documentation.

---

## Section-Based View Inheritance

### Why It Matters

Consumers need to override specific parts of auth pages (the header, the footer, the form content) without rewriting the entire view. Section-based inheritance via `@section` and `@yield` allows consumers to publish a single view and override only the sections they need.

### Incorrect

```php
// No sections — consumer must override the entire view to change anything
return view('tyro-login::login', $data);
// Published login.blade.php — consumer must copy the entire file
```

### Correct

```php
// Sections in the base layout — consumer overrides specific parts
```

```blade
{{-- layouts/auth.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <title>@yield('title', config('app.name'))</title>
    @stack('styles')
</head>
<body>
    @yield('header')
    @yield('content')
    @yield('footer')
    @stack('scripts')
</body>
</html>
```

```blade
{{-- login.blade.php — overrides specific sections --}}
@extends('tyro-login::layouts.auth')

@section('title', __('Login'))

@section('content')
    {{-- Login form --}}
@endsection

@push('styles')
    {{-- Page-specific styles --}}
@endpush
```

### Notes

- Use `@yield()` with defaults for optional sections: `@yield('title', config('app.name'))`.
- Use `@stack()` and `@push()` for assets — avoid `@section('styles')` which can only be defined once.
- The layout should yield at minimum: `title`, `content`, and have stacks for `styles` and `scripts`.

---

## shadcn CSS Variables for Theming

### Why It Matters

The shadcn UI design system uses CSS custom properties (variables) for all colors, allowing complete theme customization without touching the component templates. This is the correct approach for a package that ships with a visual theme — consumers customize variables, not templates.

### Incorrect

```blade
{{-- Hardcoded colors — consumer must edit the Blade template to change colors --}}
<style>
    .btn-primary {
        background-color: #007bff;
        color: white;
    }
</style>
```

### Correct

```blade
{{-- CSS custom properties — consumer overrides in CSS or via theme partial --}}
<style>
    :root {
        --color-primary: oklch(0.5 0.2 240);
        --color-primary-foreground: oklch(0.98 0 0);
        --color-background: oklch(1 0 0);
        --color-foreground: oklch(0.1 0 0);
        --color-muted: oklch(0.96 0 0);
        --color-muted-foreground: oklch(0.55 0 0);
        --color-border: oklch(0.92 0 0);
        --radius: 0.5rem;
    }

    .dark {
        --color-primary: oklch(0.6 0.25 250);
        --color-primary-foreground: oklch(0.1 0 0);
        --color-background: oklch(0.15 0 0);
        --color-foreground: oklch(0.95 0 0);
        --color-muted: oklch(0.2 0 0);
        --color-muted-foreground: oklch(0.65 0 0);
        --color-border: oklch(0.3 0 0);
    }

    .btn-primary {
        background-color: var(--color-primary);
        color: var(--color-primary-foreground);
        border-radius: var(--radius);
    }
</style>
```

### Notes

- The shadcn theme is published as a separate `shadcn-theme.blade.php` partial.
- The theme partial is published via `tyro-login:publish-style --theme-only`.
- All component styles use `var(--color-*)` references, not hardcoded color values.
- The dark mode toggle uses the `.dark` class on the HTML element and `prefers-color-scheme` detection.

---

## Config-Driven Page Content

### Why It Matters

Page titles, subtitles, help text, and background copy are part of the consumer's brand experience. Hardcoding these in Blade templates forces consumers to either accept the default wording or override entire views. Config-driven content allows text customization without view overrides.

### Incorrect

```blade
{{-- Hardcoded text — consumer must override the view to change --}}
<h1>Welcome Back</h1>
<p>Sign in to your account to continue.</p>
```

### Correct

```blade
{{-- Config-driven text — consumer changes in config file --}}
<h1>{{ config('tyro-login.pages.login.title', 'Welcome Back') }}</h1>
<p>{{ config('tyro-login.pages.login.subtitle', 'Sign in to your account to continue.') }}</p>
```

### Notes

- Config key pattern: `tyro-login.pages.{page}.{field}`.
- Page keys match route names: `login`, `register`, `forgot-password`, `reset-password`, `verify-email`, `two-factor-challenge`.
- Fields: `title`, `subtitle`, `help_text`, `background_copy` (for split/fullscreen layouts).
- Default values are polite and generic — consumers customize for their brand.

---

## Published Views Override Package Views via Laravel Convention

### Why It Matters

When consumers publish views with `php artisan vendor:publish --tag=tyro-login-views`, the published files land in `resources/views/vendor/tyro-login/`. Laravel's view system checks this directory before checking the package's `tyro-login::` namespace. This is the standard Laravel convention and must not be bypassed.

### Incorrect

```php
// Custom view loading — breaks Laravel's standard publishing convention
$this->loadViewsFrom(
    resource_path('views/tyro-login'),
    'tyro-login'
);
```

### Correct

```php
// Standard loadViewsFrom — Laravel automatically checks vendor directory first
$this->loadViewsFrom(
    __DIR__ . '/../../resources/views',
    'tyro-login'
);

// After publisher runs, the view lookup order is:
// 1. resources/views/vendor/tyro-login/ (published consumer override)
// 2. package's resources/views/ (package default)
// This is automatic — no additional configuration needed.
```

### Notes

- Publish path: `__DIR__ . '/../../resources/views' => resource_path('views/vendor/tyro-login')`.
- Never override the standard view loading behavior.
- Document in the config comments how publishing works for each publish tag.
