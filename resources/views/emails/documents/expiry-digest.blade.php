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
    :title="$appName.' document expiry digest'"
    :preheader="$expiringCount.' document(s) expire within '.$withinDays.' days.'"
>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
            <td style="padding-bottom:6px;">
                <span style="display:inline-block;padding:6px 12px;border-radius:999px;background-color:#fff4e5;color:#9a6700;font-size:11px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;">
                    Expiry tracking
                </span>
            </td>
        </tr>
        <tr>
            <td style="padding:10px 0 8px;font-size:26px;line-height:1.25;font-weight:700;color:#0e1218;">
                Documents need attention
            </td>
        </tr>
        <tr>
            <td style="padding:0 0 18px;font-size:15px;line-height:1.65;color:#404a60;">
                Hi {{ $userName }}, <strong style="color:#0e1218;">{{ $expiringCount }}</strong> document(s)
                expire within the next {{ $withinDays }} days
                @if ($expiredCount > 0)
                    and <strong style="color:#d31219;">{{ $expiredCount }}</strong> already expired
                @endif
                .
            </td>
        </tr>

        @foreach ($rows as $row)
            <tr>
                <td style="padding:0 0 10px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e6ebf2;border-radius:12px;background:#ffffff;">
                        <tr>
                            <td style="padding:12px 14px;">
                                <div style="font-size:15px;font-weight:700;color:#0e1218;">{{ $row['title'] }}</div>
                                <div style="font-size:13px;color:#667085;margin-top:4px;">
                                    {{ $row['employee'] ?: '—' }} · expires {{ $row['expires_at'] }}
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        @endforeach

        <tr>
            <td style="padding:12px 0 0;">
                <a href="{{ $documentsUrl }}" style="display:inline-block;padding:12px 18px;border-radius:12px;background-color:#d31219;color:#ffffff;font-size:14px;font-weight:700;text-decoration:none;">
                    Review expiring documents
                </a>
            </td>
        </tr>
    </table>
</x-emails.layouts.brand>
