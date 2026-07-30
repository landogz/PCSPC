<?php

namespace App\Repositories;

use App\Enums\AuthEvent;
use App\Models\AuthActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthRepository
{
    public function findByLogin(string $login): ?User
    {
        return User::query()
            ->with('roles.permissions')
            ->where(function ($query) use ($login): void {
                $query->where('email', $login)
                    ->orWhere('employee_number', $login);
            })
            ->first();
    }

    public function findByUuid(string $uuid): ?User
    {
        return User::query()
            ->with('roles.permissions')
            ->where('uuid', $uuid)
            ->first();
    }

    public function passwordMatches(User $user, string $password): bool
    {
        return Hash::check($password, $user->password);
    }

    public function recordFailedAttempt(User $user): User
    {
        $user->failed_login_attempts = (int) $user->failed_login_attempts + 1;

        $maxAttempts = (int) config('auth_security.max_failed_attempts', 5);
        $lockoutMinutes = (int) config('auth_security.lockout_minutes', 15);

        if ($user->failed_login_attempts >= $maxAttempts) {
            $user->locked_until = now()->addMinutes($lockoutMinutes);
            $user->failed_login_attempts = 0;
        }

        $user->save();

        return $user->fresh(['roles.permissions']) ?? $user;
    }

    public function clearLockoutState(User $user): void
    {
        $user->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();
    }

    public function markLoginSuccess(User $user, ?string $ip): void
    {
        $user->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function logActivity(
        AuthEvent $event,
        ?User $user,
        ?string $email,
        ?string $ip,
        ?string $userAgent,
        array $meta = [],
    ): void {
        AuthActivityLog::query()->create([
            'user_id' => $user?->id,
            'email' => $email ?? $user?->email,
            'event' => $event->value,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'meta' => $meta === [] ? null : $meta,
        ]);
    }
}
