<?php

namespace Tests\Feature\Leave;

use App\Models\AuthActivityLog;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveLedgerEntry;
use App\Models\LeaveType;
use App\Models\User;
use Database\Seeders\AuthSeeder;
use Database\Seeders\Leave\LeaveTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveBalanceModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthSeeder::class);
        $this->seed(LeaveTypeSeeder::class);
    }

    public function test_admin_can_list_leave_types_and_module_page(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $this->actingAs($admin)
            ->get('/modules/leave')
            ->assertOk()
            ->assertSee('Leave balances', false);

        $this->actingAs($admin)
            ->getJson('/api/v1/leave/types?all=1')
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.items.0.code', 'VL')
            ->assertJsonFragment(['code' => 'SL']);
    }

    public function test_employee_without_manage_cannot_adjust_or_run_accrual(): void
    {
        $employeeUser = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();
        $vl = LeaveType::query()->where('code', 'VL')->firstOrFail();

        $this->actingAs($employeeUser)
            ->getJson('/api/v1/leave/balances')
            ->assertForbidden();

        $this->actingAs($employeeUser)
            ->postJson('/api/v1/leave/balances/adjust', [
                'employee_id' => $employee->uuid,
                'leave_type_id' => $vl->uuid,
                'amount' => 1,
                'reason' => 'Should not work',
            ])
            ->assertForbidden();

        $this->actingAs($employeeUser)
            ->postJson('/api/v1/leave/accruals/run', ['year_month' => '2026-01'])
            ->assertForbidden();
    }

    public function test_admin_can_adjust_balance_and_writes_ledger_and_audit(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();
        $vl = LeaveType::query()->where('code', 'VL')->firstOrFail();

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/leave/balances/adjust', [
                'employee_id' => $employee->uuid,
                'leave_type_id' => $vl->uuid,
                'leave_year' => 2026,
                'amount' => 2.5,
                'reason' => 'Opening balance grant',
                'effective_date' => '2026-01-01',
            ])
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.balance.earned', '0.00')
            ->assertJsonPath('data.balance.adjusted', '2.50')
            ->assertJsonPath('data.balance.ending', '2.50');

        $balanceId = $response->json('data.balance.id');
        $this->assertNotEmpty($balanceId);
        $this->assertTrue(
            LeaveLedgerEntry::query()
                ->where('entry_type', 'adjust')
                ->where('amount', 2.5)
                ->whereHas('balance', fn ($q) => $q->where('uuid', $balanceId))
                ->exists()
        );
        $this->assertTrue(AuthActivityLog::query()->where('event', 'leave.balance.adjusted')->exists());

        $this->actingAs($admin)
            ->getJson('/api/v1/leave/balances?leave_year=2026&search=Demo')
            ->assertOk()
            ->assertJsonPath('data.items.0.id', $balanceId);

        $this->actingAs($admin)
            ->getJson("/api/v1/employees/{$employee->uuid}/leave-balances?leave_year=2026")
            ->assertOk()
            ->assertJsonPath('data.items.0.id', $balanceId);
    }

    public function test_monthly_accrual_uses_tenure_tiers_and_is_idempotent(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();
        $employee->forceFill([
            'date_hired' => '2020-01-15',
            'date_regularized' => '2020-07-15',
            'employment_status' => 'active',
        ])->save();

        $junior = Employee::query()->create([
            'employee_number' => 'EMP-LEAVE-JR',
            'first_name' => 'Junior',
            'last_name' => 'Hire',
            'email' => 'junior.leave@pcspc.local',
            'employment_status' => 'active',
            'date_hired' => '2025-01-01',
            'date_regularized' => '2025-07-01',
            'department_id' => $employee->department_id,
        ]);

        $probation = Employee::query()->create([
            'employee_number' => 'EMP-LEAVE-PR',
            'first_name' => 'Probation',
            'last_name' => 'Only',
            'email' => 'probation.leave@pcspc.local',
            'employment_status' => 'active',
            'date_hired' => '2025-06-01',
            'date_regularized' => null,
            'department_id' => $employee->department_id,
        ]);

        $first = $this->actingAs($admin)
            ->postJson('/api/v1/leave/accruals/run', ['year_month' => '2026-03'])
            ->assertOk()
            ->assertJsonPath('status', true)
            ->json('data');

        $this->assertGreaterThanOrEqual(2, (int) $first['accrued']);
        $this->assertTrue(AuthActivityLog::query()->where('event', 'leave.accrual.run')->exists());

        $seniorBalance = LeaveBalance::query()
            ->where('employee_id', $employee->id)
            ->where('leave_year', 2026)
            ->firstOrFail();
        // 6 years as of 2026-03-31 → 1.50
        $this->assertSame('1.50', number_format((float) $seniorBalance->earned, 2, '.', ''));

        $juniorBalance = LeaveBalance::query()
            ->where('employee_id', $junior->id)
            ->where('leave_year', 2026)
            ->firstOrFail();
        // 1 year → 1.25
        $this->assertSame('1.25', number_format((float) $juniorBalance->earned, 2, '.', ''));

        $this->assertFalse(
            LeaveBalance::query()->where('employee_id', $probation->id)->exists()
        );

        $earnCount = LeaveLedgerEntry::query()
            ->where('employee_id', $employee->id)
            ->where('entry_type', 'earn')
            ->where('period_key', '2026-03')
            ->count();
        $this->assertSame(1, $earnCount);

        $second = $this->actingAs($admin)
            ->postJson('/api/v1/leave/accruals/run', ['year_month' => '2026-03'])
            ->assertOk()
            ->json('data');

        $this->assertSame(0, (int) $second['accrued']);
        $seniorBalance->refresh();
        $this->assertSame('1.50', number_format((float) $seniorBalance->earned, 2, '.', ''));
        $this->assertSame(1, LeaveLedgerEntry::query()
            ->where('employee_id', $employee->id)
            ->where('entry_type', 'earn')
            ->where('period_key', '2026-03')
            ->count());
    }

    public function test_adjust_validation_requires_reason_and_nonzero_amount(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();
        $vl = LeaveType::query()->where('code', 'VL')->firstOrFail();

        $this->actingAs($admin)
            ->postJson('/api/v1/leave/balances/adjust', [
                'employee_id' => $employee->uuid,
                'leave_type_id' => $vl->uuid,
                'amount' => 0,
                'reason' => '',
            ])
            ->assertStatus(422)
            ->assertJsonPath('status', false);
    }

    public function test_veteran_tier_earns_one_point_six_six(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();
        $employee->forceFill([
            'date_hired' => '2010-01-01',
            'date_regularized' => '2010-07-01',
            'employment_status' => 'active',
        ])->save();

        // Avoid double-counting other seeded regularized employees in assertions on this employee only.
        Employee::query()
            ->where('id', '!=', $employee->id)
            ->update(['date_regularized' => null]);

        $this->actingAs($admin)
            ->postJson('/api/v1/leave/accruals/run', ['year_month' => '2026-06'])
            ->assertOk();

        $balance = LeaveBalance::query()
            ->where('employee_id', $employee->id)
            ->where('leave_year', 2026)
            ->firstOrFail();

        $this->assertSame('1.66', number_format((float) $balance->earned, 2, '.', ''));
        $this->assertNotEmpty(LeaveLedgerEntry::query()->where('uuid', '!=', '')->where('employee_id', $employee->id)->value('uuid'));
    }
}
