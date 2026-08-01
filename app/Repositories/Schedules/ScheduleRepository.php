<?php

namespace App\Repositories\Schedules;

use App\Models\ShiftSchedule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ScheduleRepository
{
    /**
     * @param  array{
     *   search?: string,
     *   status?: string,
     *   shift_id?: string,
     *   assignee_type?: string,
     *   employee_id?: string,
     *   department_id?: string,
     *   effective?: string
     * }  $filters
     */
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = ShiftSchedule::query()
            ->with([
                'shift:id,uuid,code,name,time_in,time_out,break_minutes,grace_minutes,crosses_midnight,is_active',
                'employee:id,uuid,employee_number,first_name,last_name,email,department_id',
                'department:id,uuid,code,name',
                'creator:id,name,email',
            ])
            ->orderByDesc('effective_from')
            ->orderByDesc('id');

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('notes', 'like', "%{$search}%")
                    ->orWhereHas('shift', function (Builder $shift) use ($search): void {
                        $shift->where('code', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('employee', function (Builder $employee) use ($search): void {
                        $employee->where('employee_number', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('department', function (Builder $department) use ($search): void {
                        $department->where('code', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
            });
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $shiftUuid = trim((string) ($filters['shift_id'] ?? ''));
        if ($shiftUuid !== '') {
            $query->whereHas('shift', static fn (Builder $shift) => $shift->where('uuid', $shiftUuid));
        }

        $assigneeType = trim((string) ($filters['assignee_type'] ?? ''));
        if ($assigneeType !== '') {
            $query->where('assignee_type', $assigneeType);
        }

        $employeeUuid = trim((string) ($filters['employee_id'] ?? ''));
        if ($employeeUuid !== '') {
            $query->whereHas('employee', static fn (Builder $employee) => $employee->where('uuid', $employeeUuid));
        }

        $departmentUuid = trim((string) ($filters['department_id'] ?? ''));
        if ($departmentUuid !== '') {
            $query->whereHas('department', static fn (Builder $department) => $department->where('uuid', $departmentUuid));
        }

        $effective = trim((string) ($filters['effective'] ?? ''));
        if ($effective === 'current') {
            $today = now()->toDateString();
            $query->where('is_active', true)
                ->whereDate('effective_from', '<=', $today)
                ->where(function (Builder $inner) use ($today): void {
                    $inner->whereNull('effective_to')
                        ->orWhereDate('effective_to', '>=', $today);
                });
        } elseif ($effective === 'upcoming') {
            $query->whereDate('effective_from', '>', now()->toDateString());
        } elseif ($effective === 'ended') {
            $query->whereNotNull('effective_to')
                ->whereDate('effective_to', '<', now()->toDateString());
        }

        return $query->paginate($perPage);
    }

    public function findByUuid(string $uuid): ?ShiftSchedule
    {
        return ShiftSchedule::query()
            ->with([
                'shift:id,uuid,code,name,time_in,time_out,break_minutes,grace_minutes,crosses_midnight,is_active',
                'employee:id,uuid,employee_number,first_name,last_name,email,department_id',
                'department:id,uuid,code,name',
                'creator:id,name,email',
            ])
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ShiftSchedule
    {
        return ShiftSchedule::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ShiftSchedule $schedule, array $data): ShiftSchedule
    {
        $schedule->fill($data);
        $schedule->save();

        return $schedule->fresh([
            'shift:id,uuid,code,name,time_in,time_out,break_minutes,grace_minutes,crosses_midnight,is_active',
            'employee:id,uuid,employee_number,first_name,last_name,email,department_id',
            'department:id,uuid,code,name',
            'creator:id,name,email',
        ]) ?? $schedule;
    }

    public function delete(ShiftSchedule $schedule): void
    {
        $schedule->delete();
    }
}
