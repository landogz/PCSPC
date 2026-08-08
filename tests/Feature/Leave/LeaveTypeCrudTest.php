<?php

namespace Tests\Feature\Leave;

use App\Models\AuthActivityLog;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use Database\Seeders\AuthSeeder;
use Database\Seeders\Leave\LeaveTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveTypeCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthSeeder::class);
        $this->seed(LeaveTypeSeeder::class);
    }

    public function test_admin_can_create_update_and_delete_leave_type(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $create = $this->actingAs($admin)
            ->postJson('/api/v1/leave/types', [
                'code' => 'comp',
                'name' => 'Compensatory Leave',
                'is_accruing' => false,
                'requires_reason' => true,
                'requires_hr' => false,
                'is_active' => true,
                'sort_order' => 25,
            ])
            ->assertCreated()
            ->assertJsonPath('data.leave_type.code', 'COMP')
            ->assertJsonPath('data.leave_type.name', 'Compensatory Leave');

        $typeId = $create->json('data.leave_type.id');
        $this->assertTrue(AuthActivityLog::query()->where('event', 'leave.type.created')->exists());

        $this->actingAs($admin)
            ->putJson("/api/v1/leave/types/{$typeId}", [
                'code' => 'COMP',
                'name' => 'Comp Leave',
                'is_accruing' => false,
                'requires_reason' => true,
                'requires_hr' => true,
                'is_active' => false,
                'sort_order' => 30,
            ])
            ->assertOk()
            ->assertJsonPath('data.leave_type.name', 'Comp Leave')
            ->assertJsonPath('data.leave_type.requires_hr', true)
            ->assertJsonPath('data.leave_type.is_active', false);

        $this->assertTrue(AuthActivityLog::query()->where('event', 'leave.type.updated')->exists());

        $this->actingAs($admin)
            ->deleteJson("/api/v1/leave/types/{$typeId}")
            ->assertOk();

        $this->assertFalse(LeaveType::query()->where('uuid', $typeId)->exists());
        $this->assertTrue(AuthActivityLog::query()->where('event', 'leave.type.deleted')->exists());
    }

    public function test_cannot_delete_leave_type_in_use(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $vl = LeaveType::query()->where('code', 'VL')->firstOrFail();
        $employee = \App\Models\Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();

        LeaveBalance::query()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $vl->id,
            'leave_year' => 2026,
            'beginning' => 1,
        ]);

        $this->actingAs($admin)
            ->deleteJson("/api/v1/leave/types/{$vl->uuid}")
            ->assertStatus(422)
            ->assertJsonPath('status', false);

        $this->assertTrue(LeaveType::query()->where('uuid', $vl->uuid)->exists());
    }

    public function test_employee_cannot_manage_leave_types(): void
    {
        $employee = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();

        $this->actingAs($employee)
            ->postJson('/api/v1/leave/types', [
                'code' => 'X1',
                'name' => 'Nope',
            ])
            ->assertForbidden();
    }
}
