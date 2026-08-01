<?php

namespace App\Services\Dashboard;

use App\Repositories\Dashboard\DashboardRepository;
use Carbon\Carbon;

class DashboardService
{
    public function __construct(
        private readonly DashboardRepository $dashboard,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function stats(): array
    {
        $employees = $this->dashboard->employeesHeadcount();
        $onLeave = $this->dashboard->employeesOnLeave();
        $departments = $this->dashboard->departmentsActive();
        $thisMonth = now()->startOfMonth();
        $hiresThisMonth = $this->dashboard->hiresInMonth($thisMonth);
        $separationsThisMonth = $this->dashboard->separationsInMonth($thisMonth);
        $departmentHeadcounts = $this->dashboard->departmentHeadcounts();
        $movement = $this->dashboard->headcountMovement(12);
        $incompleteCount = $this->dashboard->incompleteProfilesCount();
        $expiringDocuments = $this->dashboard->expiringDocumentsCount(30);
        $onLeaveEmployees = $this->dashboard->onLeaveEmployees();

        $share = $employees > 0
            ? round(($onLeave / $employees) * 100, 1)
            : null;

        $pendingItems = $this->pendingItems($incompleteCount, $expiringDocuments);
        $actionable = collect($pendingItems)
            ->filter(static fn (array $item): bool => ($item['available'] ?? false) && ($item['count'] ?? 0) > 0)
            ->sum(static fn (array $item): int => (int) $item['count']);

        return [
            'employees' => [
                'value' => $employees,
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
                'phase' => 'P5',
            ],
            'summary' => [
                'check_ins' => [
                    'value' => null,
                    'available' => false,
                    'phase' => 'P5',
                ],
                'on_leave' => $onLeave,
                'late_arrivals' => [
                    'value' => null,
                    'available' => false,
                    'phase' => 'P5',
                ],
            ],
            'headcount_movement' => [
                'hires_this_month' => $hiresThisMonth,
                'separations_this_month' => $separationsThisMonth,
                'net_this_month' => $hiresThisMonth - $separationsThisMonth,
            ],
            'charts' => [
                'attendance_trend' => [
                    'available' => false,
                    'phase' => 'P5',
                    'labels' => [],
                    'on_time' => [],
                    'late' => [],
                ],
                'attendance_today' => [
                    'available' => false,
                    'phase' => 'P5',
                    'labels' => ['Present', 'Late', 'Absent', 'On Leave'],
                    'values' => [0, 0, 0, $onLeave],
                ],
                'leave_by_month' => [
                    'available' => false,
                    'phase' => 'P4',
                    'labels' => $this->lastMonthLabels(6),
                    'values' => array_fill(0, 6, 0),
                ],
                'department_headcount' => [
                    'available' => true,
                    'labels' => array_column($departmentHeadcounts, 'label'),
                    'values' => array_column($departmentHeadcounts, 'value'),
                    'items' => $departmentHeadcounts,
                ],
                'headcount_trend' => [
                    'available' => true,
                    'labels' => array_column($movement, 'label'),
                    'hires' => array_column($movement, 'hires'),
                    'separations' => array_column($movement, 'separations'),
                    'net' => array_column($movement, 'net'),
                ],
            ],
            'pending' => [
                'total_actionable' => (int) $actionable,
                'items' => $pendingItems,
                'incomplete_profiles' => [
                    'count' => $incompleteCount,
                    'available' => true,
                    'items' => $this->dashboard->incompleteProfiles(),
                    'href' => '/modules/employees',
                ],
            ],
            'on_leave_now' => [
                'available' => true,
                'count' => $onLeave,
                'items' => $onLeaveEmployees,
                'href' => '/modules/employees?status=on_leave',
                'note' => 'Based on employee employment status until Leave Management (P4) is live.',
            ],
            'upcoming_leave' => [
                'available' => false,
                'phase' => 'P4',
                'items' => [],
                'href' => '/modules/leave',
            ],
            'payroll' => [
                'available' => false,
                'phase' => 'P7',
                'next_run_date' => null,
                'active_loans' => null,
                'deductions_this_cycle' => null,
                'href_earnings' => '/modules/earnings',
                'href_deductions' => '/modules/deductions',
                'href_loans' => '/modules/loans',
            ],
            'training' => [
                'available' => false,
                'phase' => 'P6',
                'sessions_this_month' => null,
                'href' => '/modules/training',
            ],
            'performance' => [
                'available' => false,
                'phase' => 'P6',
                'completed' => null,
                'total' => null,
                'href' => '/modules/performance',
            ],
            'activity' => [
                'items' => $this->dashboard->recentActivity(),
            ],
            'generated_at' => Carbon::now()->toIso8601String(),
        ];
    }

    /**
     * @return list<array{key: string, label: string, count: int, available: bool, phase: string|null, href: string, description: string}>
     */
    private function pendingItems(int $incompleteCount, int $expiringDocuments): array
    {
        return [
            [
                'key' => 'leave_approvals',
                'label' => 'Pending leave approvals',
                'count' => 0,
                'available' => false,
                'phase' => 'P4',
                'href' => '/modules/workflow',
                'description' => 'Leave approval queue arrives with Leave Management.',
            ],
            [
                'key' => 'workflow_approvals',
                'label' => 'Pending workflow approvals',
                'count' => 0,
                'available' => false,
                'phase' => 'P4',
                'href' => '/modules/workflow',
                'description' => 'Multi-level workflow engine is planned for Phase 4.',
            ],
            [
                'key' => 'incomplete_profiles',
                'label' => 'Incomplete employee profiles',
                'count' => $incompleteCount,
                'available' => true,
                'phase' => null,
                'href' => '/modules/employees',
                'description' => 'Missing department, hire date, birth date, mobile, or photo.',
            ],
            [
                'key' => 'expiring_documents',
                'label' => 'Documents expiring soon',
                'count' => $expiringDocuments,
                'available' => true,
                'phase' => null,
                'href' => '/modules/documents?expiry=expiring',
                'description' => 'Contracts, IDs, and certificates expiring within 30 days.',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function lastMonthLabels(int $months): array
    {
        $labels = [];
        $cursor = now()->startOfMonth()->subMonths($months - 1);
        for ($i = 0; $i < $months; $i++) {
            $labels[] = $cursor->copy()->addMonths($i)->format('M Y');
        }

        return $labels;
    }
}
