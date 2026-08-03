<?php

namespace Tests\Feature\Profile;

use App\Models\AuthActivityLog;
use App\Models\User;
use Database\Seeders\AuthSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthSeeder::class);
    }

    public function test_authenticated_user_can_view_and_update_profile(): void
    {
        $user = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $this->actingAs($user)
            ->getJson('/api/v1/auth/profile')
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.user.email', $user->email);

        $this->actingAs($user)
            ->putJson('/api/v1/auth/profile', ['name' => 'Updated Admin'])
            ->assertOk()
            ->assertJsonPath('data.user.name', 'Updated Admin');

        $this->assertDatabaseHas('users', [
            'uuid' => $user->uuid,
            'name' => 'Updated Admin',
        ]);

        $this->assertDatabaseHas('auth_activity_logs', [
            'event' => 'profile.updated',
            'user_id' => $user->id,
        ]);
    }

    public function test_profile_update_requires_name(): void
    {
        $user = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $this->actingAs($user)
            ->putJson('/api/v1/auth/profile', ['name' => ''])
            ->assertStatus(422)
            ->assertJsonPath('status', false);
    }

    public function test_authenticated_user_can_upload_and_remove_avatar(): void
    {
        Storage::fake('public');

        $user = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $photo = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        $upload = $this->actingAs($user)
            ->post('/api/v1/auth/profile/avatar', ['photo' => $photo], [
                'Accept' => 'application/json',
            ])
            ->assertOk()
            ->assertJsonPath('status', true);

        $avatarUrl = $upload->json('data.user.avatar_url');
        $this->assertNotEmpty($avatarUrl);

        $user->refresh();
        $this->assertNotNull($user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);

        $this->assertDatabaseHas('auth_activity_logs', [
            'event' => 'profile.avatar_updated',
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->deleteJson('/api/v1/auth/profile/avatar')
            ->assertOk()
            ->assertJsonPath('data.user.avatar_url', null);

        $user->refresh();
        $this->assertNull($user->avatar_path);

        $this->assertTrue(
            AuthActivityLog::query()->where('event', 'profile.avatar_removed')->where('user_id', $user->id)->exists()
        );
    }

    public function test_guest_cannot_access_profile_endpoints(): void
    {
        $this->getJson('/api/v1/auth/profile')->assertUnauthorized();
        $this->putJson('/api/v1/auth/profile', ['name' => 'Nope'])->assertUnauthorized();
        $this->deleteJson('/api/v1/auth/profile/avatar')->assertUnauthorized();
    }

    public function test_user_can_change_password_from_auth_endpoint(): void
    {
        $user = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $user->forceFill([
            'password' => Hash::make('Password1!'),
            'must_change_password' => false,
        ])->save();

        $this->actingAs($user)
            ->postJson('/api/v1/auth/password', [
                'current_password' => 'Password1!',
                'password' => 'NewPassword2!',
                'password_confirmation' => 'NewPassword2!',
            ])
            ->assertOk()
            ->assertJsonPath('status', true);

        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword2!', $user->password));
    }
}
