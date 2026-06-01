# Email Templates

**Tier:** 2 — Implementation
**Applies to:** All email Blade templates under `resources/views/emails/`
**Cross-references:** [mailables.md](mailables.md) (mailable classes, config-driven subjects, queueing), [views-and-themes.md](views-and-themes.md) (view publishing, layout conventions), [security.md](security.md) (masked data in emails)

Rules for HTML email template structure, responsive design, fallback rendering, and security-safe content.

---

## Use Inline Styles for Email Compatibility

### Why It Matters

Most email clients (Gmail, Outlook, Apple Mail) strip `<style>` blocks from the `<head>` section. Only inline styles are reliably rendered across all email clients. Using a `<style>` block alone results in plain-text fallback in many major email clients, breaking the visual design.

### Incorrect

```html
{{-- Styles in head — stripped by Gmail, Outlook web --}}
<head>
    <style>
        .button { background-color: #007bff; color: white; }
    </style>
</head>
<body>
    <a href="{{ $url }}" class="button">Click Here</a>
</body>
```

### Correct

```html
{{-- Inline styles — rendered by all email clients --}}
<a href="{{ $url }}"
   style="display: inline-block; background-color: #007bff; color: #ffffff;
          text-decoration: none; padding: 12px 24px; border-radius: 4px;
          font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;">
    Click Here
</a>
```

### Notes

- Every style must be inline on the element — no CSS classes or `<style>` block.
- Use a consistent style system across all email templates (same button styles, same font stack, same spacing).
- For complex emails, use a tool like MJML or a Laravel email package that handles inlining.

---

## Include Plain-Text Fallback for Every Email

### Why It Matters

Email clients and security gateways render emails in plain text by default in many contexts (SPAM filters, accessibility readers, CLI email clients). An email with only HTML content may appear blank or be flagged as suspicious. Every mailable must include a plain-text version.

### Incorrect

```php
// HTML only — plain-text readers see nothing
public function build(): self
{
    return $this->view('tyro-login::emails.otp');
}
```

### Correct

```php
// HTML + plain text — renders in any email client
public function build(): self
{
    return $this->view('tyro-login::emails.otp')
        ->text('tyro-login::emails.plain.otp');
}
```

```html
{{-- resources/views/emails/plain/otp.blade.php --}}
{{ $userName }},

Your one-time password is: {{ $otp }}

This code will expire in {{ $expiresInMinutes }} minutes.

If you did not request this code, please ignore this email.
```

### Notes

- The plain-text view lives under `resources/views/emails/plain/`.
- It contains the same information without HTML formatting.
- The plain-text view uses the same data as the HTML view via `with()`.

---

## Use MSO Conditional Comments for Outlook on Windows

### Why It Matters

Outlook on Windows uses Microsoft Word's rendering engine, not a web browser. It does not support standard CSS properties like `border-radius`, `background-position`, or `flexbox`. MSO (Microsoft Office) conditional comments provide Outlook-specific workarounds that other email clients ignore.

### Incorrect

```html
{{!-- No Outlook fallback — button is square in Outlook --}}
<a href="{{ $url }}" style="border-radius: 8px; background-color: #007bff;">
    Click Here
</a>
```

### Correct

```html
{{!-- MSO conditional comment for Outlook — VML rounded button --}}
<!--[if mso]>
<v:roundrect xmlns:v="urn:schemas-microsoft-com:vml"
             href="{{ $url }}"
             style="height: 44px; v-text-anchor: middle; width: 200px;"
             arcsize="10%"
             strokecolor="#007bff"
             fillcolor="#007bff">
    <w:anchorlock/>
    <center style="color: #ffffff; font-family: sans-serif; font-size: 14px;">
        Click Here
    </center>
</v:roundrect>
<![endif]-->
<!--[if !mso]><!-- -->
<a href="{{ $url }}"
   style="display: inline-block; border-radius: 8px; background-color: #007bff;
          color: #ffffff; padding: 12px 24px; text-decoration: none;">
    Click Here
</a>
<!--<![endif]-->
```

### Notes

- Use MSO conditionals for: buttons, background images, multi-column layouts.
- The `<!--[if !mso]><!-- -->` syntax hides the HTML version from Outlook while showing it to all other clients.
- Not every email template needs MSO — only those with complex layouts or images.

