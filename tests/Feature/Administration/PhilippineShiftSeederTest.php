<?php

namespace Tests\Feature\Administration;

use App\Models\Shift;
use Database\Seeders\Shifts\PhilippineShiftSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhilippineShiftSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_philippine_shift_templates(): void
    {
        $this->seed(PhilippineShiftSeeder::class);

        $day = Shift::query()->where('code', 'DAY')->firstOrFail();
        $this->assertSame('08:00', $day->time_in);
        $this->assertSame('17:00', $day->time_out);
        $this->assertSame(60, $day->break_minutes);
        $this->assertFalse($day->crosses_midnight);
        $this->assertSame(8.0, round($day->workMinutes() / 60, 2));

        $night = Shift::query()->where('code', 'NIGHT')->firstOrFail();
        $this->assertTrue($night->crosses_midnight);
        $this->assertSame(8.0, round($night->workMinutes() / 60, 2));

        $h12 = Shift::query()->where('code', 'H12-DAY')->firstOrFail();
        $this->assertSame(11.0, round($h12->workMinutes() / 60, 2));

        $this->assertTrue(Shift::query()->where('code', 'BPO-C')->where('crosses_midnight', true)->exists());
        $this->assertTrue(Shift::query()->where('code', 'SEC-NIGHT')->exists());
        $this->assertTrue(Shift::query()->where('code', 'HALF-AM')->exists());

        $count = Shift::query()->count();
        $this->assertGreaterThanOrEqual(25, $count);

        $this->seed(PhilippineShiftSeeder::class);
        $this->assertSame($count, Shift::query()->count());
    }
}
