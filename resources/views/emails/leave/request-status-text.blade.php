{{ $companyName ?? $appName }} — leave request {{ $event }}

{{ $employeeName }} · {{ $leaveCode }} {{ $leaveType }}
{{ $startDate }} → {{ $endDate }} ({{ $days }} day(s))

Reason: {{ $reason }}
@if (filled($approverNotes))
Notes: {{ $approverNotes }}
@endif

Open Leave: {{ $leaveUrl }}

— {{ $companyName ?? $appName }}
