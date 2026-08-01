<?php

namespace Tests\Feature\Security;

use App\Models\AuthActivityLog;
use App\Models\Department;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AuthSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthSeeder::class);
    }

    public function test_admin_can_list_users(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $this->actingAs($admin)
            ->getJson('/api/v1/security/users')
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonStructure(['data' => ['items', 'meta']]);
    }

    public function test_employee_cannot_list_users(): void
    {
        $employee = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();

        $this->actingAs($employee)
            ->getJson('/api/v1/security/users')
            ->assertStatus(403)
            ->assertJsonPath('status', false);
    }

    public function test_admin_can_create_user_from_employee(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $role = Role::query()->where('slug', 'employee')->firstOrFail();

        $employee = \App\Models\Employee::query()->create([
            'employee_number' => 'EMP-3001',
            'first_name' => 'New',
            'last_name' => 'Staff',
            'email' => 'newstaff@pcspc.local',
            'employment_status' => 'active',
        ]);

        $this->actingAs($admin)
            ->postJson('/api/v1/security/users', [
                'employee_id' => $employee->uuid,
                'name' => 'New Staff',
                'email' => 'newstaff@pcspc.local',
                'password' => 'Password1!',
                'role_ids' => [$role->uuid],
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.user.email', 'newstaff@pcspc.local')
            ->assertJsonPath('data.user.employee_number', 'EMP-3001')
            ->assertJsonMissingPath('data.user.password');

        $this->assertSame(
            User::query()->where('email', 'newstaff@pcspc.local')->value('id'),
            $employee->fresh()->user_id
        );

        $this->assertTrue(
            AuthActivityLog::query()
                ->where('event', 'user.created')
                ->where('email', 'admin@pcspc.local')
                ->exists()
        );
    }

    public function test_admin_can_update_linked_user_email_and_sync_employee(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $target = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();
        $employee = $target->employee;
        $this->assertNotNull($employee);

        $this->actingAs($admin)
            ->putJson("/api/v1/security/users/{$target->uuid}", [
                'name' => $target->name,
                'email' => 'employee.updated@pcspc.local',
                'is_active' => true,
                'mfa_enabled' => false,
                'role_ids' => $target->roles()->pluck('uuid')->all(),
            ])
            ->assertOk()
            ->assertJsonPath('data.user.email', 'employee.updated@pcspc.local');

        $this->assertSame('employee.updated@pcspc.local', $target->fresh()->email);
        $this->assertSame('employee.updated@pcspc.local', $employee->fresh()->email);
    }

    public function test_admin_can_override_email_when_creating_user_from_employee(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $role = Role::query()->where('slug', 'employee')->firstOrFail();

        $employee = \App\Models\Employee::query()->create([
            'employee_number' => 'EMP-3002',
            'first_name' => 'Override',
            'last_name' => 'Email',
            'email' => 'old.override@pcspc.local',
            'employment_status' => 'active',
        ]);

        $this->actingAs($admin)
            ->postJson('/api/v1/security/users', [
                'employee_id' => $employee->uuid,
                'name' => 'Override Email',
                'email' => 'new.override@pcspc.local',
                'password' => 'Password1!',
                'role_ids' => [$role->uuid],
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.user.email', 'new.override@pcspc.local');

        $this->assertSame('new.override@pcspc.local', $employee->fresh()->email);
    }

    public function test_cannot_create_duplicate_account_for_linked_employee(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $role = Role::query()->where('slug', 'employee')->firstOrFail();
        $employee = \App\Models\Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();

        $this->actingAs($admin)
            ->postJson('/api/v1/security/users', [
                'employee_id' => $employee->uuid,
                'name' => $employee->fullName(),
                'email' => $employee->email,
                'password' => 'Password1!',
                'role_ids' => [$role->uuid],
            ])
            ->assertStatus(422)
            ->assertJsonPath('status', false);
    }

    public function test_admin_can_search_employees_for_account(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $this->actingAs($admin)
            ->getJson('/api/v1/security/employees/search?search=EMP-1001')
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.items.0.has_account', true);
    }

    public function test_admin_can_list_audit_logs_and_departments(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $this->actingAs($admin)
            ->getJson('/api/v1/audit/logs')
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->actingAs($admin)
            ->getJson('/api/v1/departments')
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->assertTrue(Department::query()->where('code', 'HR')->exists());
        $this->assertTrue(Permission::query()->where('slug', 'departments.manage')->exists());
    }

    public function test_protected_super_admin_exists_without_employee_link(): void
    {
        $super = User::query()->where('email', 'superadmin@pcspc.local')->firstOrFail();

        $this->assertTrue($super->is_protected);
        $this->assertTrue($super->roles()->where('slug', 'super-admin')->exists());
        $this->assertNull($super->employee_number);
        $this->assertNull($super->employee);
    }

    public function test_protected_super_admin_cannot_be_deleted_or_deactivated(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $super = User::query()->where('email', 'superadmin@pcspc.local')->firstOrFail();

        $this->actingAs($admin)
            ->postJson("/api/v1/security/users/{$super->uuid}/deactivate")
            ->assertStatus(422)
            ->assertJsonPath('status', false);

        $this->actingAs($admin)
            ->deleteJson("/api/v1/security/users/{$super->uuid}")
            ->assertStatus(422)
            ->assertJsonPath('status', false);

        $this->assertTrue($super->fresh()->is_active);
        $this->assertDatabaseHas('users', ['email' => 'superadmin@pcspc.local']);
    }

    public function test_security_update_does_not_change_employee_number(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $target = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();
        $original = $target->employee_number;

        $this->actingAs($admin)
            ->putJson("/api/v1/security/users/{$target->uuid}", [
                'name' => $target->name,
                'email' => $target->email,
                'employee_number' => 'EMP-HACKED',
                'is_active' => true,
                'mfa_enabled' => false,
                'role_ids' => $target->roles()->pluck('uuid')->all(),
            ])
            ->assertOk();

        $this->assertSame($original, $target->fresh()->employee_number);
    }

    public function test_department_mutations_write_audit_logs(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $this->actingAs($admin)
            ->postJson('/api/v1/departments', [
                'code' => 'AUD',
                'name' => 'Audit Dept',
                'description' => 'For audit coverage',
                'is_active' => true,
            ])
            ->assertCreated();

        $this->assertTrue(
            AuthActivityLog::query()->where('event', 'department.created')->exists()
        );

        $department = Department::query()->where('code', 'AUD')->firstOrFail();

        $this->actingAs($admin)
            ->putJson("/api/v1/departments/{$department->uuid}", [
                'code' => 'AUD',
                'name' => 'Audit Dept Updated',
                'description' => 'Updated',
                'is_active' => true,
            ])
            ->assertOk();

        $this->assertTrue(
            AuthActivityLog::query()->where('event', 'department.updated')->exists()
        );

        $this->actingAs($admin)
            ->deleteJson("/api/v1/departments/{$department->uuid}")
            ->assertOk();

        $this->assertTrue(
            AuthActivityLog::query()->where('event', 'department.deleted')->exists()
        );

        $this->actingAs($admin)
            ->getJson('/api/v1/audit/logs?event=department.created')
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.items.0.event', 'department.created');
    }
}
