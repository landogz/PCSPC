<?php

namespace Tests\Feature\Notifications;

use App\Mail\Employees\EmployeeWelcomeMail;
use App\Models\User;
use App\Models\UserNotification;
use Database\Seeders\AuthSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthSeeder::class);
    }

    public function test_user_can_list_own_notifications_and_unread_count(): void
    {
        $employee = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();

        UserNotification::query()->where('user_id', $employee->id)->delete();

        UserNotification::query()->create([
            'user_id' => $employee->id,
            'type' => 'system.test',
            'title' => 'Test alert',
            'body' => 'Hello from tests',
            'action_url' => '/dashboard',
        ]);

        $this->actingAs($employee)
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.meta.unread_count', 1)
            ->assertJsonPath('data.items.0.title', 'Test alert');

        $this->actingAs($employee)
            ->getJson('/api/v1/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 1);

        $this->actingAs($employee)
            ->getJson('/api/v1/notifications/recent')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 1)
            ->assertJsonCount(1, 'data.items');
    }

    public function test_user_cannot_read_another_users_notification(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employee = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();

        $notification = UserNotification::query()->create([
            'user_id' => $admin->id,
            'type' => 'system.private',
            'title' => 'Admin only',
            'body' => 'Secret',
        ]);

        $this->actingAs($employee)
            ->getJson('/api/v1/notifications/'.$notification->uuid)
            ->assertNotFound();
    }

    public function test_mark_read_and_mark_all_read(): void
    {
        $employee = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();

        UserNotification::query()->where('user_id', $employee->id)->delete();

        $first = UserNotification::query()->create([
            'user_id' => $employee->id,
            'type' => 'system.one',
            'title' => 'One',
        ]);
        UserNotification::query()->create([
            'user_id' => $employee->id,
            'type' => 'system.two',
            'title' => 'Two',
        ]);

        $this->actingAs($employee)
            ->postJson('/api/v1/notifications/'.$first->uuid.'/read')
            ->assertOk()
            ->assertJsonPath('data.notification.is_read', true)
            ->assertJsonPath('data.unread_count', 1);

        $this->actingAs($employee)
            ->postJson('/api/v1/notifications/mark-all-read')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);

        $this->assertSame(0, UserNotification::query()
            ->where('user_id', $employee->id)
            ->whereNull('read_at')
            ->count());
    }

    public function test_employee_create_also_creates_welcome_in_app_notification(): void
    {
        Mail::fake();

        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $this->actingAs($admin)
            ->postJson('/api/v1/employees', [
                'employee_number' => 'EMP-9101',
                'first_name' => 'Notify',
                'last_name' => 'Me',
                'email' => 'notify.me@pcspc.local',
                'employment_status' => 'active',
                'position_title' => 'Analyst',
            ])
            ->assertCreated()
            ->assertJsonPath('data.welcome_email_sent', true);

        Mail::assertSent(EmployeeWelcomeMail::class);

        $user = User::query()->where('email', 'notify.me@pcspc.local')->firstOrFail();

        $this->assertTrue(
            UserNotification::query()
                ->where('user_id', $user->id)
                ->where('type', 'employee.welcome')
                ->exists()
        );

        $meta = UserNotification::query()
            ->where('user_id', $user->id)
            ->where('type', 'employee.welcome')
            ->value('meta');

        $this->assertIsArray($meta);
        $this->assertArrayNotHasKey('temporary_password', $meta);
        $this->assertArrayNotHasKey('password', $meta);
    }

    public function test_guest_cannot_access_notifications_api(): void
    {
        $this->getJson('/api/v1/notifications')->assertUnauthorized();
    }
}
