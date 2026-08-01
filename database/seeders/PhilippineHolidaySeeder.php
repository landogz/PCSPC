<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/** Convenience alias: php artisan db:seed --class=PhilippineHolidaySeeder */
class PhilippineHolidaySeeder extends Seeder
{
    public function run(): void
    {
        $this->call(Holidays\PhilippineHolidaySeeder::class);
    }
}
