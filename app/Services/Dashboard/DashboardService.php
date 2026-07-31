<?php

namespace App\Services\Dashboard;

use App\Repositories\Dashboard\DashboardRepository;

class DashboardService
{
    public function __construct(
        private readonly DashboardRepository $dashboard,
    ) {}

    /**
     * @return array{
     *     employees: array{value: int, delta_percent: float|null},
     *     on_leave: array{value: int, delta_percent: float|null, share_percent: float|null},
     *     departments: array{value: int, delta_percent: float|null},
     *     attendance: array{value: int|null, available: bool},
     *     summary: array{
     *         check_ins: array{value: string|null, available: bool},
     *         on_leave: int,
     *         late_arrivals: array{value: int|null, available: bool}
     *     }
     * }
     */
    public function stats(): array
    {
        $employees = $this->dashboard->employeesHeadcount();
        $onLeave = $this->dashboard->employeesOnLeave();
        $departments = $this->dashboard->departmentsActive();

        $share = $employees > 0
            ? round(($onLeave / $employees) * 100, 1)
            : null;

        return [
            'employees' => [
                'value' => $employees,
                // Month-over-month hire deltas are misleading next to headcount; hide until history exists.
                'delta_percent' => null,
            ],
            'on_leave' => [
                'value' => $onLeave,
                'delta_percent' => null,
                'share_percent' => $share,
            ],
            'departments' => [
                'value' => $departments,
                'delta_percent' => null,
            ],
            'attendance' => [
                'value' => null,
                'available' => false,
            ],
            'summary' => [
                'check_ins' => [
                    'value' => null,
                    'available' => false,
                ],
                'on_leave' => $onLeave,
                'late_arrivals' => [
                    'value' => null,
                    'available' => false,
                ],
            ],
        ];
    }
}
