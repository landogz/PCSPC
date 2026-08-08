{{ $employeeName }} — {{ $kindLabel }}
{{ $workDate }} · {{ $hours }} hour(s)
@if (! empty($stepLabel))
Current step: {{ $stepLabel }}
@endif

Reason: {{ $reason }}
@if (filled($mealNotes))
Meal notes: {{ $mealNotes }}
@endif

Open: {{ $overtimeUrl }}

{{ $companyName }}
