<?php

namespace Tests\Feature\Administration;

use App\Models\Holiday;
use Database\Seeders\Holidays\PhilippineHolidaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhilippineHolidaySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_philippine_holidays_for_current_plan_years(): void
    {
        $this->seed(PhilippineHolidaySeeder::class);

        $this->assertTrue(
            Holiday::query()
                ->whereDate('holiday_date', '2026-06-12')
                ->where('name', 'Independence Day')
                ->where('type', 'regular')
                ->where('is_double_pay', true)
                ->exists()
        );

        $this->assertTrue(
            Holiday::query()
                ->whereDate('holiday_date', '2026-01-01')
                ->where('name', "New Year's Day")
                ->exists()
        );

        $this->assertTrue(
            Holiday::query()
                ->where('name', 'Good Friday')
                ->whereYear('holiday_date', 2026)
                ->where('type', 'regular')
                ->exists()
        );

        $this->assertTrue(
            Holiday::query()
                ->where('name', 'Chinese New Year')
                ->whereDate('holiday_date', '2026-02-17')
                ->where('type', 'special_non_working')
                ->exists()
        );

        $this->assertTrue(
            Holiday::query()
                ->where('name', 'National Heroes Day')
                ->whereDate('holiday_date', '2026-08-31')
                ->exists()
        );

        // Idempotent re-seed
        $count = Holiday::query()->count();
        $this->seed(PhilippineHolidaySeeder::class);
        $this->assertSame($count, Holiday::query()->count());

        // Full year pack (regular + special + movable)
        $this->assertGreaterThanOrEqual(20, Holiday::query()->whereYear('holiday_date', 2026)->count());
    }
}
