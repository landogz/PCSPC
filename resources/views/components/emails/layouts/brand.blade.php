@props([
    'appName' => null,
    'companyName' => null,
    'logoUrl' => null,
    'appUrl' => null,
    'title' => null,
    'preheader' => '',
])

@php
    $appName = $appName ?: config('app.name');
    $companyName = $companyName ?: $appName;
    $title = $title ?: $appName;
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $title }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
</head>
<body style="margin:0;padding:0;background-color:#f3f5f8;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#28303f;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;mso-hide:all;">
        {{ $preheader }}
    </div>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f3f5f8;margin:0;padding:0;width:100%;">
        <tr>
            <td align="center" style="padding:28px 16px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:560px;width:100%;">
                    <tr>
                        <td align="center" style="padding:0 0 18px;">
                            @if (! empty($logoUrl))
                                <img
                                    src="{{ $logoUrl }}"
                                    alt="{{ $companyName }}"
                                    width="180"
                                    style="display:block;border:0;outline:none;text-decoration:none;height:auto;max-width:180px;width:180px;"
                                >
                            @else
                                <div style="font-size:20px;font-weight:700;letter-spacing:0.04em;color:#d31219;">
                                    {{ $appName }}
                                </div>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color:#ffffff;border:1px solid #e2e5ea;border-radius:18px;overflow:hidden;box-shadow:0 8px 24px rgba(14,18,24,0.06);">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td style="height:5px;line-height:5px;font-size:0;background-color:#d31219;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td style="padding:28px 28px 8px;">
                                        {{ $slot }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 28px 28px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-top:1px solid #eef0f3;">
                                            <tr>
                                                <td style="padding-top:18px;font-size:12px;line-height:1.55;color:#94a0b0;">
                                                    This message was sent by {{ $companyName }}. Do not share one-time codes with anyone.
                                                    @if (! empty($appUrl))
                                                        <br>
                                                        <a href="{{ $appUrl }}" style="color:#d31219;text-decoration:none;">{{ parse_url($appUrl, PHP_URL_HOST) ?: $appUrl }}</a>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:18px 8px 0;font-size:11px;line-height:1.5;color:#94a0b0;">
                            © {{ date('Y') }} {{ $companyName }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
