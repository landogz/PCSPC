<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AuthSeeder::class,
            Lookups\LookupSeeder::class,
            Holidays\PhilippineHolidaySeeder::class,
            Shifts\PhilippineShiftSeeder::class,
            Leave\LeaveTypeSeeder::class,
            Workflow\WorkflowDefinitionSeeder::class,
        ]);
    }
}
