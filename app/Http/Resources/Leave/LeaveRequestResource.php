<?php

namespace App\Http\Resources\Leave;

use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LeaveRequest */
class LeaveRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $employee = $this->relationLoaded('employee') ? $this->employee : null;
        $leaveType = $this->relationLoaded('leaveType') ? $this->leaveType : null;
        $decider = $this->relationLoaded('decider') ? $this->decider : null;
        $instance = $this->relationLoaded('workflowInstance') ? $this->workflowInstance : null;
        $step = $instance?->currentStep();

        return [
            'id' => $this->uuid,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'days' => number_format((float) $this->days, 2, '.', ''),
            'reason' => $this->reason,
            'status' => $this->status,
            'approver_notes' => $this->approver_notes,
            'decided_at' => $this->decided_at?->toIso8601String(),
            'employee' => $employee ? [
                'id' => $employee->uuid,
                'employee_number' => $employee->employee_number,
                'full_name' => $employee->fullName(),
                'department' => $employee->relationLoaded('department') && $employee->department
                    ? $employee->department->name
                    : null,
            ] : null,
            'leave_type' => $leaveType ? [
                'id' => $leaveType->uuid,
                'code' => $leaveType->code,
                'name' => $leaveType->name,
                'requires_hr' => (bool) $leaveType->requires_hr,
                'requires_reason' => (bool) $leaveType->requires_reason,
            ] : null,
            'decided_by' => $decider ? [
                'id' => $decider->uuid,
                'name' => $decider->name,
            ] : null,
            'workflow' => $instance ? [
                'id' => $instance->uuid,
                'status' => $instance->status,
                'current_step_order' => $instance->current_step_order,
                'current_step_label' => $step?->label,
                'current_step_permission' => $step?->approver_permission,
                'definition' => $instance->relationLoaded('definition') && $instance->definition
                    ? [
                        'id' => $instance->definition->uuid,
                        'code' => $instance->definition->code,
                        'name' => $instance->definition->name,
                    ]
                    : null,
            ] : null,
        ];
    }
}
