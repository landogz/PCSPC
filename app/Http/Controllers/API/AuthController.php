<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResendMfaRequest;
use App\Http\Requests\Auth\VerifyMfaRequest;
use App\Http\Resources\UserResource;
use App\Services\Administration\PasswordPolicyService;
use App\Services\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Inject authentication and password policy services.
     */
    public function __construct(
        private readonly AuthService $authService,
        private readonly PasswordPolicyService $passwordPolicy,
    ) {}

    /**
     * Authenticate credentials and start login or MFA challenge flow.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            login: (string) $request->validated('login'),
            password: (string) $request->validated('password'),
            deviceName: $request->validated('device_name'),
            ip: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return ApiResponse::success($result['message'], $result['data'], $result['http']);
    }

    /**
     * Verify an MFA one-time code and complete sign-in.
     */
    public function verifyMfa(VerifyMfaRequest $request): JsonResponse
    {
        $result = $this->authService->verifyMfa(
            mfaToken: (string) $request->validated('mfa_token'),
            otp: (string) $request->validated('otp'),
            ip: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return ApiResponse::success($result['message'], $result['data'], $result['http']);
    }

    /**
     * Resend the MFA one-time code for a pending login session.
     */
    public function resendMfa(ResendMfaRequest $request): JsonResponse
    {
        $result = $this->authService->resendMfa(
            mfaToken: (string) $request->validated('mfa_token'),
            ip: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return ApiResponse::success($result['message'], $result['data'], $result['http']);
    }

    /**
     * Return the authenticated user profile and password policy status.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()?->loadMissing(['roles.permissions', 'employee']);

        if ($user === null) {
            return ApiResponse::error('Unauthenticated.', [], 401);
        }

        return ApiResponse::success('Authenticated user retrieved.', [
            'user' => (new UserResource($user))->resolve(),
            ...$this->passwordPolicy->statusFor($user),
        ]);
    }

    /**
     * Return the active password complexity and expiry rules.
     */
    public function passwordPolicy(): JsonResponse
    {
        $policy = $this->passwordPolicy->current();

        return ApiResponse::success('Password policy retrieved.', [
            'policy' => [
                'min_length' => $policy['min_length'],
                'require_mixed_case' => $policy['require_mixed_case'],
                'require_numbers' => $policy['require_numbers'],
                'require_symbols' => $policy['require_symbols'],
                'history_count' => $policy['history_count'],
                'expire_days' => $policy['expire_days'],
                'hint' => $policy['hint'],
            ],
        ]);
    }

    /**
     * Change the authenticated user's password after validating the current one.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return ApiResponse::error('Unauthenticated.', [], 401);
        }

        $updated = $this->authService->changePassword(
            user: $user,
            currentPassword: (string) $request->validated('current_password'),
            newPassword: (string) $request->validated('password'),
        );

        return ApiResponse::success('Password updated successfully.', [
            'user' => (new UserResource($updated->loadMissing('roles.permissions')))->resolve(),
            ...$this->passwordPolicy->statusFor($updated),
        ]);
    }

    /**
     * Revoke the current token and invalidate the web session when present.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->authService->logout(
            user: $user,
            ip: $request->ip(),
            userAgent: $request->userAgent(),
            revokeCurrentToken: true,
        );

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return ApiResponse::success('Logged out successfully.');
    }

    /**
     * Revoke all other active sessions and tokens for the authenticated user.
     */
    public function logoutOthers(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return ApiResponse::error('Unauthenticated.', [], 401);
        }

        $revoked = $this->authService->logoutOtherDevices(
            user: $user,
            ip: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return ApiResponse::success('Logged out from other devices.', [
            'revoked_tokens' => $revoked,
        ]);
    }
}
