<?php

namespace Tests\Feature\Leave;

use App\Models\AuthActivityLog;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveLedgerEntry;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\WorkflowInstance;
use Database\Seeders\AuthSeeder;
use Database\Seeders\Leave\LeaveTypeSeeder;
use Database\Seeders\Workflow\WorkflowDefinitionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LeaveRequestModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthSeeder::class);
        $this->seed(LeaveTypeSeeder::class);
        $this->seed(WorkflowDefinitionSeeder::class);
        Mail::fake();
    }

    public function test_employee_can_file_and_cancel_leave_request(): void
    {
        $employeeUser = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();
        $vl = LeaveType::query()->where('code', 'VL')->firstOrFail();

        LeaveBalance::query()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $vl->id,
            'leave_year' => 2026,
            'beginning' => 5,
            'earned' => 0,
            'used' => 0,
            'adjusted' => 0,
        ]);

        $create = $this->actingAs($employeeUser)
            ->postJson('/api/v1/leave/requests', [
                'leave_type_id' => $vl->uuid,
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-02',
                'reason' => 'Family trip',
            ])
            ->assertCreated()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.request.status', 'pending')
            ->assertJsonPath('data.request.days', '2.00')
            ->assertJsonPath('data.request.workflow.current_step_label', 'Approver');

        $requestId = $create->json('data.request.id');
        $this->assertTrue(AuthActivityLog::query()->where('event', 'leave.request.submitted')->exists());
        $this->assertTrue(UserNotification::query()->where('type', 'leave.request.submitted')->exists());
        $this->assertTrue(WorkflowInstance::query()->where('status', 'pending')->exists());

        $this->actingAs($employeeUser)
            ->getJson('/api/v1/leave/requests/mine')
            ->assertOk()
            ->assertJsonPath('data.items.0.id', $requestId);

        $this->actingAs($employeeUser)
            ->postJson("/api/v1/leave/requests/{$requestId}/cancel")
            ->assertOk()
            ->assertJsonPath('data.request.status', 'cancelled');

        $this->assertTrue(AuthActivityLog::query()->where('event', 'leave.request.cancelled')->exists());
        $this->assertSame('cancelled', WorkflowInstance::query()->first()?->status);
    }

    public function test_two_step_approval_deducts_used_balance_on_final(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employeeUser = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();
        $vl = LeaveType::query()->where('code', 'VL')->firstOrFail();

        LeaveBalance::query()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $vl->id,
            'leave_year' => 2026,
            'beginning' => 10,
            'earned' => 0,
            'used' => 0,
            'adjusted' => 0,
        ]);

        $requestId = $this->actingAs($employeeUser)
            ->postJson('/api/v1/leave/requests', [
                'leave_type_id' => $vl->uuid,
                'start_date' => '2026-10-05',
                'end_date' => '2026-10-05',
                'reason' => 'Personal errand',
            ])
            ->assertCreated()
            ->json('data.request.id');

        $step1 = $this->actingAs($admin)
            ->postJson("/api/v1/leave/requests/{$requestId}/approve", [
                'approver_notes' => 'Ops OK',
            ])
            ->assertOk();

        $this->assertSame('pending', $step1->json('data.request.status'));
        $this->assertSame('HR', $step1->json('data.request.workflow.current_step_label'));
        $this->assertSame(0.0, (float) LeaveBalance::query()->where('employee_id', $employee->id)->value('used'));

        $this->actingAs($admin)
            ->postJson("/api/v1/leave/requests/{$requestId}/approve", [
                'approver_notes' => 'HR OK',
            ])
            ->assertOk()
            ->assertJsonPath('data.request.status', 'approved');

        $balance = LeaveBalance::query()
            ->where('employee_id', $employee->id)
            ->where('leave_type_id', $vl->id)
            ->where('leave_year', 2026)
            ->firstOrFail();

        $this->assertSame('1.00', number_format((float) $balance->used, 2, '.', ''));
        $this->assertSame(9.0, $balance->ending());
        $this->assertTrue(
            LeaveLedgerEntry::query()
                ->where('entry_type', 'use')
                ->where('employee_id', $employee->id)
                ->exists()
        );
        $this->assertTrue(AuthActivityLog::query()->where('event', 'leave.request.approved')->exists());
        $this->assertTrue(UserNotification::query()->where('type', 'leave.request.approved')->exists());
    }

    public function test_requires_hr_type_uses_single_hr_step(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employeeUser = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();
        $bl = LeaveType::query()->where('code', 'BL')->firstOrFail();
        $bl->update(['is_active' => true, 'requires_hr' => true]);

        LeaveBalance::query()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $bl->id,
            'leave_year' => 2026,
            'beginning' => 5,
        ]);

        $requestId = $this->actingAs($employeeUser)
            ->postJson('/api/v1/leave/requests', [
                'leave_type_id' => $bl->uuid,
                'start_date' => '2026-11-10',
                'end_date' => '2026-11-10',
                'reason' => 'Family bereavement',
            ])
            ->assertCreated()
            ->assertJsonPath('data.request.workflow.current_step_label', 'HR')
            ->json('data.request.id');

        $this->actingAs($admin)
            ->postJson("/api/v1/leave/requests/{$requestId}/approve")
            ->assertOk()
            ->assertJsonPath('data.request.status', 'approved');
    }

    public function test_employee_cannot_approve_leave(): void
    {
        $employeeUser = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();
        $vl = LeaveType::query()->where('code', 'VL')->firstOrFail();

        LeaveBalance::query()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $vl->id,
            'leave_year' => 2026,
            'beginning' => 5,
        ]);

        $requestId = $this->actingAs($employeeUser)
            ->postJson('/api/v1/leave/requests', [
                'leave_type_id' => $vl->uuid,
                'start_date' => '2026-11-01',
                'end_date' => '2026-11-01',
                'reason' => 'One day off',
            ])
            ->assertCreated()
            ->json('data.request.id');

        $this->actingAs($employeeUser)
            ->postJson("/api/v1/leave/requests/{$requestId}/approve")
            ->assertForbidden();
    }

    public function test_filing_requires_reason_and_sufficient_balance(): void
    {
        $employeeUser = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();
        $vl = LeaveType::query()->where('code', 'VL')->firstOrFail();

        $this->actingAs($employeeUser)
            ->postJson('/api/v1/leave/requests', [
                'leave_type_id' => $vl->uuid,
                'start_date' => '2026-12-01',
                'end_date' => '2026-12-01',
                'reason' => '',
            ])
            ->assertStatus(422);

        LeaveBalance::query()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $vl->id,
            'leave_year' => 2026,
            'beginning' => 0.5,
        ]);

        $this->actingAs($employeeUser)
            ->postJson('/api/v1/leave/requests', [
                'leave_type_id' => $vl->uuid,
                'start_date' => '2026-12-01',
                'end_date' => '2026-12-03',
                'reason' => 'Need three days',
            ])
            ->assertStatus(422)
            ->assertJsonPath('status', false);
    }

    public function test_reject_does_not_deduct_balance(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employeeUser = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();
        $vl = LeaveType::query()->where('code', 'VL')->firstOrFail();

        LeaveBalance::query()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $vl->id,
            'leave_year' => 2026,
            'beginning' => 4,
        ]);

        $requestId = $this->actingAs($employeeUser)
            ->postJson('/api/v1/leave/requests', [
                'leave_type_id' => $vl->uuid,
                'start_date' => '2026-08-20',
                'end_date' => '2026-08-20',
                'reason' => 'Try reject path',
            ])
            ->assertCreated()
            ->json('data.request.id');

        $this->actingAs($admin)
            ->postJson("/api/v1/leave/requests/{$requestId}/reject", [
                'approver_notes' => 'Coverage issue',
            ])
            ->assertOk()
            ->assertJsonPath('data.request.status', 'rejected');

        $this->assertSame(0.0, (float) LeaveBalance::query()->where('employee_id', $employee->id)->value('used'));
        $this->assertFalse(LeaveLedgerEntry::query()->where('entry_type', 'use')->exists());
        $this->assertTrue(LeaveRequest::query()->where('uuid', $requestId)->where('status', 'rejected')->exists());
        $this->assertSame('rejected', WorkflowInstance::query()->first()?->status);
    }

    public function test_leave_module_page_loads_for_employee(): void
    {
        $employeeUser = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();

        $this->actingAs($employeeUser)
            ->get('/modules/leave')
            ->assertOk()
            ->assertSee('My leave requests', false);
    }
}
