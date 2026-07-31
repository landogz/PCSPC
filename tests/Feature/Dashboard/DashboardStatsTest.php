<?php

namespace Tests\Feature\Dashboard;

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
        $this->assertSame($onLeave, (int) $response->json('data.summary.on_leave'));
    }

    public function test_guest_cannot_load_dashboard_stats(): void
    {
        $this->getJson('/api/v1/dashboard/stats')->assertUnauthorized();
    }
}
