<?php

namespace App\Services\Schedules;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\ShiftSchedule;
use App\Http\Resources\Schedules\ScheduleResource;
use App\Repositories\Schedules\ScheduleRepository;
use App\Repositories\Shifts\ShiftRepository;
use App\Services\Administration\SystemParameterService;
use App\Services\Audit\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ScheduleService
{
    public function __construct(
        private readonly ScheduleRepository $schedules,
        private readonly ShiftRepository $shifts,
        private readonly SystemParameterService $systemParameters,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->schedules->paginate($filters, $perPage);
    }

    /**
     * Landscape printable schedule report grouped per employee or per department.
     *
     * @param  array{scope: string, employee_id?: string|null, department_id?: string|null, effective?: string|null, include_related?: bool}  $options
     * @return array<string, mixed>
     */
    public function printReport(array $options): array
    {
        $scope = (string) ($options['scope'] ?? 'employee');
        $effective = trim((string) ($options['effective'] ?? 'current'));
        if ($effective === 'all') {
            $effective = '';
        }
        $includeRelated = array_key_exists('include_related', $options)
            ? (bool) $options['include_related']
            : true;

        $params = $this->systemParameters->current();
        $base = [
            'scope' => $scope,
            'orientation' => 'landscape',
            'title' => $scope === 'department' ? 'Department Shift Schedules' : 'Employee Shift Schedules',
            'generated_at' => now()->toIso8601String(),
            'generated_on' => now()->timezone((string) ($params['timezone'] ?? 'Asia/Manila'))->format('Y-m-d H:i'),
            'company' => [
                'name' => (string) ($params['company_name'] ?? config('app.name')),
                'short_name' => (string) ($params['company_short_name'] ?? ''),
                'logo_url' => (string) ($params['logo_url'] ?? ''),
            ],
            'filters' => [
                'effective' => $effective === '' ? 'all' : $effective,
                'employee_id' => $options['employee_id'] ?? null,
                'department_id' => $options['department_id'] ?? null,
                'include_related' => $includeRelated,
            ],
            'groups' => [],
            'totals' => [
                'groups' => 0,
                'rows' => 0,
            ],
        ];

        if ($scope === 'department') {
            $base['groups'] = $this->buildDepartmentPrintGroups(
                trim((string) ($options['department_id'] ?? '')),
                $effective,
                $includeRelated,
            );
        } else {
            $base['groups'] = $this->buildEmployeePrintGroups(
                trim((string) ($options['employee_id'] ?? '')),
                $effective,
                $includeRelated,
            );
        }

        $base['totals']['groups'] = count($base['groups']);
        $base['totals']['rows'] = array_sum(array_map(
            static fn (array $group): int => count($group['rows'] ?? []) + array_sum(array_map(
                static fn (array $section): int => count($section['rows'] ?? []),
                $group['sections'] ?? [],
            )),
            $base['groups'],
        ));

        return $base;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildEmployeePrintGroups(string $employeeUuid, string $effective, bool $includeRelated): array
    {
        $filters = [
            'assignee_type' => ShiftSchedule::ASSIGNEE_EMPLOYEE,
            'effective' => $effective,
            'status' => $effective === 'current' ? 'active' : '',
        ];
        if ($employeeUuid !== '') {
            $filters['employee_id'] = $employeeUuid;
        }

        $schedules = $this->schedules->listForPrint($filters);
        if ($employeeUuid !== '' && $schedules->isEmpty()) {
            $employee = Employee::query()
                ->with('department:id,uuid,code,name')
                ->where('uuid', $employeeUuid)
                ->first();
            if ($employee === null) {
                abort(404, 'Employee not found.');
            }

            return [[
                'key' => $employee->uuid,
                'type' => 'employee',
                'heading' => trim($employee->first_name.' '.$employee->last_name),
                'subheading' => trim(($employee->employee_number ?? '').' · '.($employee->department?->name ?? 'No department'), ' ·'),
                'meta' => [
                    'employee_number' => $employee->employee_number,
                    'department' => $employee->department?->name,
                ],
                'rows' => [],
                'sections' => $includeRelated && $employee->department
                    ? [$this->departmentRelatedSection($employee->department->uuid, $effective)]
                    : [],
            ]];
        }

        $grouped = $schedules->groupBy(static fn (ShiftSchedule $row): string => (string) $row->employee?->uuid);
        $groups = [];

        foreach ($grouped as $uuid => $rows) {
            /** @var ShiftSchedule $first */
            $first = $rows->first();
            $employee = $first->employee;
            if ($employee === null) {
                continue;
            }

            $sections = [];
            if ($includeRelated && $employee->department_id) {
                $deptUuid = $employee->department?->uuid;
                if ($deptUuid) {
                    $related = $this->departmentRelatedSection($deptUuid, $effective);
                    if ($related['rows'] !== []) {
                        $sections[] = $related;
                    }
                }
            }

            $groups[] = [
                'key' => $employee->uuid,
                'type' => 'employee',
                'heading' => trim($employee->first_name.' '.$employee->last_name),
                'subheading' => trim(($employee->employee_number ?? '').' · '.($employee->department?->name ?? 'No department'), ' ·'),
                'meta' => [
                    'employee_number' => $employee->employee_number,
                    'department' => $employee->department?->name,
                ],
                'rows' => $this->mapScheduleRows($rows),
                'sections' => $sections,
            ];
        }

        usort($groups, static fn (array $a, array $b): int => strcasecmp($a['heading'], $b['heading']));

        return $groups;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildDepartmentPrintGroups(string $departmentUuid, string $effective, bool $includeRelated): array
    {
        $filters = [
            'assignee_type' => ShiftSchedule::ASSIGNEE_DEPARTMENT,
            'effective' => $effective,
            'status' => $effective === 'current' ? 'active' : '',
        ];
        if ($departmentUuid !== '') {
            $filters['department_id'] = $departmentUuid;
        }

        $deptSchedules = $this->schedules->listForPrint($filters);
        /** @var array<int, Department> $departmentIds */
        $departmentIds = [];

        if ($departmentUuid !== '') {
            $department = Department::query()->where('uuid', $departmentUuid)->first();
            if ($department === null) {
                abort(404, 'Department not found.');
            }
            $departmentIds[$department->id] = $department;
        } else {
            foreach ($deptSchedules as $schedule) {
                if ($schedule->department !== null) {
                    $departmentIds[$schedule->department->id] = $schedule->department;
                }
            }
        }

        $allEmployeeSchedules = collect();
        if ($includeRelated) {
            $allEmployeeSchedules = $this->schedules->listForPrint([
                'assignee_type' => ShiftSchedule::ASSIGNEE_EMPLOYEE,
                'effective' => $effective,
                'status' => $effective === 'current' ? 'active' : '',
            ]);

            if ($departmentUuid === '') {
                foreach ($allEmployeeSchedules as $schedule) {
                    $dept = $schedule->employee?->department;
                    if ($dept !== null) {
                        $departmentIds[$dept->id] = $dept;
                    }
                }
            }
        }

        $groups = [];
        foreach ($departmentIds as $department) {
            $rows = $deptSchedules
                ->filter(static fn (ShiftSchedule $row): bool => (int) $row->department_id === (int) $department->id)
                ->values();

            $sections = [];
            if ($includeRelated) {
                $employeeRows = $allEmployeeSchedules->filter(
                    static fn (ShiftSchedule $row): bool => (int) ($row->employee?->department_id) === (int) $department->id
                );

                $byEmployee = $employeeRows->groupBy(static fn (ShiftSchedule $row): string => (string) $row->employee?->uuid);
                foreach ($byEmployee as $empRows) {
                    /** @var ShiftSchedule $first */
                    $first = $empRows->first();
                    $employee = $first->employee;
                    if ($employee === null) {
                        continue;
                    }
                    $sections[] = [
                        'key' => $employee->uuid,
                        'type' => 'employee_override',
                        'heading' => trim($employee->first_name.' '.$employee->last_name),
                        'subheading' => 'Employee override · '.($employee->employee_number ?? ''),
                        'rows' => $this->mapScheduleRows($empRows),
                    ];
                }
                usort($sections, static fn (array $a, array $b): int => strcasecmp($a['heading'], $b['heading']));
            }

            if ($rows->isEmpty() && $sections === []) {
                continue;
            }

            $groups[] = [
                'key' => $department->uuid,
                'type' => 'department',
                'heading' => $department->name,
                'subheading' => 'Department · '.$department->code,
                'meta' => [
                    'code' => $department->code,
                ],
                'rows' => $this->mapScheduleRows($rows),
                'sections' => $sections,
            ];
        }

        usort($groups, static fn (array $a, array $b): int => strcasecmp($a['heading'], $b['heading']));

        return $groups;
    }

    /**
     * @return array{key: string, type: string, heading: string, subheading: string, rows: list<array<string, mixed>>}
     */
    private function departmentRelatedSection(string $departmentUuid, string $effective): array
    {
        $department = Department::query()->where('uuid', $departmentUuid)->first();
        $rows = $this->schedules->listForPrint([
            'assignee_type' => ShiftSchedule::ASSIGNEE_DEPARTMENT,
            'department_id' => $departmentUuid,
            'effective' => $effective,
            'status' => $effective === 'current' ? 'active' : '',
        ]);

        return [
            'key' => $departmentUuid,
            'type' => 'department_default',
            'heading' => $department?->name ?? 'Department schedule',
            'subheading' => 'Department default (applies unless employee override exists)',
            'rows' => $this->mapScheduleRows($rows),
        ];
    }

    /**
     * @param  Collection<int, ShiftSchedule>  $rows
     * @return list<array<string, mixed>>
     */
    private function mapScheduleRows(Collection $rows): array
    {
        return $rows->map(
            static fn (ShiftSchedule $schedule): array => (new ScheduleResource($schedule))->resolve()
        )->values()->all();
    }

    public function find(string $uuid): ShiftSchedule
    {
        $schedule = $this->schedules->findByUuid($uuid);
        if ($schedule === null) {
            abort(404, 'Schedule not found.');
        }

        return $schedule;
    }

    /**
     * @return list<array{id: string, code: string, name: string, label: string}>
     */
    public function shiftOptions(): array
    {
        return Shift::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['uuid', 'code', 'name'])
            ->map(static fn (Shift $shift): array => [
                'id' => $shift->uuid,
                'code' => $shift->code,
                'name' => $shift->name,
                'label' => $shift->code.' — '.$shift->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: string, code: string, name: string, label: string}>
     */
    public function departmentOptions(): array
    {
        return Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['uuid', 'code', 'name'])
            ->map(static fn (Department $department): array => [
                'id' => $department->uuid,
                'code' => $department->code,
                'name' => $department->name,
                'label' => $department->code.' — '.$department->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload, ?int $creatorId = null): ShiftSchedule
    {
        $data = $this->mapPayload($payload);
        $data['created_by'] = $creatorId;

        $schedule = $this->schedules->create($data);
        $schedule = $this->find($schedule->uuid);

        $this->audit->log('schedule.created', [
            'schedule_id' => $schedule->uuid,
            'shift_id' => $schedule->shift?->uuid,
            'assignee_type' => $schedule->assignee_type,
            'employee_id' => $schedule->employee?->uuid,
            'department_id' => $schedule->department?->uuid,
            'effective_from' => $schedule->effective_from?->toDateString(),
            'effective_to' => $schedule->effective_to?->toDateString(),
        ]);

        return $schedule;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(string $uuid, array $payload): ShiftSchedule
    {
        $schedule = $this->find($uuid);
        $updated = $this->schedules->update($schedule, $this->mapPayload($payload, $schedule));

        $this->audit->log('schedule.updated', [
            'schedule_id' => $updated->uuid,
            'shift_id' => $updated->shift?->uuid,
            'assignee_type' => $updated->assignee_type,
            'employee_id' => $updated->employee?->uuid,
            'department_id' => $updated->department?->uuid,
            'is_active' => (bool) $updated->is_active,
        ]);

        return $this->find($updated->uuid);
    }

    public function delete(string $uuid): void
    {
        $schedule = $this->find($uuid);
        $meta = [
            'schedule_id' => $schedule->uuid,
            'shift_id' => $schedule->shift?->uuid,
            'assignee_type' => $schedule->assignee_type,
            'employee_id' => $schedule->employee?->uuid,
            'department_id' => $schedule->department?->uuid,
        ];

        $this->schedules->delete($schedule);
        $this->audit->log('schedule.deleted', $meta);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function mapPayload(array $payload, ?ShiftSchedule $existing = null): array
    {
        $assigneeType = (string) ($payload['assignee_type'] ?? $existing?->assignee_type ?? ShiftSchedule::ASSIGNEE_EMPLOYEE);
        if (! in_array($assigneeType, ShiftSchedule::ASSIGNEE_TYPES, true)) {
            throw ValidationException::withMessages([
                'assignee_type' => ['Please choose employee or department assignment.'],
            ]);
        }

        $shiftUuid = (string) ($payload['shift_id'] ?? $existing?->shift?->uuid ?? '');
        $shift = $this->shifts->findByUuid($shiftUuid);
        if ($shift === null) {
            throw ValidationException::withMessages([
                'shift_id' => ['Please choose a valid shift template.'],
            ]);
        }

        $employeeId = null;
        $departmentId = null;

        if ($assigneeType === ShiftSchedule::ASSIGNEE_EMPLOYEE) {
            $employeeUuid = (string) ($payload['employee_id'] ?? $existing?->employee?->uuid ?? '');
            $employee = Employee::query()->where('uuid', $employeeUuid)->first();
            if ($employee === null) {
                throw ValidationException::withMessages([
                    'employee_id' => ['Please select an employee.'],
                ]);
            }
            $employeeId = $employee->id;
        } else {
            $departmentUuid = (string) ($payload['department_id'] ?? $existing?->department?->uuid ?? '');
            $department = Department::query()->where('uuid', $departmentUuid)->first();
            if ($department === null) {
                throw ValidationException::withMessages([
                    'department_id' => ['Please select a department.'],
                ]);
            }
            $departmentId = $department->id;
        }

        $from = (string) ($payload['effective_from'] ?? $existing?->effective_from?->toDateString() ?? '');
        $to = array_key_exists('effective_to', $payload)
            ? ($payload['effective_to'] ?: null)
            : $existing?->effective_to?->toDateString();

        if ($to !== null && $from !== '' && $to < $from) {
            throw ValidationException::withMessages([
                'effective_to' => ['End date must be on or after the start date.'],
            ]);
        }

        $days = $payload['days_of_week'] ?? $existing?->days_of_week;
        if (is_array($days)) {
            $days = array_values(array_unique(array_map('intval', $days)));
            $days = array_values(array_filter($days, static fn (int $day): bool => $day >= 1 && $day <= 7));
            sort($days);
        } else {
            $days = null;
        }

        return [
            'shift_id' => $shift->id,
            'assignee_type' => $assigneeType,
            'employee_id' => $employeeId,
            'department_id' => $departmentId,
            'effective_from' => $from,
            'effective_to' => $to,
            'days_of_week' => $days === [] ? null : $days,
            'notes' => $this->nullableString($payload['notes'] ?? ($existing?->notes)),
            'is_active' => array_key_exists('is_active', $payload)
                ? (bool) $payload['is_active']
                : ($existing?->is_active ?? true),
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
