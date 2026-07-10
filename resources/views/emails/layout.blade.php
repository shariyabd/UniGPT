<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f7;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f7;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">
                    <tr>
                        <td style="padding:0 24px 16px;">
                            <span style="font-size:20px;font-weight:700;color:#6366f1;">{{ config('app.name') }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#ffffff;border-radius:12px;padding:32px 24px;border:1px solid #e5e7eb;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 24px;font-size:12px;color:#6b7280;">
                            You are receiving this because email updates are enabled in your
                            <a href="{{ route('settings') }}" style="color:#6366f1;">{{ config('app.name') }} settings</a>.
                            Turn off "Email digest" there to stop these emails.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
