<?php

namespace Database\Seeders\Holidays;

use App\Models\Holiday;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Seeds nationwide Philippine holidays for payroll / timekeeping calendars.
 *
 * Fixed dates are marked recurring; movable observances (Holy Week, Eid,
 * Chinese New Year, National Heroes Day) are expanded per year.
 */
class PhilippineHolidaySeeder extends Seeder
{
    /** Inclusive calendar years to materialize (UI filters by year). */
    private const YEARS = [2025, 2026, 2027, 2028, 2029, 2030];

    public function run(): void
    {
        foreach (self::YEARS as $year) {
            foreach ($this->holidaysForYear($year) as $holiday) {
                $attributes = [
                    'type' => $holiday['type'],
                    'is_recurring' => $holiday['is_recurring'],
                    'is_double_pay' => $holiday['is_double_pay'],
                    'paid_hours' => $holiday['paid_hours'] ?? 8,
                    'description' => $holiday['description'],
                    'is_active' => true,
                ];

                $existing = Holiday::query()
                    ->whereDate('holiday_date', $holiday['holiday_date'])
                    ->where('name', $holiday['name'])
                    ->first();

                if ($existing) {
                    $existing->fill($attributes)->save();
                    continue;
                }

                Holiday::query()->create([
                    ...$attributes,
                    'name' => $holiday['name'],
                    'holiday_date' => $holiday['holiday_date'],
                ]);
            }
        }
    }

    /**
     * @return list<array{
     *     name: string,
     *     holiday_date: string,
     *     type: string,
     *     is_recurring: bool,
     *     is_double_pay: bool,
     *     paid_hours?: int,
     *     description: string
     * }>
     */
    private function holidaysForYear(int $year): array
    {
        $easter = $this->easterSunday($year);
        $maundyThursday = $easter->subDays(3);
        $goodFriday = $easter->subDays(2);
        $blackSaturday = $easter->subDays(1);
        $nationalHeroesDay = $this->lastMondayOfAugust($year);

        $chineseNewYear = $this->chineseNewYearDates()[$year] ?? null;
        $eidFitr = $this->eidFitrDates()[$year] ?? null;
        $eidAdha = $this->eidAdhaDates()[$year] ?? null;

        $rows = [
            // —— Regular holidays (Labor Code / typical proclamation) ——
            $this->regular('New Year\'s Day', "{$year}-01-01", true, 'First day of the year.'),
            $this->regular('Maundy Thursday', $maundyThursday->toDateString(), false, 'Holy Week — Thursday before Easter.'),
            $this->regular('Good Friday', $goodFriday->toDateString(), false, 'Holy Week — Friday before Easter.'),
            $this->regular('Araw ng Kagitingan', "{$year}-04-09", true, 'Day of Valor (Bataan Day).'),
            $this->regular('Labor Day', "{$year}-05-01", true, 'International Workers\' Day.'),
            $this->regular('Independence Day', "{$year}-06-12", true, 'Philippine Independence Day.'),
            $this->regular('National Heroes Day', $nationalHeroesDay->toDateString(), false, 'Last Monday of August.'),
            $this->regular('Bonifacio Day', "{$year}-11-30", true, 'Birth anniversary of Andres Bonifacio.'),
            $this->regular('Christmas Day', "{$year}-12-25", true, 'Nativity of Jesus Christ.'),
            $this->regular('Rizal Day', "{$year}-12-30", true, 'Death anniversary of Dr. Jose Rizal.'),

            // —— Special (non-working) holidays ——
            $this->specialNonWorking('EDSA People Power Revolution Anniversary', "{$year}-02-25", true, 'EDSA People Power Revolution Anniversary.'),
            $this->specialNonWorking('Black Saturday', $blackSaturday->toDateString(), false, 'Holy Week — Saturday before Easter.'),
            $this->specialNonWorking('Ninoy Aquino Day', "{$year}-08-21", true, 'Death anniversary of Benigno “Ninoy” Aquino Jr.'),
            $this->specialNonWorking('All Saints\' Day', "{$year}-11-01", true, 'Feast of All Saints.'),
            $this->specialNonWorking('Feast of the Immaculate Conception of Mary', "{$year}-12-08", true, 'Solemnity of the Immaculate Conception.'),
            $this->specialNonWorking('Christmas Eve', "{$year}-12-24", true, 'Often proclaimed special non-working.'),
            $this->specialNonWorking('Last Day of the Year', "{$year}-12-31", true, 'New Year\'s Eve — often proclaimed special non-working.'),

            // —— Special working (commonly observed / sometimes proclaimed) ——
            $this->specialWorking('All Souls\' Day', "{$year}-11-02", true, 'Often a special working day when proclaimed.'),
        ];

        if ($chineseNewYear !== null) {
            $rows[] = $this->specialNonWorking(
                'Chinese New Year',
                $chineseNewYear,
                false,
                'Lunar New Year — date follows the lunar calendar; confirm annual proclamation.'
            );
        }

        if ($eidFitr !== null) {
            $rows[] = $this->regular(
                'Eid\'l Fitr',
                $eidFitr,
                false,
                'End of Ramadan — Islamic movable feast; confirm annual NCMF / Malacañang proclamation.'
            );
        }

        if ($eidAdha !== null) {
            $rows[] = $this->regular(
                'Eid\'l Adha',
                $eidAdha,
                false,
                'Feast of Sacrifice — Islamic movable feast; confirm annual NCMF / Malacañang proclamation.'
            );
        }

        return $rows;
    }

