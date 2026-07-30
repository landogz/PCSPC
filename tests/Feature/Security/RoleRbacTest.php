<?php

namespace Tests\Feature\Security;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AuthSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthSeeder::class);
    }

    public function test_admin_can_create_role_with_permissions(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $permission = Permission::query()->where('slug', 'dashboard.view')->firstOrFail();

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/security/rbac/roles', [
                'name' => 'Department Head',
                'description' => 'Approves leave for a unit',
                'requires_mfa' => false,
                'permission_ids' => [$permission->uuid],
            ]);

        $response->assertCreated()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.role.name', 'Department Head')
            ->assertJsonPath('data.role.slug', 'department-head');

        $this->assertDatabaseHas('roles', ['slug' => 'department-head']);
    }

    public function test_system_role_cannot_be_deleted(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $role = Role::query()->where('slug', 'employee')->firstOrFail();

        $this->actingAs($admin)
            ->deleteJson('/api/v1/security/rbac/roles/'.$role->uuid)
            ->assertStatus(422)
            ->assertJsonPath('status', false);
    }

    public function test_employee_cannot_manage_roles(): void
    {
        $employee = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();

        $this->actingAs($employee)
            ->getJson('/api/v1/security/rbac/roles')
            ->assertStatus(403);
    }
}
