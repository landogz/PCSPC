<?php

namespace Tests\Feature\Administration;

use App\Models\AuthActivityLog;
use App\Models\Holiday;
use App\Models\Shift;
use App\Models\User;
use Database\Seeders\AuthSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HolidaysAndShiftsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthSeeder::class);
    }

    public function test_admin_can_manage_holidays(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $create = $this->actingAs($admin)
            ->postJson('/api/v1/holidays', [
                'name' => 'Independence Day',
                'holiday_date' => '2026-06-12',
                'type' => 'regular',
                'is_recurring' => true,
                'is_double_pay' => true,
                'paid_hours' => 8,
            ])
            ->assertCreated()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.holiday.type', 'regular')
            ->assertJsonPath('data.holiday.is_double_pay', true);

        $holidayId = $create->json('data.holiday.id');
        $this->assertTrue(AuthActivityLog::query()->where('event', 'holiday.created')->exists());

        $this->actingAs($admin)
            ->getJson('/api/v1/holidays?year=2026')
            ->assertOk()
            ->assertJsonPath('data.items.0.id', $holidayId)
            ->assertJsonStructure(['data' => ['items', 'types', 'meta']]);

        $this->actingAs($admin)
            ->putJson("/api/v1/holidays/{$holidayId}", [
                'name' => 'Independence Day',
                'holiday_date' => '2026-06-12',
                'type' => 'regular',
                'is_recurring' => true,
                'is_double_pay' => true,
                'paid_hours' => 8,
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.holiday.is_active', false);

        $this->assertTrue(AuthActivityLog::query()->where('event', 'holiday.updated')->exists());

        $this->actingAs($admin)
            ->deleteJson("/api/v1/holidays/{$holidayId}")
            ->assertOk();

        $this->assertFalse(Holiday::query()->where('uuid', $holidayId)->exists());
        $this->assertTrue(AuthActivityLog::query()->where('event', 'holiday.deleted')->exists());
    }

    public function test_holiday_validation_requires_name_and_date(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $this->actingAs($admin)
            ->postJson('/api/v1/holidays', [])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'Please enter a holiday name.')
            ->assertJsonPath('errors.holiday_date.0', 'Please choose a holiday date.');
    }

    public function test_admin_can_manage_shifts(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $create = $this->actingAs($admin)
            ->postJson('/api/v1/shifts', [
                'code' => 'DAY',
                'name' => 'Day shift',
                'time_in' => '08:00',
                'time_out' => '17:00',
                'break_minutes' => 60,
                'grace_minutes' => 5,
            ])
            ->assertCreated()
            ->assertJsonPath('data.shift.code', 'DAY')
            ->assertJsonPath('data.shift.work_hours', 8);

        $shiftId = $create->json('data.shift.id');
        $this->assertTrue(AuthActivityLog::query()->where('event', 'shift.created')->exists());

        $this->actingAs($admin)
            ->getJson('/api/v1/shifts')
            ->assertOk()
            ->assertJsonPath('data.items.0.id', $shiftId);

        $this->actingAs($admin)
            ->putJson("/api/v1/shifts/{$shiftId}", [
                'code' => 'NIGHT',
                'name' => 'Night shift',
                'time_in' => '22:00',
                'time_out' => '06:00',
                'break_minutes' => 60,
                'crosses_midnight' => true,
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.shift.code', 'NIGHT')
            ->assertJsonPath('data.shift.crosses_midnight', true)
            ->assertJsonPath('data.shift.work_hours', 7);

        $this->assertTrue(AuthActivityLog::query()->where('event', 'shift.updated')->exists());

        $this->actingAs($admin)
            ->deleteJson("/api/v1/shifts/{$shiftId}")
            ->assertOk();

        $this->assertFalse(Shift::query()->where('uuid', $shiftId)->exists());
        $this->assertTrue(AuthActivityLog::query()->where('event', 'shift.deleted')->exists());
    }

    public function test_employee_cannot_access_holidays_or_shifts(): void
    {
        $employee = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();

        $this->actingAs($employee)
            ->getJson('/api/v1/holidays')
            ->assertStatus(403);

        $this->actingAs($employee)
            ->postJson('/api/v1/shifts', [
                'code' => 'X',
                'name' => 'Nope',
                'time_in' => '08:00',
                'time_out' => '17:00',
            ])
            ->assertStatus(403);

        $this->actingAs($employee)
            ->get('/modules/holidays')
            ->assertForbidden();

        $this->actingAs($employee)
            ->get('/modules/shifts')
            ->assertForbidden();
    }
}
