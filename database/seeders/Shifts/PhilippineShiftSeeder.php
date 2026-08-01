<?php

namespace Database\Seeders\Shifts;

use App\Models\Shift;
use Illuminate\Database\Seeder;

/**
 * Seeds common Philippine shift templates for HR / timekeeping.
 *
 * Schedules follow typical Labor Code practice: 8 hours work exclusive of
 * a meal break (usually 60 minutes). Overnight rows set crosses_midnight.
 */
class PhilippineShiftSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->shifts() as $shift) {
            $attributes = [
                'name' => $shift['name'],
                'time_in' => $shift['time_in'],
                'time_out' => $shift['time_out'],
                'break_minutes' => $shift['break_minutes'],
                'grace_minutes' => $shift['grace_minutes'],
                'crosses_midnight' => $shift['crosses_midnight'],
                'description' => $shift['description'],
                'is_active' => true,
            ];

            $existing = Shift::query()->where('code', $shift['code'])->first();

            if ($existing) {
                $existing->fill($attributes)->save();
                continue;
            }

            Shift::query()->create([
                ...$attributes,
                'code' => $shift['code'],
            ]);
        }
    }

    /**
     * @return list<array{
     *     code: string,
     *     name: string,
     *     time_in: string,
     *     time_out: string,
     *     break_minutes: int,
     *     grace_minutes: int,
     *     crosses_midnight: bool,
     *     description: string
     * }>
     */
    private function shifts(): array
    {
        return [
            // —— Standard office / admin (8 work hours) ——
            $this->row('DAY', 'Day shift (standard)', '08:00', '17:00', 60, 15, false,
                'Standard PH day shift: 8 work hours + 60-minute meal break.'),
            $this->row('DAY07', 'Day shift (7:00 AM)', '07:00', '16:00', 60, 15, false,
                'Early day shift common in plant / ops offices.'),
            $this->row('DAY09', 'Day shift (9:00 AM)', '09:00', '18:00', 60, 15, false,
                'Later start day shift for admin / support teams.'),
            $this->row('OFFICE', 'Office hours', '08:30', '17:30', 60, 15, false,
                'Typical corporate office schedule with 8 work hours.'),

            // —— Split / rotating 8-hour bands ——
            $this->row('AM', 'Morning shift', '06:00', '15:00', 60, 10, false,
                'Morning band (often first shift in 3-shift operations).'),
            $this->row('MID', 'Mid shift', '14:00', '23:00', 60, 10, false,
                'Afternoon / mid band for continuous operations.'),
            $this->row('PM', 'Afternoon–evening shift', '15:00', '00:00', 60, 10, true,
                'Afternoon into midnight; overnight flag enabled.'),
            $this->row('NIGHT', 'Night shift', '22:00', '07:00', 60, 10, true,
                'Night / third shift crossing midnight (8 work hours).'),
            $this->row('GRAVEYARD', 'Graveyard shift', '23:00', '08:00', 60, 10, true,
                'Late night to morning graveyard schedule.'),
            $this->row('MIDNIGHT', 'Midnight shift', '00:00', '09:00', 60, 10, false,
                'Starts at midnight; same-calendar-day end (8 work hours).'),

            // —— Classic 3-shifting (A/B/C) with shorter meal ——
            $this->row('SHIFT-A', 'Rotating Shift A', '06:00', '14:00', 30, 10, false,
                '8-hour rotating A shift (30-minute break → 7.5 work hours).'),
            $this->row('SHIFT-B', 'Rotating Shift B', '14:00', '22:00', 30, 10, false,
                '8-hour rotating B shift (30-minute break → 7.5 work hours).'),
            $this->row('SHIFT-C', 'Rotating Shift C', '22:00', '06:00', 30, 10, true,
                '8-hour rotating C / night shift crossing midnight.'),

            // —— 12-hour compressed / plant / security ——
            $this->row('H12-DAY', '12-hour day', '07:00', '19:00', 60, 15, false,
                '12-hour day tour common in plant, terminal, and security posts.'),
            $this->row('H12-NIGHT', '12-hour night', '19:00', '07:00', 60, 15, true,
                '12-hour night tour crossing midnight.'),
            $this->row('COMPRESSED', 'Compressed week (10h)', '07:00', '18:00', 60, 10, false,
                'Compressed workweek day (10 work hours after meal break).'),

            // —— Half-day ——
            $this->row('HALF-AM', 'Half-day morning', '08:00', '12:00', 0, 5, false,
                'Half-day AM — 4 hours, no meal break.'),
            $this->row('HALF-PM', 'Half-day afternoon', '13:00', '17:00', 0, 5, false,
                'Half-day PM — 4 hours, no meal break.'),

            // —— BPO / shared services ——
            $this->row('BPO-A', 'BPO opening shift', '06:00', '15:00', 60, 5, false,
                'BPO / shared-services opening band.'),
            $this->row('BPO-B', 'BPO mid shift', '14:00', '23:00', 60, 5, false,
                'BPO / shared-services mid band.'),
            $this->row('BPO-C', 'BPO night shift', '22:00', '07:00', 60, 5, true,
                'BPO / shared-services night band crossing midnight.'),

            // —— Operations / terminal (PCSPC-style continuous ops) ——
            $this->row('OPS-DAY', 'Operations day', '08:00', '20:00', 60, 10, false,
                'Operations / terminal day coverage (11 work hours).'),
            $this->row('OPS-NIGHT', 'Operations night', '20:00', '08:00', 60, 10, true,
                'Operations / terminal night coverage crossing midnight.'),

            // —— Security ——
            $this->row('SEC-DAY', 'Security day', '06:00', '18:00', 60, 5, false,
                'Security day post (12-hour tour).'),
            $this->row('SEC-NIGHT', 'Security night', '18:00', '06:00', 60, 5, true,
                'Security night post crossing midnight.'),

            // —— Flex / general ——
            $this->row('FLEX', 'Flexible core hours', '10:00', '19:00', 60, 15, false,
                'Flexible core window still totaling 8 work hours.'),
            $this->row('GENERAL', 'General duty', '08:00', '17:00', 60, 0, false,
                'General duty template with no grace minutes.'),
        ];
    }

    /**
     * @return array{
     *     code: string,
     *     name: string,
     *     time_in: string,
     *     time_out: string,
     *     break_minutes: int,
     *     grace_minutes: int,
     *     crosses_midnight: bool,
     *     description: string
     * }
     */
    private function row(
        string $code,
        string $name,
        string $timeIn,
        string $timeOut,
        int $breakMinutes,
        int $graceMinutes,
        bool $crossesMidnight,
        string $description,
    ): array {
        return [
            'code' => strtoupper($code),
            'name' => $name,
            'time_in' => $timeIn,
            'time_out' => $timeOut,
            'break_minutes' => $breakMinutes,
            'grace_minutes' => $graceMinutes,
            'crosses_midnight' => $crossesMidnight,
            'description' => $description,
        ];
    }
}