---

## Mobile-First Responsive Design

### Why It Matters

Over 60% of emails are opened on mobile devices. Emails designed for desktop only are unreadable on mobile — tiny fonts, overflowing tables, microscopic buttons. A mobile-first approach ensures readability on small screens first, then scales up for desktop.

### Incorrect

```html
{{-- Desktop-only table — tiny on mobile --}}
<table width="600" style="font-size: 14px;">
    <tr>
        <td>{{ $content }}</td>
    </tr>
</table>
```

### Correct

```html
{{-- Mobile-first container — full width on mobile, centered on desktop --}}
<table role="presentation" cellpadding="0" cellspacing="0" border="0"
       style="width: 100%; max-width: 600px; margin: 0 auto;">
    <tr>
        <td style="padding: 20px; font-family: -apple-system, BlinkMacSystemFont,
                   'Segoe UI', Roboto, sans-serif; font-size: 16px; line-height: 1.5;">
            {{ $content }}
        </td>
    </tr>
</table>

{{-- Responsive button — full width on mobile, auto on desktop --}}
<!--[if mso]>
<v:roundrect ... style="width: 280px;">
<![endif]-->
<a href="{{ $url }}"
   style="display: block; width: 100%; max-width: 280px; margin: 0 auto;
          text-align: center; padding: 14px 24px; font-size: 16px;">
    Click Here
</a>
<!--[if !mso]><!-- -->
</a>
<!--<![endif]-->
```

### Notes

- Use `max-width` containers — not fixed `width`.
- Font size should be at least 14px body, 16px for buttons (prevents iOS auto-zoom).
- Buttons should have at least 44px height for touch targets.
- Test every email template on: Gmail mobile app, iOS Mail, Outlook mobile, and a desktop client.

---

## All Dynamic Content Must Be Escaped

### Why It Matters

Email templates render user-provided data (user name, email, verification URLs). If a user's name contains HTML or JavaScript, unescaped output in an email can render malicious content or trigger SPAM filters. Laravel's Blade auto-escapes `{{ }}` but raw `{!! !!}` output must be justified.

### Incorrect

```html
{{-- Unescaped user name — XSS risk in HTML-rendered emails --}}
<p>Hello {!! $userName !!},</p>
```

### Correct

```html
{{-- Escaped user name — safe for all email clients --}}
<p>Hello {{ $userName }},</p>

{{-- Only use raw output for trusted, hardcoded HTML --}}
<p>Click the button below to verify your email address:</p>
```

### Notes

- Always use `{{ }}` for user-provided data in email templates.
- Never use `{!! !!}` for user names, email addresses, or any dynamic content.
- URLs are safe inside `{{ }}` because they are rendered as text — use `{{ $url }}` in the `href` attribute, not `{!! $url !!}`.
- The only exception is hardcoded HTML in the template itself (icons, spacers, dividers).

---

## All Emails Require a Plain-Text Security Notice

### Why It Matters

Phishing emails often mimic transactional emails. Every email sent by Tyro Login must include a security notice explaining that the recipient should never share the code or link with anyone, and that Tyro Login will never ask for their password.

### Incorrect

```html
{{-- No security notice — recipient may not know this is legitimate --}}
<p>Your verification code is: {{ $otp }}</p>
```

### Correct

```html
{{-- Security notice — every email must include one --}}
<table role="presentation" cellpadding="0" cellspacing="0" border="0"
       style="width: 100%; margin-top: 32px; padding-top: 16px;
              border-top: 1px solid #e5e7eb;">
    <tr>
        <td style="font-family: -apple-system, sans-serif; font-size: 12px;
                   color: #6b7280; line-height: 1.5;">
            <p style="margin: 0;">
                <strong>Security Notice:</strong>
                Never share this code with anyone.
                {{ config('app.name') }} will never ask for your
                password or verification code via email or phone.
            </p>
            <p style="margin: 8px 0 0 0;">
                If you did not request this email, please ignore it.
                Your account security has not been compromised.
            </p>
        </td>
    </tr>
</table>
```

### Notes

- The security notice is in the smallest font size and muted color — intentional to not distract but still present.
- The notice must be in both HTML and plain-text versions.
- Wording should be consistent across all email types.
