@php
    $digits = str_split((string) $otp);
    $resolvedLogoUrl = $logoUrl ?? null;
    if (isset($message) && filled($logoPath ?? null) && is_string($logoPath) && is_file($logoPath)) {
        $resolvedLogoUrl = $message->embed($logoPath);
    }
@endphp

<x-emails.layouts.brand
    :app-name="$appName"
    :company-name="$companyName"
    :logo-url="$resolvedLogoUrl"
    :app-url="$appUrl"
    :title="$appName.' sign-in code'"
    :preheader="'Your '.$appName.' sign-in code expires in '.$expiresInMinutes.' minutes.'"
>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
            <td style="padding-bottom:6px;">
                <span style="display:inline-block;padding:6px 12px;border-radius:999px;background-color:#fde8e9;color:#d31219;font-size:11px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;">
                    Security verification
                </span>
            </td>
        </tr>
        <tr>
            <td style="padding:10px 0 8px;font-size:26px;line-height:1.25;font-weight:700;color:#0e1218;">
                Your sign-in code
            </td>
        </tr>
        <tr>
            <td style="padding:0 0 22px;font-size:15px;line-height:1.65;color:#404a60;">
                Hi {{ $userName }}, use this one-time code to finish signing in to
                <strong style="color:#0e1218;">{{ $appName }}</strong>.
            </td>
        </tr>

        <tr>
            <td align="center" style="padding:0 0 22px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:0 auto;background-color:#fff7f7;border:1px solid #f5c2c4;border-radius:16px;">
                    <tr>
                        <td style="padding:18px 20px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                                <tr>
                                    @foreach ($digits as $digit)
                                        <td align="center" valign="middle" style="padding:0 {{ $loop->last ? '0' : '4px' }} 0 0;">
                                            <div style="width:42px;height:50px;line-height:50px;text-align:center;border-radius:10px;background-color:#ffffff;border:1px solid #e2e5ea;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:24px;font-weight:700;color:#0e1218;">
                                                {{ $digit }}
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td style="padding:0 0 18px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f7f8fa;border:1px solid #eef0f3;border-radius:14px;">
                    <tr>
                        <td style="padding:14px 16px;font-size:13px;line-height:1.6;color:#404a60;">
                            <strong style="color:#0e1218;">Expires in {{ $expiresInMinutes }} minutes.</strong><br>
                            Never share this code. {{ $appName }} will never ask you for it by phone or chat.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td style="padding:0;font-size:13px;line-height:1.6;color:#94a0b0;">
                If you did not try to sign in, you can safely ignore this email. Your password was not shared.
            </td>
        </tr>
    </table>
</x-emails.layouts.brand>
