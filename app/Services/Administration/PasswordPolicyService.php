<?php

namespace App\Services\Administration;

use App\Models\PasswordHistory;
use App\Models\User;
use App\Repositories\Administration\SystemSettingRepository;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class PasswordPolicyService
{
    public const SETTINGS_KEY = 'password_policy';

    public function __construct(
        private readonly SystemSettingRepository $settings,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return array{
     *     min_length: int,
     *     require_mixed_case: bool,
     *     require_numbers: bool,
     *     require_symbols: bool,
     *     uncompromised: bool,
     *     expire_days: int,
     *     history_count: int,
     *     force_change_temporary: bool,
     *     hint: string
     * }
     */
    public function current(): array
    {
        $defaults = config('auth_security.password', []);
        $stored = $this->settings->get(self::SETTINGS_KEY);

        $policy = array_merge(
            [
                'min_length' => 8,
                'require_mixed_case' => true,
                'require_numbers' => true,
                'require_symbols' => true,
                'uncompromised' => false,
                'expire_days' => 90,
                'history_count' => 5,
                'force_change_temporary' => true,
            ],
            is_array($defaults) ? $defaults : [],
            is_array($stored) ? $stored : [],
        );

        $policy['min_length'] = max(6, min(64, (int) $policy['min_length']));
        $policy['expire_days'] = max(0, min(730, (int) $policy['expire_days']));
        $policy['history_count'] = max(0, min(24, (int) $policy['history_count']));
        $policy['require_mixed_case'] = (bool) $policy['require_mixed_case'];
        $policy['require_numbers'] = (bool) $policy['require_numbers'];
        $policy['require_symbols'] = (bool) $policy['require_symbols'];
        $policy['uncompromised'] = (bool) $policy['uncompromised'];
        $policy['force_change_temporary'] = (bool) $policy['force_change_temporary'];
        $policy['hint'] = $this->buildHint($policy);

        return $policy;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function update(array $payload): array
    {
        $current = $this->current();
        unset($current['hint']);

        $next = [
            'min_length' => (int) ($payload['min_length'] ?? $current['min_length']),
            'require_mixed_case' => (bool) ($payload['require_mixed_case'] ?? $current['require_mixed_case']),
            'require_numbers' => (bool) ($payload['require_numbers'] ?? $current['require_numbers']),
            'require_symbols' => (bool) ($payload['require_symbols'] ?? $current['require_symbols']),
            'uncompromised' => (bool) ($payload['uncompromised'] ?? $current['uncompromised']),
            'expire_days' => (int) ($payload['expire_days'] ?? $current['expire_days']),
            'history_count' => (int) ($payload['history_count'] ?? $current['history_count']),
            'force_change_temporary' => (bool) ($payload['force_change_temporary'] ?? $current['force_change_temporary']),
        ];

        $this->settings->put(self::SETTINGS_KEY, $next);

        $policy = $this->current();

        $this->audit->log('password_policy.updated', [
            'min_length' => $policy['min_length'],
            'expire_days' => $policy['expire_days'],
            'history_count' => $policy['history_count'],
            'require_mixed_case' => $policy['require_mixed_case'],
            'require_numbers' => $policy['require_numbers'],
            'require_symbols' => $policy['require_symbols'],
            'uncompromised' => $policy['uncompromised'],
            'force_change_temporary' => $policy['force_change_temporary'],
        ]);

        return $policy;
    }

    public function validationRule(): Password
    {
        $policy = $this->current();
        $rule = Password::min($policy['min_length']);

        if ($policy['require_mixed_case']) {
            $rule = $rule->mixedCase();
        }

        if ($policy['require_numbers']) {
            $rule = $rule->numbers();
        }

        if ($policy['require_symbols']) {
            $rule = $rule->symbols();
        }

        if ($policy['uncompromised']) {
            $rule = $rule->uncompromised();
        }

        return $rule;
    }

    public function isExpired(User $user): bool
    {
        $expireDays = $this->current()['expire_days'];
        if ($expireDays <= 0) {
            return false;
        }

        $changedAt = $user->password_changed_at;

        if ($changedAt === null) {
            return true;
        }

        return $changedAt->copy()->addDays($expireDays)->isPast();
    }

    public function requiresChange(User $user): bool
    {
        return (bool) $user->must_change_password || $this->isExpired($user);
    }

    public function changeReason(User $user): ?string
    {
        if ((bool) $user->must_change_password) {
            return 'temporary_or_admin_reset';
        }

        if ($this->isExpired($user)) {
            return 'expired';
        }

        return null;
    }

    /**
     * @return array{must_change_password: bool, password_expired: bool, password_change_required: bool, password_change_reason: string|null, password_policy_hint: string}
     */
    public function statusFor(User $user): array
    {
        $expired = $this->isExpired($user);
        $mustChange = (bool) $user->must_change_password;
        $required = $mustChange || $expired;

        return [
            'must_change_password' => $mustChange,
            'password_expired' => $expired,
            'password_change_required' => $required,
            'password_change_reason' => $this->changeReason($user),
            'password_policy_hint' => $this->current()['hint'],
        ];
    }

    public function assertNotReused(User $user, string $plainPassword): void
    {
        $historyCount = $this->current()['history_count'];

        if (Hash::check($plainPassword, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['New password must be different from your current password.'],
            ]);
        }

        if ($historyCount <= 0) {
            return;
        }

        $histories = PasswordHistory::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit($historyCount)
            ->get(['password']);

        foreach ($histories as $history) {
            if (Hash::check($plainPassword, $history->password)) {
                throw ValidationException::withMessages([
                    'password' => ["New password must not match your last {$historyCount} passwords."],
                ]);
            }
        }
    }

    public function rememberCurrentPassword(User $user): void
    {
        $historyCount = $this->current()['history_count'];
        if ($historyCount <= 0 || blank($user->password)) {
            return;
        }

        PasswordHistory::query()->create([
            'user_id' => $user->id,
            'password' => $user->password,
        ]);

        $keepIds = PasswordHistory::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit($historyCount)
            ->pluck('id');

        PasswordHistory::query()
            ->where('user_id', $user->id)
            ->whereNotIn('id', $keepIds)
            ->delete();
    }

    public function applyNewPassword(User $user, string $plainPassword, bool $forceChangeNextLogin = false): User
    {
        $this->assertNotReused($user, $plainPassword);
        $this->rememberCurrentPassword($user);

        $user->forceFill([
            'password' => $plainPassword,
            'password_changed_at' => now(),
            'must_change_password' => $forceChangeNextLogin,
        ])->save();

        return $user->fresh() ?? $user;
    }

    public function changeOwnPassword(User $user, string $currentPassword, string $newPassword): User
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        $updated = $this->applyNewPassword($user, $newPassword, false);

        $this->audit->log('password.changed', [
            'user_id' => $updated->uuid,
            'reason' => 'self_service',
        ], $updated);

        return $updated;
    }

    /**
     * @param  array<string, mixed>  $policy
     */
    private function buildHint(array $policy): string
    {
        $parts = [sprintf('at least %d characters', (int) $policy['min_length'])];

        if ($policy['require_mixed_case']) {
            $parts[] = 'upper and lower case';
        }
        if ($policy['require_numbers']) {
            $parts[] = 'a number';
        }
        if ($policy['require_symbols']) {
            $parts[] = 'a symbol';
        }
        if ((int) $policy['history_count'] > 0) {
            $parts[] = sprintf('not one of your last %d passwords', (int) $policy['history_count']);
        }

        return 'Password must include '.implode(', ', $parts).'.';
    }
}
