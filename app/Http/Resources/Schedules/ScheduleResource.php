<?php

namespace App\Http\Resources\Schedules;

use App\Models\ShiftSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ShiftSchedule
 */
class ScheduleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $shift = $this->shift;
        $employee = $this->employee;
        $department = $this->department;
        $today = now()->startOfDay();
        $from = $this->effective_from;
        $to = $this->effective_to;

        $period = 'current';
        if ($from !== null && $today->lt($from->copy()->startOfDay())) {
            $period = 'upcoming';
        } elseif ($to !== null && $today->gt($to->copy()->startOfDay())) {
            $period = 'ended';
        }

        return [
            'id' => $this->uuid,
            'assignee_type' => $this->assignee_type,
            'effective_from' => $from?->toDateString(),
            'effective_to' => $to?->toDateString(),
            'days_of_week' => array_values(array_map('intval', $this->days_of_week ?? [])),
            'days_label' => $this->daysLabel($this->days_of_week),
            'notes' => $this->notes,
            'is_active' => (bool) $this->is_active,
            'period' => $period,
            'shift' => $shift === null ? null : [
                'id' => $shift->uuid,
                'code' => $shift->code,
                'name' => $shift->name,
                'time_in' => $shift->time_in,
                'time_out' => $shift->time_out,
                'break_minutes' => (int) ($shift->break_minutes ?? 0),
                'grace_minutes' => (int) ($shift->grace_minutes ?? 0),
                'crosses_midnight' => (bool) ($shift->crosses_midnight ?? false),
                'label' => $shift->code.' — '.$shift->name,
            ],
            'employee' => $employee === null ? null : [
                'id' => $employee->uuid,
                'employee_number' => $employee->employee_number,
                'name' => trim($employee->first_name.' '.$employee->last_name),
                'email' => $employee->email,
                'department' => $employee->department === null ? null : [
                    'id' => $employee->department->uuid,
                    'code' => $employee->department->code,
                    'name' => $employee->department->name,
                ],
            ],
            'department' => $department === null ? null : [
                'id' => $department->uuid,
                'code' => $department->code,
                'name' => $department->name,
            ],
            'created_by' => $this->creator === null ? null : [
                'name' => $this->creator->name,
                'email' => $this->creator->email,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @param  list<int>|null  $days
     */
    private function daysLabel(?array $days): string
    {
        if ($days === null || $days === []) {
            return 'Every day';
        }

        $map = [
            1 => 'Mon',
            2 => 'Tue',
            3 => 'Wed',
            4 => 'Thu',
            5 => 'Fri',
            6 => 'Sat',
            7 => 'Sun',
        ];

        $sorted = array_values(array_unique(array_map('intval', $days)));
        sort($sorted);

        if ($sorted === [1, 2, 3, 4, 5]) {
            return 'Weekdays';
        }
        if ($sorted === [6, 7]) {
            return 'Weekends';
        }

        return implode(', ', array_map(static fn (int $day): string => $map[$day] ?? (string) $day, $sorted));
    }
}
