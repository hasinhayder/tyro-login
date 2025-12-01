<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $appName }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        table { border-collapse: collapse !important; }
        body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; }
        a[x-apple-data-detectors] { color: inherit !important; text-decoration: none !important; font-size: inherit !important; font-family: inherit !important; font-weight: inherit !important; line-height: inherit !important; }
        @media screen and (max-width: 525px) {
            .mobile-padding { padding: 28px 24px !important; }
            .mobile-title { font-size: 22px !important; }
            .mobile-button { padding: 14px 28px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f9fafb; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
    
    <div style="display: none; max-height: 0; overflow: hidden;">
        Verify your email address for {{ $appName }}
        &nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;
    </div>
    
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f9fafb;">
        <tr>
            <td align="center" style="padding: 48px 16px;">
                
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width: 480px; background-color: #ffffff; border-radius: 12px; border: 1px solid #f3f4f6; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
                    
                    <!-- Header -->
                    <tr>
                        <td class="mobile-padding" style="padding: 36px 36px 28px 36px; border-bottom: 1px solid #f9fafb;">
                            @if(config('tyro-login.branding.logo'))
                                <img src="{{ config('tyro-login.branding.logo') }}" alt="{{ $appName }}" height="40" style="display: block;">
                            @else
                                <div style="font-size: 24px; font-weight: 600; letter-spacing: -0.025em; color: #111827;">{{ $appName }}</div>
                            @endif
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td class="mobile-padding" style="padding: 36px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="padding-bottom: 10px;">
                                        <h1 class="mobile-title" style="margin: 0; font-size: 26px; font-weight: 600; letter-spacing: -0.025em; color: #111827;">Verify your email address</h1>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-bottom: 28px;">
                                        <p style="margin: 0; font-size: 18px; color: #6b7280; line-height: 1.6;">
                                            Hi {{ $name }}, you've successfully created a {{ $appName }} account. Please click the button below to verify your email address.
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-bottom: 28px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td align="center" style="border-radius: 8px; background-color: #111827;">
                                                    <a href="{{ $verificationUrl }}" target="_blank" class="mobile-button" style="display: inline-block; padding: 14px 28px; font-size: 18px; font-weight: 500; color: #ffffff; text-decoration: none; border-radius: 8px;">
                                                        Verify Email
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <p style="margin: 0 0 10px 0; font-size: 15px; color: #9ca3af;">
                                            Or copy and paste this link into your browser:
                                        </p>
                                        <p style="margin: 0; font-size: 15px; color: #2563eb; word-break: break-all; line-height: 1.5;">
                                            {{ $verificationUrl }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding: 28px 36px; background-color: #f9fafb; border-radius: 0 0 12px 12px;">
                            <p style="margin: 0; font-size: 15px; color: #9ca3af;">
                                If you didn't create an account, please ignore this email.
                            </p>
                        </td>
                    </tr>
                    
                </table>
                
            </td>
        </tr>
    </table>
    
</body>
</html>
