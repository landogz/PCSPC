<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Login lockout (ADM-005)
    |--------------------------------------------------------------------------
    */
    'max_failed_attempts' => (int) env('AUTH_MAX_FAILED_ATTEMPTS', 5),
    'lockout_minutes' => (int) env('AUTH_LOCKOUT_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | Password policy (ADM-005)
    | DB overrides (Administration) win over these defaults when present.
    |--------------------------------------------------------------------------
    */
    'password' => [
        'min_length' => (int) env('AUTH_PASSWORD_MIN_LENGTH', 8),
        'require_mixed_case' => filter_var(env('AUTH_PASSWORD_MIXED_CASE', true), FILTER_VALIDATE_BOOL),
        'require_numbers' => filter_var(env('AUTH_PASSWORD_NUMBERS', true), FILTER_VALIDATE_BOOL),
        'require_symbols' => filter_var(env('AUTH_PASSWORD_SYMBOLS', true), FILTER_VALIDATE_BOOL),
        'uncompromised' => filter_var(env('AUTH_PASSWORD_UNCOMPROMISED', false), FILTER_VALIDATE_BOOL),
        'expire_days' => (int) env('AUTH_PASSWORD_EXPIRE_DAYS', 90),
        'history_count' => (int) env('AUTH_PASSWORD_HISTORY_COUNT', 5),
        'force_change_temporary' => filter_var(env('AUTH_PASSWORD_FORCE_TEMP', true), FILTER_VALIDATE_BOOL),
    ],

    /*
    |--------------------------------------------------------------------------
    | MFA challenge
    |--------------------------------------------------------------------------
    */
    'mfa_challenge_ttl_minutes' => (int) env('AUTH_MFA_CHALLENGE_TTL', 10),
    'mfa_otp_length' => 6,

    /*
    |--------------------------------------------------------------------------
    | Login throttle (max attempts, decay minutes)
    | Use AUTH_LOGIN_THROTTLE in .env — local default is raised for auto-login.
    |--------------------------------------------------------------------------
    */
    'login_throttle' => env('AUTH_LOGIN_THROTTLE', '5,1'),
];
