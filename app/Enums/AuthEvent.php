<?php

namespace App\Enums;

enum AuthEvent: string
{
    case LoginSuccess = 'login_success';
    case LoginFailed = 'login_failed';
    case AccountLocked = 'account_locked';
    case AccountInactive = 'account_inactive';
    case MfaChallenged = 'mfa_challenged';
    case MfaFailed = 'mfa_failed';
    case MfaSuccess = 'mfa_success';
    case Logout = 'logout';
    case LogoutOthers = 'logout_others';
}
