<?php

namespace Tests\Feature\Auth;

use App\Models\AuthActivityLog;
use App\Models\User;
use Database\Seeders\AuthSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthSeeder::class);
    }

    public function test_admin_can_update_password_policy(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $this->actingAs($admin)
            ->putJson('/api/v1/administration/password-policy', [
                'min_length' => 10,
                'require_mixed_case' => true,
                'require_numbers' => true,
                'require_symbols' => true,
                'uncompromised' => false,
                'expire_days' => 60,
                'history_count' => 3,
                'force_change_temporary' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.policy.min_length', 10)
            ->assertJsonPath('data.policy.expire_days', 60);

        $this->assertTrue(
            AuthActivityLog::query()->where('event', 'password_policy.updated')->exists()
        );
    }

    public function test_expired_password_requires_change_after_login(): void
    {
        $user = User::factory()->passwordExpired()->create([
            'email' => 'expired@pcspc.local',
            'password' => Hash::make('Password1!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'expired@pcspc.local',
            'password' => 'Password1!',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.password_change_required', true)
            ->assertJsonPath('data.password_expired', true);

        $this->assertTrue($user->fresh()->must_change_password);

        $this->actingAs($user->fresh())
            ->getJson('/api/v1/employees')
            ->assertStatus(403)
            ->assertJsonPath('status', false);
    }

    public function test_user_can_change_password_and_clear_force_flag(): void
    {
        $user = User::factory()->mustChangePassword()->create([
            'email' => 'force@pcspc.local',
            'password' => Hash::make('Password1!'),
        ]);

        $this->actingAs($user)
            ->postJson('/api/v1/auth/password', [
                'current_password' => 'Password1!',
                'password' => 'NewPass2@ok',
                'password_confirmation' => 'NewPass2@ok',
            ])
            ->assertOk()
            ->assertJsonPath('data.password_change_required', false);

        $fresh = $user->fresh();
        $this->assertFalse($fresh->must_change_password);
        $this->assertTrue(Hash::check('NewPass2@ok', $fresh->password));
        $this->assertTrue(
            AuthActivityLog::query()->where('event', 'password.changed')->exists()
        );
    }

    public function test_password_history_blocks_reuse(): void
    {
        $user = User::factory()->create([
            'email' => 'history@pcspc.local',
            'password' => Hash::make('Password1!'),
            'must_change_password' => false,
        ]);

        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $this->actingAs($admin)
            ->putJson('/api/v1/administration/password-policy', [
                'min_length' => 8,
                'require_mixed_case' => true,
                'require_numbers' => true,
                'require_symbols' => true,
                'uncompromised' => false,
                'expire_days' => 90,
                'history_count' => 3,
                'force_change_temporary' => true,
            ])
            ->assertOk();

        $this->actingAs($user)
            ->postJson('/api/v1/auth/password', [
                'current_password' => 'Password1!',
                'password' => 'SecondPass1!',
                'password_confirmation' => 'SecondPass1!',
            ])
            ->assertOk();

        $this->actingAs($user->fresh())
            ->postJson('/api/v1/auth/password', [
                'current_password' => 'SecondPass1!',
                'password' => 'Password1!',
                'password_confirmation' => 'Password1!',
            ])
            ->assertStatus(422)
            ->assertJsonPath('status', false);
    }

    public function test_weak_password_rejected_on_change(): void
    {
        $user = User::factory()->create([
            'email' => 'weak@pcspc.local',
            'password' => Hash::make('Password1!'),
        ]);

        $this->actingAs($user)
            ->postJson('/api/v1/auth/password', [
                'current_password' => 'Password1!',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertStatus(422);
    }
}
