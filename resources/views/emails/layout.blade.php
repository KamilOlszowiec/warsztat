<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>@yield('tytul')</title>
</head>
<body style="margin: 0; padding: 0; background: #EDEDF0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background: #EDEDF0;">
    <tr>
        <td align="center" style="padding: 28px 12px;">

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                   style="max-width: 540px; background: #FFFFFF; border: 1px solid #E4E4E7; border-radius: 4px;">

                <tr>
                    <td style="background: #1F2128; padding: 18px 28px; border-radius: 4px 4px 0 0;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td width="32" height="32" align="center" valign="middle"
                                    style="width: 32px; height: 32px; background: #EA580C; border-radius: 2px; color: #FFFFFF; font-family: -apple-system, 'Segoe UI', Helvetica, Arial, sans-serif; font-size: 16px; font-weight: 700;">
                                    W
                                </td>
                                <td style="padding-left: 12px; color: #FAFAFA; font-family: -apple-system, 'Segoe UI', Helvetica, Arial, sans-serif; font-size: 16px; font-weight: 700;">
                                    Warsztat
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding: 32px 28px; font-family: -apple-system, 'Segoe UI', Helvetica, Arial, sans-serif; color: #27272A;">
                        @yield('tresc')
                    </td>
                </tr>
            </table>

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 540px;">
                <tr>
                    <td align="center" style="padding: 18px 10px 0; font-family: -apple-system, 'Segoe UI', Helvetica, Arial, sans-serif; font-size: 12px; line-height: 1.6; color: #71717A;">
                        Wiadomość wysłana automatycznie z systemu rezerwacji warsztatu. Prosimy na nią nie odpowiadać.
                    </td>
                </tr>
            </table>

        </td>
    </tr>
</table>
</body>
</html>