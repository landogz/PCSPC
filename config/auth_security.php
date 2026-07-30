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
