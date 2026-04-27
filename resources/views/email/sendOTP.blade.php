<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ config('app.name') }} — Verification Code</title>

    <style>
        /* Client resets */
        body,
        table,
        td,
        a {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table,
        td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
            border-collapse: collapse;
        }

        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            outline: none;
            text-decoration: none;
            display: block;
        }

        body {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            background-color: #f3f6fb;
            font-family: 'Segoe UI', Roboto, -apple-system, BlinkMacSystemFont, 'Helvetica Neue', Arial, sans-serif;
        }

        a {
            color: #0d9488;
            text-decoration: none;
        }

        /* Mobile */
        @media screen and (max-width: 600px) {
            .container {
                width: 100% !important;
            }

            .px-32 {
                padding-left: 24px !important;
                padding-right: 24px !important;
            }

            .otp-digit {
                width: 38px !important;
                height: 50px !important;
                font-size: 24px !important;
                line-height: 50px !important;
                margin: 0 3px !important;
            }

            .h1 {
                font-size: 22px !important;
                line-height: 30px !important;
            }
        }
    </style>
</head>

<body>
    <!-- Preheader (hidden preview text) -->
    <div
        style="display:none;font-size:1px;color:#f3f6fb;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">
        Your one-time verification code is ready. It expires in a few minutes — don't share it with anyone.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
        style="background-color:#f3f6fb;">
        <tr>
            <td align="center" style="padding:32px 16px;">

                <!-- Card -->
                <table role="presentation" class="container" width="600" cellpadding="0" cellspacing="0" border="0"
                    style="width:600px;max-width:600px;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(15,23,42,0.06);">

                    <!-- Brand header -->
                    <tr>
                        <td align="center"
                            style="background:linear-gradient(135deg,#0f766e 0%,#0d9488 50%,#14b8a6 100%);padding:36px 32px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center">
                                        <!-- Logo mark (CSS-only, swap with <img> when an asset is available) -->
                                        <div
                                            style="width:64px;height:64px;border-radius:16px;background-color:#ffffff;display:inline-block;text-align:center;line-height:64px;font-size:28px;font-weight:700;color:#0f766e;letter-spacing:-1px;font-family:'Segoe UI',Roboto,Arial,sans-serif;">
                                            {{ strtoupper(substr(config('app.name', 'NGO'), 0, 2)) }}
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding-top:14px;">
                                        <div
                                            style="color:#ffffff;font-size:18px;font-weight:600;letter-spacing:0.4px;font-family:'Segoe UI',Roboto,Arial,sans-serif;">
                                            {{ config('app.name') }}
                                        </div>
                                        <div
                                            style="color:rgba(255,255,255,0.85);font-size:12px;letter-spacing:1.5px;text-transform:uppercase;margin-top:4px;font-family:'Segoe UI',Roboto,Arial,sans-serif;">
                                            Account Verification
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td class="px-32" style="padding:40px 48px 16px 48px;">
                            <h1 class="h1"
                                style="margin:0 0 12px 0;color:#0f172a;font-size:26px;line-height:34px;font-weight:700;font-family:'Segoe UI',Roboto,Arial,sans-serif;">
                                Hi {{ $name }}, verify it's you
                            </h1>
                            <p
                                style="margin:0 0 28px 0;color:#475569;font-size:15px;line-height:24px;font-family:'Segoe UI',Roboto,Arial,sans-serif;">
                                Use the one-time verification code below to confirm your account. This code is unique
                                to this request — please don't share it with anyone, including {{ config('app.name') }}
                                staff.
                            </p>
                        </td>
                    </tr>

                    <!-- OTP boxes -->
                    <tr>
                        <td class="px-32" align="center" style="padding:0 48px 8px 48px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                                style="margin:0 auto;">
                                <tr>
                                    @foreach (str_split((string) $otp) as $digit)
                                        <td class="otp-digit" align="center"
                                            style="width:48px;height:60px;margin:0 4px;background-color:#f0fdfa;border:2px solid #99f6e4;border-radius:12px;color:#0f766e;font-size:28px;line-height:60px;font-weight:700;font-family:'SF Mono','Menlo','Consolas',monospace;letter-spacing:0;">
                                            {{ $digit }}
                                        </td>
                                        @if (!$loop->last)
                                            <td style="width:8px;">&nbsp;</td>
                                        @endif
                                    @endforeach
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Expiry callout -->
                    <tr>
                        <td class="px-32" style="padding:24px 48px 8px 48px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                                style="background-color:#fffbeb;border:1px solid #fde68a;border-radius:10px;">
                                <tr>
                                    <td style="padding:14px 18px;">
                                        <p
                                            style="margin:0;color:#92400e;font-size:13px;line-height:20px;font-family:'Segoe UI',Roboto,Arial,sans-serif;">
                                            <strong>Heads up —</strong> this code expires at
                                            <strong>{{ $expired }}</strong>. Request a new one if it lapses.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Security note -->
                    <tr>
                        <td class="px-32" style="padding:20px 48px 8px 48px;">
                            <p
                                style="margin:0;color:#64748b;font-size:13px;line-height:20px;font-family:'Segoe UI',Roboto,Arial,sans-serif;">
                                Didn't request this? You can safely ignore this email — your account stays secure.
                                If you keep getting these messages, please contact our support team.
                            </p>
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td class="px-32" style="padding:28px 48px 0 48px;">
                            <div style="height:1px;background-color:#e2e8f0;line-height:1px;font-size:1px;">&nbsp;</div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td class="px-32" align="center" style="padding:24px 48px 36px 48px;">
                            <p
                                style="margin:0 0 6px 0;color:#0f172a;font-size:13px;font-weight:600;font-family:'Segoe UI',Roboto,Arial,sans-serif;">
                                {{ config('app.name') }}
                            </p>
                            <p
                                style="margin:0;color:#94a3b8;font-size:12px;line-height:18px;font-family:'Segoe UI',Roboto,Arial,sans-serif;">
                                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.<br>
                                This is an automated message — please don't reply to this email.
                            </p>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
</body>

</html>
