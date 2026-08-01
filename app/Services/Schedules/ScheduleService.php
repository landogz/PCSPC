<?php

namespace App\Services\Schedules;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\ShiftSchedule;
use App\Repositories\Schedules\ScheduleRepository;
use App\Repositories\Shifts\ShiftRepository;
use App\Services\Audit\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class ScheduleService
{
    public function __construct(
        private readonly ScheduleRepository $schedules,
        private readonly ShiftRepository $shifts,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->schedules->paginate($filters, $perPage);
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
