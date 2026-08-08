<?php

namespace Database\Seeders\Leave;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('leave.seed_types', []) as $row) {
            LeaveType::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'is_accruing' => (bool) $row['is_accruing'],
                    'requires_reason' => (bool) $row['requires_reason'],
                    'requires_hr' => (bool) $row['requires_hr'],
                    'is_active' => (bool) $row['is_active'],
                    'sort_order' => (int) $row['sort_order'],
                ]
            );
        }
    }
}
