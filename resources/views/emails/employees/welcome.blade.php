@php
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
    :title="'Your '.$appName.' account is ready'"
    :preheader="'Sign in to '.$appName.' with your email and temporary password.'"
>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
            <td style="padding-bottom:6px;">
                <span style="display:inline-block;padding:6px 12px;border-radius:999px;background-color:#fde8e9;color:#d31219;font-size:11px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;">
                    Account invitation
                </span>
            </td>
        </tr>
        <tr>
            <td style="padding:10px 0 8px;font-size:26px;line-height:1.25;font-weight:700;color:#0e1218;">
                Welcome to {{ $appName }}
            </td>
        </tr>
        <tr>
            <td style="padding:0 0 22px;font-size:15px;line-height:1.65;color:#404a60;">
                Hi {{ $userName }}, your employee account
                @if (filled($employeeNumber))
                    (<strong style="color:#0e1218;">{{ $employeeNumber }}</strong>)
                @endif
                has been created. Use the credentials below to sign in to the dashboard.
            </td>
        </tr>

        <tr>
            <td style="padding:0 0 18px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#fff7f7;border:1px solid #f5c2c4;border-radius:14px;">
                    <tr>
                        <td style="padding:16px 18px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td style="padding:0 0 10px;font-size:12px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:#94a0b0;">
                                        Login email
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 0 16px;font-size:16px;font-weight:700;color:#0e1218;word-break:break-all;">
                                        {{ $loginEmail }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 0 10px;font-size:12px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:#94a0b0;">
                                        Temporary password
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:18px;font-weight:700;letter-spacing:0.04em;color:#0e1218;word-break:break-all;">
                                        {{ $temporaryPassword }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td align="center" style="padding:0 0 22px;">
                <a
                    href="{{ $loginUrl }}"
                    style="display:inline-block;padding:14px 28px;border-radius:12px;background-color:#d31219;color:#ffffff;font-size:15px;font-weight:700;text-decoration:none;"
                >
                    Sign in to dashboard
                </a>
            </td>
        </tr>

        <tr>
            <td style="padding:0 0 18px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f7f8fa;border:1px solid #eef0f3;border-radius:14px;">
                    <tr>
                        <td style="padding:14px 16px;font-size:13px;line-height:1.6;color:#404a60;">
                            <strong style="color:#0e1218;">Security tips</strong><br>
                            @if (! empty($mustChangePassword))
                                You will be asked to change this temporary password on first sign-in.
                                <br>
                            @endif
                            Keep this email private. Do not forward your password.
                            After signing in, you can also change your password from your profile menu.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td style="padding:0;font-size:13px;line-height:1.6;color:#94a0b0;">
                If you did not expect this account, contact HR or your system administrator.
                Login page:
                <a href="{{ $loginUrl }}" style="color:#d31219;text-decoration:none;">{{ $loginUrl }}</a>
            </td>
        </tr>
    </table>
</x-emails.layouts.brand>
