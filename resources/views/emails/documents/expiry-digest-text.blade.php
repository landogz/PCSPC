Hi {{ $userName }},

{{ $expiringCount }} document(s) expire within the next {{ $withinDays }} days@if ($expiredCount > 0), and {{ $expiredCount }} already expired@endif.

@foreach ($rows as $row)
- {{ $row['title'] }} ({{ $row['employee'] ?: '—' }}) — expires {{ $row['expires_at'] }}
@endforeach

Review: {{ $documentsUrl }}

— {{ $companyName }}
