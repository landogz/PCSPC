{{ $companyName ?? $appName }} — your account is ready

Hi {{ $userName }},

@if (filled($employeeNumber))
Your employee account ({{ $employeeNumber }}) has been created.
@else
Your employee account has been created.
@endif

Sign in here: {{ $loginUrl }}

Login email: {{ $loginEmail }}
Temporary password: {{ $temporaryPassword }}

@if (! empty($mustChangePassword))
You will be asked to change this temporary password on first sign-in.
@endif

Keep this email private. Do not forward your password.

If you did not expect this account, contact HR or your system administrator.

— {{ $companyName ?? $appName }}
