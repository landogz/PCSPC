<?php

namespace Tests\Feature\Schedules;

use App\Models\AuthActivityLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\User;
use Database\Seeders\AuthSeeder;
use Database\Seeders\Shifts\PhilippineShiftSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthSeeder::class);
        $this->seed(PhilippineShiftSeeder::class);
    }

    public function test_admin_can_assign_employee_and_department_schedules(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-0001')->firstOrFail();
        $department = Department::query()->firstOrFail();
        $shift = Shift::query()->where('is_active', true)->firstOrFail();

        $employeeAssign = $this->actingAs($admin)
            ->postJson('/api/v1/schedules', [
                'shift_id' => $shift->uuid,
                'assignee_type' => 'employee',
                'employee_id' => $employee->uuid,
                'effective_from' => now()->toDateString(),
                'days_of_week' => [1, 2, 3, 4, 5],
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.schedule.assignee_type', 'employee');

        $this->assertTrue(AuthActivityLog::query()->where('event', 'schedule.created')->exists());

        $this->actingAs($admin)
            ->postJson('/api/v1/schedules', [
                'shift_id' => $shift->uuid,
                'assignee_type' => 'department',
                'department_id' => $department->uuid,
                'effective_from' => now()->toDateString(),
                'effective_to' => now()->addMonth()->toDateString(),
                'days_of_week' => [1, 2, 3, 4, 5, 6],
                'notes' => 'Ops rotating coverage',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.schedule.assignee_type', 'department');

        $this->actingAs($admin)
            ->getJson('/api/v1/schedules?effective=current')
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.meta.total', 2);

        $scheduleId = $employeeAssign->json('data.schedule.id');

        $this->actingAs($admin)
            ->putJson("/api/v1/schedules/{$scheduleId}", [
                'shift_id' => $shift->uuid,
                'assignee_type' => 'employee',
                'employee_id' => $employee->uuid,
                'effective_from' => now()->toDateString(),
                'days_of_week' => [1, 2, 3, 4, 5],
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.schedule.is_active', false);

        $this->actingAs($admin)
            ->deleteJson("/api/v1/schedules/{$scheduleId}")
            ->assertOk();

        $this->assertDatabaseMissing('shift_schedules', ['uuid' => $scheduleId]);
        $this->assertTrue(AuthActivityLog::query()->where('event', 'schedule.deleted')->exists());
    }

    public function test_employee_cannot_manage_schedules(): void
    {
        $employee = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();

        $this->actingAs($employee)
            ->getJson('/api/v1/schedules')
            ->assertForbidden();

        $this->actingAs($employee)
            ->get('/modules/schedules')
            ->assertForbidden();
    }

    public function test_admin_can_open_schedules_module_and_meta(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $this->actingAs($admin)
            ->get('/modules/schedules')
            ->assertOk()
            ->assertSee('ADM-009', false)
            ->assertSee('Shift assignments', false)
            ->assertSee('data-employee-search', false);

        $this->actingAs($admin)
            ->getJson('/api/v1/schedules/meta')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'shifts',
                    'departments',
                    'assignee_types',
                    'days_of_week',
                ],
            ]);
    }

    public function test_assignment_requires_matching_assignee(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $shift = Shift::query()->where('is_active', true)->firstOrFail();

        $this->actingAs($admin)
            ->postJson('/api/v1/schedules', [
                'shift_id' => $shift->uuid,
                'assignee_type' => 'employee',
                'effective_from' => now()->toDateString(),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['employee_id']);
    }
}
