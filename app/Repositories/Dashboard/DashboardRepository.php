<?php

namespace App\Repositories\Dashboard;

use App\Models\AuthActivityLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

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

    /**
     * @return list<array{label: string, value: int, department_uuid: string|null}>
     */
    public function departmentHeadcounts(): array
    {
        $rows = Department::query()
            ->where('is_active', true)
            ->withCount([
                'employees as headcount' => static function ($query): void {
                    $query->whereIn('employment_status', ['active', 'on_leave']);
                },
            ])
            ->orderByDesc('headcount')
            ->orderBy('name')
            ->get(['id', 'uuid', 'name']);

        $items = $rows->map(static fn (Department $department): array => [
            'label' => $department->name,
            'value' => (int) $department->headcount,
            'department_uuid' => $department->uuid,
        ])->values()->all();

        $unassigned = Employee::query()
            ->whereIn('employment_status', ['active', 'on_leave'])
            ->whereNull('department_id')
            ->count();

        if ($unassigned > 0) {
            $items[] = [
                'label' => 'Unassigned',
                'value' => $unassigned,
                'department_uuid' => null,
            ];
        }

        return $items;
    }

    public function hiresInMonth(Carbon $month): int
    {
        return Employee::query()
            ->whereNotNull('date_hired')
            ->whereYear('date_hired', $month->year)
            ->whereMonth('date_hired', $month->month)
            ->count();
    }

    public function separationsInMonth(Carbon $month): int
    {
        return Employee::query()
            ->whereNotNull('date_separated')
            ->whereYear('date_separated', $month->year)
            ->whereMonth('date_separated', $month->month)
            ->count();
    }

    /**
     * Monthly hires / separations for the last N months (oldest → newest).
     *
     * @return list<array{label: string, key: string, hires: int, separations: int, net: int}>
     */
    public function headcountMovement(int $months = 12): array
    {
        $end = now()->startOfMonth();
        $start = $end->copy()->subMonths($months - 1);
        $period = CarbonPeriod::create($start, '1 month', $end);

        $rangeEnd = $end->copy()->endOfMonth()->toDateString();
        $rangeStart = $start->toDateString();

        $hires = Employee::query()
            ->whereNotNull('date_hired')
            ->whereBetween('date_hired', [$rangeStart, $rangeEnd])
            ->pluck('date_hired')
            ->map(static fn ($date): string => Carbon::parse($date)->format('Y-m'))
            ->countBy();

        $separations = Employee::query()
            ->whereNotNull('date_separated')
            ->whereBetween('date_separated', [$rangeStart, $rangeEnd])
            ->pluck('date_separated')
            ->map(static fn ($date): string => Carbon::parse($date)->format('Y-m'))
            ->countBy();

        $series = [];
        foreach ($period as $month) {
            /** @var Carbon $month */
            $key = $month->format('Y-m');
            $hireCount = (int) ($hires[$key] ?? 0);
            $sepCount = (int) ($separations[$key] ?? 0);
            $series[] = [
                'label' => $month->format('M Y'),
                'key' => $key,
                'hires' => $hireCount,
                'separations' => $sepCount,
                'net' => $hireCount - $sepCount,
            ];
        }

        return $series;
    }

    /**
     * Active roster missing common 201 fields HR expects to fill.
     */
    public function incompleteProfilesCount(): int
    {
        return $this->incompleteProfilesQuery()->count();
    }

    /**
     * @return list<array{uuid: string, employee_number: string, name: string, missing: list<string>}>
     */
    public function incompleteProfiles(int $limit = 8): array
    {
        return $this->incompleteProfilesQuery()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit($limit)
            ->get()
            ->map(function (Employee $employee): array {
                $missing = [];
                if ($employee->department_id === null) {
                    $missing[] = 'department';
                }
                if ($employee->date_hired === null) {
                    $missing[] = 'date_hired';
                }
                if ($employee->birth_date === null) {
                    $missing[] = 'birth_date';
                }
                if (blank($employee->mobile)) {
                    $missing[] = 'mobile';
                }
                if (blank($employee->photo_path)) {
                    $missing[] = 'photo';
                }

                return [
                    'uuid' => $employee->uuid,
                    'employee_number' => $employee->employee_number,
                    'name' => trim($employee->first_name.' '.$employee->last_name),
                    'missing' => $missing,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{uuid: string, employee_number: string, name: string, department: string|null, position_title: string|null}>
     */
    public function onLeaveEmployees(int $limit = 10): array
    {
        return Employee::query()
            ->with('department:id,name')
            ->where('employment_status', 'on_leave')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit($limit)
            ->get()
            ->map(static fn (Employee $employee): array => [
                'uuid' => $employee->uuid,
                'employee_number' => $employee->employee_number,
                'name' => trim($employee->first_name.' '.$employee->last_name),
                'department' => $employee->department?->name,
                'position_title' => $employee->position_title,
            ])
            ->values()
            ->all();
    }

    public function expiredDocumentsCount(): int
    {
        return EmployeeDocument::query()
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<', now()->toDateString())
            ->count();
    }

    public function expiringDocumentsCount(int $withinDays = 30): int
    {
        return EmployeeDocument::query()
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '>=', now()->toDateString())
            ->whereDate('expires_at', '<=', now()->addDays($withinDays)->toDateString())
            ->count();
    }

    /**
     * @return list<array{event: string, message: string, actor: string|null, created_at: string, meta: array<string, mixed>|null}>
     */
    public function recentActivity(int $limit = 12): array
    {
        $events = [
            'employee.created',
            'employee.updated',
            'employee.deactivated',
            'employee.deleted',
            'employee.exported',
            'employee.photo_updated',
            'employee.dependent.created',
            'employee.education.created',
            'employee.employment_history.created',
            'employee.career_history.created',
            'document.created',
            'document.updated',
            'document.deleted',
            'department.created',
            'department.updated',
            'department.deleted',
            'user.created',
            'user.updated',
            'holiday.created',
            'shift.created',
        ];

        return AuthActivityLog::query()
            ->whereIn('event', $events)
            ->latest('id')
            ->limit($limit)
            ->get(['event', 'email', 'meta', 'created_at'])
            ->map(function (AuthActivityLog $log): array {
                return [
                    'event' => $log->event,
                    'message' => $this->activityMessage($log->event, is_array($log->meta) ? $log->meta : []),
                    'actor' => $log->email,
                    'created_at' => optional($log->created_at)?->toIso8601String(),
                    'meta' => is_array($log->meta) ? $log->meta : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function activityMessage(string $event, array $meta): string
    {
        $name = (string) ($meta['name'] ?? $meta['title'] ?? $meta['employee_number'] ?? $meta['code'] ?? '');

        return match ($event) {
            'employee.created' => $name !== '' ? "Employee {$name} created" : 'Employee created',
            'employee.updated' => $name !== '' ? "Employee {$name} updated" : 'Employee updated',
            'employee.deactivated' => $name !== '' ? "Employee {$name} deactivated" : 'Employee deactivated',
            'employee.deleted' => $name !== '' ? "Employee {$name} deleted" : 'Employee deleted',
            'employee.exported' => 'Employee list exported',
            'employee.photo_updated' => 'Employee photo updated',
            'employee.dependent.created' => 'Dependent added to employee 201',
            'employee.education.created' => 'Education record added',
            'employee.employment_history.created' => 'Employment history added',
            'employee.career_history.created' => 'Career history added',
            'document.created' => $name !== '' ? "Document {$name} uploaded" : 'Document uploaded',
            'document.updated' => $name !== '' ? "Document {$name} updated" : 'Document updated',
            'document.deleted' => $name !== '' ? "Document {$name} deleted" : 'Document deleted',
            'department.created' => $name !== '' ? "Department {$name} created" : 'Department created',
            'department.updated' => $name !== '' ? "Department {$name} updated" : 'Department updated',
            'department.deleted' => $name !== '' ? "Department {$name} deleted" : 'Department deleted',
            'user.created' => 'User account created',
            'user.updated' => 'User account updated',
            'holiday.created' => 'Holiday created',
            'shift.created' => 'Shift template created',
            default => str_replace('.', ' ', $event),
        };
    }

    private function incompleteProfilesQuery()
    {
        return Employee::query()
            ->whereIn('employment_status', ['active', 'on_leave'])
            ->where(function ($query): void {
                $query->whereNull('department_id')
                    ->orWhereNull('date_hired')
                    ->orWhereNull('birth_date')
                    ->orWhereNull('mobile')
                    ->orWhere('mobile', '')
                    ->orWhereNull('photo_path')
                    ->orWhere('photo_path', '');
            });
    }
}
