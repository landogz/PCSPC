@php
    $headline = match ($event ?? '') {
        'submitted' => 'A leave request was submitted',
        'step' => 'Leave awaiting next approval',
        'approved' => 'Your leave request was approved',
        'rejected' => 'Your leave request was rejected',
        'cancelled' => 'A leave request was cancelled',
        default => 'Leave request update',
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $headline }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f8;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e5e7eb;">
                    <tr>
                        <td style="padding:24px 28px;border-bottom:1px solid #e5e7eb;">
                            @if (! empty($logoUrl))
                                <img src="{{ $logoUrl }}" alt="{{ $appName }}" height="36" style="display:block;height:36px;width:auto;">
                            @else
                                <strong style="font-size:18px;">{{ $appName }}</strong>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <h1 style="margin:0 0 12px;font-size:20px;line-height:1.3;">{{ $headline }}</h1>
                            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#4b5563;">
                                <strong>{{ $employeeName }}</strong> · {{ $leaveCode }} {{ $leaveType }}<br>
                                {{ $startDate }} → {{ $endDate }} ({{ $days }} day(s))
                            </p>
                            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#4b5563;">
                                <strong>Reason:</strong> {{ $reason }}
                            </p>
                            @if (filled($approverNotes))
                                <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#4b5563;">
                                    <strong>Notes:</strong> {{ $approverNotes }}
                                </p>
                            @endif
                            <p style="margin:0;">
                                <a href="{{ $leaveUrl }}" style="display:inline-block;background:#c8102e;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:10px;font-size:14px;font-weight:600;">Open Leave module</a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px;background:#f9fafb;font-size:12px;color:#6b7280;">
                            {{ $companyName }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
