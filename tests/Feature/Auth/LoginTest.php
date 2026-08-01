<?php

namespace Tests\Feature\Auth;

use App\Mail\Auth\MfaOtpMail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_spa_login_succeeds_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@pcspc.local',
            'password' => Hash::make('Password1!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'admin@pcspc.local',
            'password' => 'Password1!',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.mfa_required', false)
            ->assertJsonPath('data.user.id', $user->uuid)
            ->assertJsonMissingPath('data.user.password');

        $this->assertAuthenticatedAs($user);
    }

    public function test_mobile_login_returns_sanctum_token(): void
    {
        User::factory()->create([
            'email' => 'mobile@pcspc.local',
            'password' => Hash::make('Password1!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'mobile@pcspc.local',
            'password' => 'Password1!',
            'device_name' => 'iPhone Test',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonStructure(['data' => ['token', 'user']]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_login_fails_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'admin@pcspc.local',
            'password' => Hash::make('Password1!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'admin@pcspc.local',
            'password' => 'WrongPass1!',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', false);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->inactive()->create([
            'email' => 'disabled@pcspc.local',
            'password' => Hash::make('Password1!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'disabled@pcspc.local',
            'password' => 'Password1!',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('status', false);
    }

    public function test_locked_user_cannot_login(): void
    {
        User::factory()->locked()->create([
            'email' => 'locked@pcspc.local',
            'password' => Hash::make('Password1!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'locked@pcspc.local',
            'password' => 'Password1!',
        ]);

        $response->assertStatus(423)
            ->assertJsonPath('status', false);
    }

    public function test_mfa_challenge_and_verify_flow(): void
    {
        Mail::fake();

        $user = User::factory()->withMfa()->create([
            'email' => 'mfa@pcspc.local',
            'password' => Hash::make('Password1!'),
        ]);

        $challenge = $this->postJson('/api/v1/auth/login', [
            'login' => 'mfa@pcspc.local',
            'password' => 'Password1!',
        ]);

        $challenge->assertOk()
            ->assertJsonPath('data.mfa_required', true)
            ->assertJsonPath('data.email_delivered', true)
            ->assertJsonPath('data.otp_sent_to', 'mf*@pcspc.local')
            ->assertJsonStructure(['data' => ['mfa_token', 'debug_otp']]);

        $otp = $challenge->json('data.debug_otp');
        $mfaToken = $challenge->json('data.mfa_token');

        Mail::assertSent(MfaOtpMail::class, function (MfaOtpMail $mail) use ($user, $otp): bool {
            return $mail->hasTo($user->email) && $mail->otp === $otp;
        });

        $this->assertGuest();

        $verify = $this->postJson('/api/v1/auth/mfa/verify', [
            'mfa_token' => $mfaToken,
            'otp' => $otp,
        ]);

        $verify->assertOk()
            ->assertJsonPath('data.mfa_required', false)
            ->assertJsonPath('data.user.id', $user->uuid);

        $this->assertAuthenticatedAs($user);
    }

    public function test_mfa_resend_sends_a_new_email_code(): void
    {
        Mail::fake();

        User::factory()->withMfa()->create([
            'email' => 'mfa-resend@pcspc.local',
            'password' => Hash::make('Password1!'),
        ]);

        $challenge = $this->postJson('/api/v1/auth/login', [
            'login' => 'mfa-resend@pcspc.local',
            'password' => 'Password1!',
        ])->assertOk();

        $token = $challenge->json('data.mfa_token');
        $firstOtp = $challenge->json('data.debug_otp');

        Mail::assertSent(MfaOtpMail::class, 1);

        $resend = $this->postJson('/api/v1/auth/mfa/resend', [
            'mfa_token' => $token,
        ])->assertOk()
            ->assertJsonPath('data.mfa_required', true)
            ->assertJsonPath('data.email_delivered', true);

        $secondOtp = $resend->json('data.debug_otp');
        $this->assertNotSame($firstOtp, $secondOtp);

        Mail::assertSent(MfaOtpMail::class, 2);

        $this->postJson('/api/v1/auth/mfa/verify', [
            'mfa_token' => $resend->json('data.mfa_token'),
            'otp' => $secondOtp,
        ])->assertOk()
            ->assertJsonPath('data.mfa_required', false);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('status', false);
    }

    public function test_authenticated_user_can_fetch_me(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->uuid);
    }

    public function test_token_logout_revokes_current_access_token(): void
    {
        $user = User::factory()->create();
        $plainTextToken = $user->createToken('iphone')->plainTextToken;

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withToken($plainTextToken)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_role_requires_mfa_triggers_challenge(): void
    {
        $role = Role::query()->create([
            'name' => 'Privileged',
            'slug' => 'privileged',
            'requires_mfa' => true,
        ]);

        $user = User::factory()->create([
            'email' => 'priv@pcspc.local',
            'password' => Hash::make('Password1!'),
            'mfa_enabled' => false,
        ]);
        $user->roles()->attach($role->id);

        Cache::flush();

        $this->postJson('/api/v1/auth/login', [
            'login' => 'priv@pcspc.local',
            'password' => 'Password1!',
        ])->assertOk()
            ->assertJsonPath('data.mfa_required', true);
    }
}
