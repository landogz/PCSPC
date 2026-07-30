<?php

namespace App\Http\Middleware;

use App\Services\Administration\PasswordPolicyService;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsCurrent
{
    public function __construct(
        private readonly PasswordPolicyService $passwordPolicy,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $this->passwordPolicy->requiresChange($user)) {
            return $next($request);
        }

        if ($this->isAllowedWhileForced($request)) {
            return $next($request);
        }

        if ($request->is('api/*') || $request->expectsJson()) {
            return ApiResponse::error(
                'Password change required before continuing.',
                [
                    'password' => ['You must change your password before using the system.'],
                    'password_change_required' => true,
                    'password_change_reason' => $this->passwordPolicy->changeReason($user),
                ],
                403,
            );
        }

        return redirect()->route('account.password');
    }

    private function isAllowedWhileForced(Request $request): bool
    {
        if ($request->routeIs('account.password', 'login')) {
            return true;
        }

        $path = trim($request->path(), '/');

        $allowed = [
            'api/v1/auth/me',
            'api/v1/auth/password',
            'api/v1/auth/password/policy',
            'api/v1/auth/logout',
            'account/password',
        ];

        return in_array($path, $allowed, true);
    }
}
