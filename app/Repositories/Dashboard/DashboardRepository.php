<?php

namespace App\Repositories\Dashboard;

use App\Models\Department;
use App\Models\Employee;

class DashboardRepository
{
    public function employeesHeadcount(): int
    {
        return Employee::query()
            ->whereIn('employment_status', ['active', 'on_leave'])
            ->count();
    }

    public function employeesOnLeave(): int
    {
        return Employee::query()
            ->where('employment_status', 'on_leave')
            ->count();
    }

    public function departmentsActive(): int
    {
        return Department::query()
            ->where('is_active', true)
            ->count();
    }
}