    /**
     * @return array{name: string, holiday_date: string, type: string, is_recurring: bool, is_double_pay: bool, paid_hours: int, description: string}
     */
    private function regular(string $name, string $date, bool $recurring, string $description): array
    {
        return [
            'name' => $name,
            'holiday_date' => $date,
            'type' => 'regular',
            'is_recurring' => $recurring,
            'is_double_pay' => true,
            'paid_hours' => 8,
            'description' => $description,
        ];
    }

    /**
     * @return array{name: string, holiday_date: string, type: string, is_recurring: bool, is_double_pay: bool, paid_hours: int, description: string}
     */
    private function specialNonWorking(string $name, string $date, bool $recurring, string $description): array
    {
        return [
            'name' => $name,
            'holiday_date' => $date,
            'type' => 'special_non_working',
            'is_recurring' => $recurring,
            'is_double_pay' => false,
            'paid_hours' => 8,
            'description' => $description,
        ];
    }

    /**
     * @return array{name: string, holiday_date: string, type: string, is_recurring: bool, is_double_pay: bool, paid_hours: int, description: string}
     */
    private function specialWorking(string $name, string $date, bool $recurring, string $description): array
    {
        return [
            'name' => $name,
            'holiday_date' => $date,
            'type' => 'special_working',
            'is_recurring' => $recurring,
            'is_double_pay' => false,
            'paid_hours' => 8,
            'description' => $description,
        ];
    }

    private function easterSunday(int $year): CarbonImmutable
    {
        // Anonymous Gregorian algorithm
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return CarbonImmutable::create($year, $month, $day)->startOfDay();
    }

    private function lastMondayOfAugust(int $year): CarbonImmutable
    {
        $endOfAugust = CarbonImmutable::create($year, 8, 31)->startOfDay();

        return $endOfAugust->isMonday()
            ? $endOfAugust
            : $endOfAugust->previous(CarbonImmutable::MONDAY);
    }

    /**
     * Known lunar New Year dates (Gregorian). Verify against annual proclamation.
     *
     * @return array<int, string>
     */
    private function chineseNewYearDates(): array
    {
        return [
            2025 => '2025-01-29',
            2026 => '2026-02-17',
            2027 => '2027-02-06',
            2028 => '2028-01-26',
            2029 => '2029-02-13',
            2030 => '2030-02-03',
        ];
    }

    /**
     * Approximate Eid'l Fitr dates used in PH calendars; confirm NCMF each year.
     *
     * @return array<int, string>
     */
    private function eidFitrDates(): array
    {
        return [
            2025 => '2025-03-31',
            2026 => '2026-03-20',
            2027 => '2027-03-10',
            2028 => '2028-02-27',
            2029 => '2029-02-15',
            2030 => '2030-02-05',
        ];
    }

    /**
     * Approximate Eid'l Adha dates used in PH calendars; confirm NCMF each year.
     *
     * @return array<int, string>
     */
    private function eidAdhaDates(): array
    {
        return [
            2025 => '2025-06-07',
            2026 => '2026-05-27',
            2027 => '2027-05-16',
            2028 => '2028-05-05',
            2029 => '2029-04-24',
            2030 => '2030-04-13',
        ];
    }
}
