<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/** Convenience alias: php artisan db:seed --class=PhilippineShiftSeeder */
class PhilippineShiftSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(Shifts\PhilippineShiftSeeder::class);
    }
}
