<?php

namespace Tests\Feature\Dashboard;

use App\Models\AuthActivityLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\AuthSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthSeeder::class);
    }

    public function test_admin_can_load_live_dashboard_stats(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        Employee::query()->where('employee_number', 'EMP-1001')->update([
            'employment_status' => 'on_leave',
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'data' => [
                    'employees' => ['value', 'delta_percent'],
                    'on_leave' => ['value', 'delta_percent', 'share_percent'],
                    'departments' => ['value', 'delta_percent'],
                    'attendance' => ['value', 'available'],
                    'summary' => ['check_ins', 'on_leave', 'late_arrivals'],
                    'headcount_movement' => ['hires_this_month', 'separations_this_month', 'net_this_month'],
                    'charts' => [
                        'attendance_trend',
                        'attendance_today',
                        'leave_by_month',
                        'department_headcount',
                        'headcount_trend',
                    ],
                    'pending' => ['total_actionable', 'items', 'incomplete_profiles'],
                    'on_leave_now' => ['available', 'count', 'items'],
                    'activity' => ['items'],
                ],
            ]);

        $employees = (int) $response->json('data.employees.value');
        $onLeave = (int) $response->json('data.on_leave.value');
        $departments = (int) $response->json('data.departments.value');

        $this->assertSame(
            Employee::query()->whereIn('employment_status', ['active', 'on_leave'])->count(),
            $employees,
        );
        $this->assertSame(
            Employee::query()->where('employment_status', 'on_leave')->count(),
            $onLeave,
        );
        $this->assertSame(
            Department::query()->where('is_active', true)->count(),
            $departments,
        );
        $this->assertFalse($response->json('data.attendance.available'));
        $this->assertFalse($response->json('data.charts.attendance_trend.available'));
        $this->assertFalse($response->json('data.charts.leave_by_month.available'));
        $this->assertTrue($response->json('data.charts.department_headcount.available'));
        $this->assertTrue($response->json('data.charts.headcount_trend.available'));
        $this->assertSame($onLeave, (int) $response->json('data.summary.on_leave'));
        $this->assertSame($onLeave, (int) $response->json('data.on_leave_now.count'));
        $this->assertCount(12, $response->json('data.charts.headcount_trend.labels'));
    }

    public function test_dashboard_includes_department_headcount_and_incomplete_profiles(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $department = Department::query()->where('is_active', true)->firstOrFail();

        Employee::query()->create([
            'employee_number' => 'EMP-DASH-01',
            'first_name' => 'Dash',
            'last_name' => 'Incomplete',
            'email' => 'dash.incomplete@pcspc.local',
            'employment_status' => 'active',
            'department_id' => null,
            'date_hired' => null,
            'birth_date' => null,
            'mobile' => null,
        ]);

        Employee::query()->create([
            'employee_number' => 'EMP-DASH-02',
            'first_name' => 'Dash',
            'last_name' => 'Assigned',
            'email' => 'dash.assigned@pcspc.local',
            'employment_status' => 'active',
            'department_id' => $department->id,
            'date_hired' => now()->toDateString(),
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/dashboard/stats')
            ->assertOk();

        $labels = $response->json('data.charts.department_headcount.labels');
        $this->assertContains($department->name, $labels);
        $this->assertGreaterThanOrEqual(1, (int) $response->json('data.pending.incomplete_profiles.count'));
        $this->assertGreaterThanOrEqual(1, (int) $response->json('data.pending.total_actionable'));
    }

    public function test_dashboard_headcount_movement_counts_hires_this_month(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        Employee::query()->create([
            'employee_number' => 'EMP-HIRE-01',
            'first_name' => 'New',
            'last_name' => 'Hire',
            'email' => 'new.hire@pcspc.local',
            'employment_status' => 'active',
            'date_hired' => now()->toDateString(),
        ]);

        $this->actingAs($admin)
            ->getJson('/api/v1/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('data.headcount_movement.hires_this_month', 1);
    }

    public function test_dashboard_includes_recent_activity_items(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        AuthActivityLog::query()->create([
            'user_id' => $admin->id,
            'email' => $admin->email,
            'event' => 'employee.created',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'meta' => ['name' => 'Sample Employee', 'employee_number' => 'EMP-ACT'],
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/dashboard/stats')
            ->assertOk();

        $messages = collect($response->json('data.activity.items'))->pluck('message')->all();
        $this->assertTrue(
            collect($messages)->contains(fn (string $message): bool => str_contains($message, 'Employee')),
        );
    }

    public function test_guest_cannot_load_dashboard_stats(): void
    {
        $this->getJson('/api/v1/dashboard/stats')->assertUnauthorized();
    }

    public function test_employee_dashboard_stats_hide_hr_org_metrics(): void
    {
        $employee = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();

        $this->actingAs($employee)
            ->getJson('/api/v1/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('data.mode', 'employee')
            ->assertJsonPath('data.employees.value', null)
            ->assertJsonPath('data.charts.department_headcount.available', false)
            ->assertJsonPath('data.activity.items', []);
    }

    public function test_employee_dashboard_page_hides_hr_widgets(): void
    {
        $employee = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();

        $this->actingAs($employee)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('self-service home', false)
            ->assertDontSee('Needs your attention', false)
            ->assertDontSee('Headcount by department', false)
            ->assertDontSee('Payroll & talent snapshot', false);
    }

    public function test_employee_cannot_open_help_module_page(): void
    {
        $employee = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();

        $this->actingAs($employee)
            ->get('/modules/help')
            ->assertForbidden();
    }
}
