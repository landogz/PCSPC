<?php

namespace App\Http\Resources\Overtime;

use App\Models\OvertimeRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OvertimeRequest */
class OvertimeRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $employee = $this->relationLoaded('employee') ? $this->employee : null;
        $instance = $this->relationLoaded('workflowInstance') ? $this->workflowInstance : null;
        $step = $instance?->currentStep();

        return [
            'id' => $this->uuid,
            'kind' => $this->kind,
            'kind_label' => $this->kind === 'ot_meal' ? 'OT Meal' : 'OT',
            'work_date' => $this->work_date?->toDateString(),
            'hours' => number_format((float) $this->hours, 2, '.', ''),
            'reason' => $this->reason,
            'meal_notes' => $this->meal_notes,
            'status' => $this->status,
            'employee' => $employee ? [
                'id' => $employee->uuid,
                'employee_number' => $employee->employee_number,
                'full_name' => $employee->fullName(),
                'department' => $employee->relationLoaded('department') && $employee->department
                    ? $employee->department->name
                    : null,
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
                'actions' => $instance->relationLoaded('actions')
                    ? $instance->actions->map(fn ($action) => [
                        'id' => $action->uuid,
                        'step_order' => $action->step_order,
                        'action' => $action->action,
                        'notes' => $action->notes,
                        'acted_at' => $action->acted_at?->toIso8601String(),
                        'actor' => $action->relationLoaded('actor') && $action->actor
                            ? ['id' => $action->actor->uuid, 'name' => $action->actor->name]
                            : null,
                    ])->values()->all()
                    : [],
            ] : null,
        ];
    }
}
