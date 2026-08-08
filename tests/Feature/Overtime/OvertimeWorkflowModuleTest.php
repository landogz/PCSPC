<?php

namespace Tests\Feature\Overtime;

use App\Models\AuthActivityLog;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\WorkflowInstance;
use Database\Seeders\AuthSeeder;
use Database\Seeders\Workflow\WorkflowDefinitionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OvertimeWorkflowModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthSeeder::class);
        $this->seed(WorkflowDefinitionSeeder::class);
        Mail::fake();
    }

    public function test_employee_can_file_and_cancel_ot_request(): void
    {
        $employeeUser = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();

        $create = $this->actingAs($employeeUser)
            ->postJson('/api/v1/overtime/requests', [
                'kind' => 'ot',
                'work_date' => '2026-09-15',
                'hours' => 2.5,
                'reason' => 'Pipeline maintenance window',
            ])
            ->assertCreated()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.request.status', 'pending')
            ->assertJsonPath('data.request.hours', '2.50')
            ->assertJsonPath('data.request.workflow.current_step_label', 'Approver');

        $requestId = $create->json('data.request.id');
        $this->assertTrue(AuthActivityLog::query()->where('event', 'overtime.request.submitted')->exists());
        $this->assertTrue(WorkflowInstance::query()->where('status', 'pending')->exists());
        $this->assertTrue(UserNotification::query()->where('type', 'overtime.request.submitted')->exists());

        $this->actingAs($employeeUser)
            ->getJson('/api/v1/overtime/requests/mine')
            ->assertOk()
            ->assertJsonPath('data.items.0.id', $requestId);

        $this->actingAs($employeeUser)
            ->postJson("/api/v1/overtime/requests/{$requestId}/cancel")
            ->assertOk()
            ->assertJsonPath('data.request.status', 'cancelled');

        $this->assertTrue(AuthActivityLog::query()->where('event', 'overtime.request.cancelled')->exists());
        $this->assertSame('cancelled', WorkflowInstance::query()->first()?->status);
    }

    public function test_two_step_approval_completes_ot_request(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employeeUser = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();

        $requestId = $this->actingAs($employeeUser)
            ->postJson('/api/v1/overtime/requests', [
                'kind' => 'ot_meal',
                'work_date' => '2026-09-20',
                'hours' => 3,
                'reason' => 'Night shift coverage',
                'meal_notes' => 'Dinner allowance for crew',
            ])
            ->assertCreated()
            ->json('data.request.id');

        $step1 = $this->actingAs($admin)
            ->postJson("/api/v1/overtime/requests/{$requestId}/approve", [
                'notes' => 'Ops OK',
            ])
            ->assertOk();

        $this->assertSame('pending', $step1->json('data.request.status'));
        $this->assertSame('HR', $step1->json('data.request.workflow.current_step_label'));

        $this->actingAs($admin)
            ->getJson('/api/v1/workflow/inbox')
            ->assertOk()
            ->assertJsonPath('status', true);

        $step2 = $this->actingAs($admin)
            ->postJson("/api/v1/overtime/requests/{$requestId}/approve", [
                'notes' => 'HR OK',
            ])
            ->assertOk();

        $this->assertSame('approved', $step2->json('data.request.status'));
        $this->assertSame('approved', $step2->json('data.request.workflow.status'));
        $this->assertTrue(AuthActivityLog::query()->where('event', 'overtime.request.approved')->exists());
    }

    public function test_reject_at_first_step_rejects_ot(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employeeUser = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();

        $requestId = $this->actingAs($employeeUser)
            ->postJson('/api/v1/overtime/requests', [
                'kind' => 'ot',
                'work_date' => '2026-09-22',
                'hours' => 1,
                'reason' => 'Extra loading',
            ])
            ->assertCreated()
            ->json('data.request.id');

        $this->actingAs($admin)
            ->postJson("/api/v1/overtime/requests/{$requestId}/reject", [
                'notes' => 'Not authorized',
            ])
            ->assertOk()
            ->assertJsonPath('data.request.status', 'rejected');

        $this->assertSame('rejected', OvertimeRequest::query()->where('uuid', $requestId)->value('status'));
        $this->assertTrue(AuthActivityLog::query()->where('event', 'overtime.request.rejected')->exists());
    }

    public function test_employee_cannot_approve_ot(): void
    {
        $employeeUser = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();

        $requestId = $this->actingAs($employeeUser)
            ->postJson('/api/v1/overtime/requests', [
                'kind' => 'ot',
                'work_date' => '2026-09-25',
                'hours' => 2,
                'reason' => 'Self approve attempt',
            ])
            ->assertCreated()
            ->json('data.request.id');

        $this->actingAs($employeeUser)
            ->postJson("/api/v1/overtime/requests/{$requestId}/approve")
            ->assertForbidden();
    }

    public function test_duplicate_pending_same_day_kind_blocked(): void
    {
        $employeeUser = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();
        Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();

        $payload = [
            'kind' => 'ot',
            'work_date' => '2026-09-28',
            'hours' => 2,
            'reason' => 'First filing',
        ];

        $this->actingAs($employeeUser)
            ->postJson('/api/v1/overtime/requests', $payload)
            ->assertCreated();

        $this->actingAs($employeeUser)
            ->postJson('/api/v1/overtime/requests', [
                ...$payload,
                'reason' => 'Duplicate filing',
            ])
            ->assertStatus(422);
    }

    public function test_workflow_definitions_list(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/workflow/definitions')
            ->assertOk();

        $codes = collect($response->json('data.items'))->pluck('code')->all();
        $this->assertContains('overtime', $codes);
        $this->assertContains('leave', $codes);
        $this->assertContains('leave_hr', $codes);

        $overtime = collect($response->json('data.items'))->firstWhere('code', 'overtime');
        $this->assertSame('Approver', $overtime['steps'][0]['label']);
        $this->assertSame('HR', $overtime['steps'][1]['label']);
    }
}