<?php
/**
 * Canvasign Email Template
 *
 * Email template using the canvasign design aesthetic with inline CSS
 * for maximum compatibility across email clients (Gmail, Outlook, Apple Mail).
 *
 * Variables:
 *   $content  - The main email body content (HTML allowed)
 *
 * @filesource  canvasign.blade.php
 *
 * @author      wisnuwidi@canvastack.com
 * @copyright   wisnuwidi
 * @email       wisnuwidi@canvastack.com
 */
?>
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <title>{{ config('app.name') }}</title>
    <style type="text/css">
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        a[x-apple-data-detectors] { color: inherit !important; text-decoration: none !important; font-size: inherit !important; font-family: inherit !important; font-weight: inherit !important; line-height: inherit !important; }
        u + #body a { color: inherit; text-decoration: none; font-size: inherit; font-family: inherit; font-weight: inherit; line-height: inherit; }
        #MessageViewBody a { color: inherit; text-decoration: none; font-size: inherit; font-family: inherit; font-weight: inherit; line-height: inherit; }
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; max-width: 100% !important; }
            .email-padding { padding: 20px !important; }
        }
    </style>
</head>
<body id="body" width="100%" style="margin: 0; padding: 0 !important; mso-line-height-rule: exactly; background-color: #0f1117;">

    <center role="article" aria-roledescription="email" lang="en" style="width: 100%; background-color: #0f1117;">
        <!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#0f1117;"><tr><td><![endif]-->

        <!-- Preheader -->
        <div style="max-height:0;overflow:hidden;mso-hide:all;" aria-hidden="true">
            {{ config('app.name') }} — {{ strip_tags($content ?? '') }}
        </div>

        <!-- Email Container -->
        <table align="center" role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="margin:auto;" class="email-container">

            <!-- HEADER: Branding -->
            <tr>
                <td style="padding:40px 20px 20px;text-align:center;" class="email-padding">
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center">
                        <tr>
                            <td style="vertical-align:middle;padding-right:12px;">
                                <div style="display:inline-block;width:40px;height:40px;background:linear-gradient(135deg,#6366f1 0%,#8b5cf6 100%);border-radius:10px;text-align:center;line-height:40px;font-size:20px;color:#ffffff;font-weight:bold;">&#9700;</div>
                            </td>
                            <td style="vertical-align:middle;">
                                <span style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;font-size:22px;font-weight:700;color:#f1f5f9;letter-spacing:-0.5px;">
                                    {{ config('app.name') }}<span style="color:#6366f1;">.</span>
                                </span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- MAIN CONTENT CARD -->
            <tr>
                <td style="padding:0 20px;" class="email-padding">
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color:#1a1d27;border-radius:16px;border:1px solid #2a2d3a;">
                        <!-- Top accent bar -->
                        <tr>
                            <td style="height:4px;background:linear-gradient(90deg,#6366f1 0%,#8b5cf6 50%,#06b6d4 100%);border-radius:16px 16px 0 0;font-size:0;line-height:0;">&nbsp;</td>
                        </tr>
                        <!-- Content -->
                        <tr>
                            <td style="padding:40px 40px 32px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;font-size:15px;line-height:1.7;color:#cbd5e1;" class="email-padding">
                                {!! $content ?? '' !!}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- FOOTER -->
            <tr>
                <td style="padding:32px 20px 40px;" class="email-padding">
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                        <!-- Divider -->
                        <tr>
                            <td style="padding-bottom:24px;border-top:1px solid #2a2d3a;font-size:0;line-height:0;">&nbsp;</td>
                        </tr>
                        <!-- Footer links -->
                        <tr>
                            <td style="text-align:center;padding-bottom:16px;">
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center">
                                    <tr>
                                        <td style="padding:0 12px;">
                                            <a href="{{ url('/') }}" style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;font-size:13px;color:#6366f1;text-decoration:none;font-weight:500;">Visit Website</a>
                                        </td>
                                        <td style="padding:0 12px;color:#2a2d3a;font-size:13px;">|</td>
                                        <td style="padding:0 12px;">
                                            <a href="{{ url('/contact') }}" style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;font-size:13px;color:#6366f1;text-decoration:none;font-weight:500;">Contact Us</a>
                                        </td>
                                        <td style="padding:0 12px;color:#2a2d3a;font-size:13px;">|</td>
                                        <td style="padding:0 12px;">
                                            <a href="{{ url('/unsubscribe') }}" style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;font-size:13px;color:#94a3b8;text-decoration:none;">Unsubscribe</a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <!-- Copyright -->
                        <tr>
                            <td style="text-align:center;padding-bottom:8px;">
                                <p style="margin:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;font-size:12px;color:#475569;line-height:1.6;">
                                    &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                                </p>
                            </td>
                        </tr>
                        <!-- Legal -->
                        <tr>
                            <td style="text-align:center;">
                                <p style="margin:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;font-size:11px;color:#334155;line-height:1.6;">
                                    You are receiving this email because you have an account with {{ config('app.name') }}.<br>
                                    If you did not request this email, you can safely ignore it.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

        </table>

        <!--[if mso | IE]></td></tr></table><![endif]-->
    </center>

</body>
</html>
