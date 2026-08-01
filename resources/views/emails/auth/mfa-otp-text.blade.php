{{ $companyName ?? $appName }} sign-in code

Hi {{ $userName }},

Your one-time sign-in code is: {{ $otp }}

This code expires in {{ $expiresInMinutes }} minutes.
Never share this code with anyone.

If you did not try to sign in, you can ignore this email.

— {{ $companyName ?? $appName }} Security
