<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\VerifyMfaRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

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

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()?->loadMissing('roles.permissions');

        if ($user === null) {
            return ApiResponse::error('Unauthenticated.', [], 401);
        }

        return ApiResponse::success('Authenticated user retrieved.', [
            'user' => (new UserResource($user))->resolve(),
        ]);
    }

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
