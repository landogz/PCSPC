<?php

namespace App\Services;

use App\Enums\AuthEvent;
use App\Models\User;
use App\Repositories\AuthRepository;
use App\Services\Administration\PasswordPolicyService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthService
{
    public function __construct(
        private readonly AuthRepository $authRepository,
        private readonly PasswordPolicyService $passwordPolicy,
    ) {}

    /**
     * @return array{status: string, message: string, data: array<string, mixed>, http: int}
     */
    public function login(
        string $login,
        string $password,
        ?string $deviceName,
        ?string $ip,
        ?string $userAgent,
    ): array {
        $user = $this->authRepository->findByLogin($login);

        if ($user === null) {
            $this->authRepository->logActivity(
                AuthEvent::LoginFailed,
                null,
                $login,
                $ip,
                $userAgent,
                ['reason' => 'user_not_found'],
            );

            throw ValidationException::withMessages([
                'login' => ['These credentials do not match our records.'],
            ]);
        }

        if (! $user->is_active) {
            $this->authRepository->logActivity(
                AuthEvent::AccountInactive,
                $user,
                $user->email,
                $ip,
                $userAgent,
            );

            throw new HttpException(403, 'This account has been disabled. Contact HR/IT.');
        }

        if ($user->isLocked()) {
            $this->authRepository->logActivity(
                AuthEvent::AccountLocked,
                $user,
                $user->email,
                $ip,
                $userAgent,
            );

            throw new HttpException(
                423,
                'Account is temporarily locked due to failed login attempts. Try again later.',
            );
        }

        if (! $this->authRepository->passwordMatches($user, $password)) {
            $user = $this->authRepository->recordFailedAttempt($user);

            $this->authRepository->logActivity(
                AuthEvent::LoginFailed,
                $user,
                $user->email,
                $ip,
                $userAgent,
                ['reason' => 'invalid_password'],
            );

            if ($user->isLocked()) {
                $this->authRepository->logActivity(
                    AuthEvent::AccountLocked,
                    $user,
                    $user->email,
                    $ip,
                    $userAgent,
                );

                throw new HttpException(
                    423,
                    'Account is temporarily locked due to failed login attempts. Try again later.',
                );
            }

            throw ValidationException::withMessages([
                'login' => ['These credentials do not match our records.'],
            ]);
        }

        if ($user->requiresMfa()) {
            return $this->startMfaChallenge($user, $deviceName, $ip, $userAgent);
        }

        return $this->completeLogin($user, $deviceName, $ip, $userAgent);
    }

    /**
     * @return array{status: string, message: string, data: array<string, mixed>, http: int}
     */
    public function verifyMfa(
        string $mfaToken,
        string $otp,
        ?string $ip,
        ?string $userAgent,
    ): array {
        try {
            /** @var array{user_uuid: string, device_name: ?string, expires_at: int} $payload */
            $payload = Crypt::decrypt($mfaToken);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'mfa_token' => ['Invalid or expired MFA challenge.'],
            ]);
        }

        if (($payload['expires_at'] ?? 0) < now()->timestamp) {
            throw ValidationException::withMessages([
                'mfa_token' => ['MFA challenge has expired. Please sign in again.'],
            ]);
        }

        $user = $this->authRepository->findByUuid($payload['user_uuid']);

        if ($user === null || ! $user->is_active) {
            throw new HttpException(403, 'Unable to complete MFA for this account.');
        }

        $cacheKey = $this->mfaCacheKey($user->uuid);
        $hashedOtp = Cache::get($cacheKey);

        if (! is_string($hashedOtp) || ! Hash::check($otp, $hashedOtp)) {
            $this->authRepository->logActivity(
                AuthEvent::MfaFailed,
                $user,
                $user->email,
                $ip,
                $userAgent,
            );

            throw ValidationException::withMessages([
                'otp' => ['Invalid authentication code.'],
            ]);
        }

        Cache::forget($cacheKey);

        $this->authRepository->logActivity(
            AuthEvent::MfaSuccess,
            $user,
            $user->email,
            $ip,
            $userAgent,
        );

        return $this->completeLogin(
            $user,
            $payload['device_name'] ?? null,
            $ip,
            $userAgent,
        );
    }

    public function logout(?User $user, ?string $ip, ?string $userAgent, bool $revokeCurrentToken = true): void
    {
        if ($user === null) {
            return;
        }

        if ($revokeCurrentToken) {
            $token = $user->currentAccessToken();

            if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
                $token->delete();
            }
        }

        Auth::guard('web')->logout();

        $this->authRepository->logActivity(
            AuthEvent::Logout,
            $user,
            $user->email,
            $ip,
            $userAgent,
        );
    }

    public function logoutOtherDevices(User $user, ?string $ip, ?string $userAgent): int
    {
        $currentToken = $user->currentAccessToken();
        $currentTokenId = $currentToken instanceof \Laravel\Sanctum\PersonalAccessToken
            ? $currentToken->id
            : null;

        $query = $user->tokens();

        if ($currentTokenId !== null) {
            $query->where('id', '!=', $currentTokenId);
        }

        $revoked = (int) $query->delete();

        $this->authRepository->logActivity(
            AuthEvent::LogoutOthers,
            $user,
            $user->email,
            $ip,
            $userAgent,
            ['revoked_tokens' => $revoked],
        );

        return $revoked;
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): User
    {
        return $this->passwordPolicy->changeOwnPassword($user, $currentPassword, $newPassword);
    }

    /**
     * @return array{status: string, message: string, data: array<string, mixed>, http: int}
     */
    private function startMfaChallenge(
        User $user,
        ?string $deviceName,
        ?string $ip,
        ?string $userAgent,
    ): array {
        $ttl = (int) config('auth_security.mfa_challenge_ttl_minutes', 10);
        $length = (int) config('auth_security.mfa_otp_length', 6);
        $max = (10 ** $length) - 1;
        $otp = str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);

        Cache::put($this->mfaCacheKey($user->uuid), Hash::make($otp), now()->addMinutes($ttl));

        // Local/dev visibility; production should deliver via secure notification channel only.
        if (app()->environment(['local', 'testing'])) {
            Log::info('MFA OTP generated', [
                'user_uuid' => $user->uuid,
                'otp' => $otp,
            ]);
        }

        $mfaToken = Crypt::encrypt([
            'user_uuid' => $user->uuid,
            'device_name' => $deviceName,
            'expires_at' => now()->addMinutes($ttl)->timestamp,
        ]);

        $this->authRepository->logActivity(
            AuthEvent::MfaChallenged,
            $user,
            $user->email,
            $ip,
            $userAgent,
        );

        return [
            'status' => 'mfa_required',
            'message' => 'Multi-factor authentication required.',
            'http' => 200,
            'data' => [
                'mfa_required' => true,
                'mfa_token' => $mfaToken,
                'expires_in' => $ttl * 60,
                // Exposed only outside production so SPA/tests can verify without mail.
                'debug_otp' => app()->environment(['local', 'testing']) ? $otp : null,
            ],
        ];
    }

    /**
     * @return array{status: string, message: string, data: array<string, mixed>, http: int}
     */
    private function completeLogin(
        User $user,
        ?string $deviceName,
        ?string $ip,
        ?string $userAgent,
    ): array {
        $this->authRepository->clearLockoutState($user);
        $this->authRepository->markLoginSuccess($user, $ip);

        $token = null;

        if (filled($deviceName)) {
            $token = $user->createToken($deviceName)->plainTextToken;
        } else {
            Auth::guard('web')->login($user, true);

            if (! request()->hasSession()) {
                throw new HttpException(500, 'Unable to start login session. Please refresh and try again.');
            }

            request()->session()->regenerate();
        }

        $user->load(['roles.permissions', 'employee']);

        $passwordStatus = $this->passwordPolicy->statusFor($user);

        if ($passwordStatus['password_expired'] && ! $user->must_change_password) {
            $user->forceFill(['must_change_password' => true])->save();
            $user = $user->fresh(['roles.permissions']) ?? $user;
            $passwordStatus = $this->passwordPolicy->statusFor($user);
        }

        $this->authRepository->logActivity(
            AuthEvent::LoginSuccess,
            $user,
            $user->email,
            $ip,
            $userAgent,
            [
                'client' => filled($deviceName) ? 'token' : 'spa',
                'password_change_required' => $passwordStatus['password_change_required'],
                'password_change_reason' => $passwordStatus['password_change_reason'],
            ],
        );

        return [
            'status' => 'authenticated',
            'message' => $passwordStatus['password_change_required']
                ? 'Login successful. Password change required.'
                : 'Login successful.',
            'http' => 200,
            'data' => array_merge([
                'mfa_required' => false,
                'token' => $token,
                'user' => array_merge([
                    'id' => $user->uuid,
                    'name' => $user->name,
                    'email' => $user->email,
                    'employee_number' => $user->employee_number,
                    'avatar_url' => $user->avatarUrl(),
                    'is_active' => $user->is_active,
                    'mfa_enabled' => $user->mfa_enabled,
                    'roles' => $user->roleSlugs(),
                    'permissions' => $user->permissionSlugs(),
                    'last_login_at' => $user->last_login_at?->toIso8601String(),
                ], $passwordStatus),
            ], $passwordStatus),
        ];
    }

    private function mfaCacheKey(string $userUuid): string
    {
        return "auth:mfa_otp:{$userUuid}";
    }
}
