<?php

namespace App\Http\Resources\Workflow;

use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\WorkflowInstance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkflowInstance */
class WorkflowInstanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $definition = $this->relationLoaded('definition') ? $this->definition : null;
        $step = $this->currentStep();
        $subject = $this->relationLoaded('subject') ? $this->subject : null;
        $starter = $this->relationLoaded('starter') ? $this->starter : null;

        $subjectSummary = null;
        if ($subject instanceof OvertimeRequest) {
            $subject->loadMissing('employee');
            $subjectSummary = [
                'type' => 'overtime',
                'id' => $subject->uuid,
                'label' => ($subject->kind === 'ot_meal' ? 'OT Meal' : 'OT')
                    .' · '.($subject->work_date?->toDateString() ?? '')
                    .' · '.number_format((float) $subject->hours, 2, '.', '').'h',
                'status' => $subject->status,
                'employee' => $subject->employee?->fullName(),
                'module_url' => '/modules/overtime',
            ];
        } elseif ($subject instanceof LeaveRequest) {
            $subject->loadMissing(['employee', 'leaveType']);
            $subjectSummary = [
                'type' => 'leave',
                'id' => $subject->uuid,
                'label' => ($subject->leaveType?->code ?? 'Leave')
                    .' · '.($subject->start_date?->toDateString() ?? '')
                    .' → '.($subject->end_date?->toDateString() ?? '')
                    .' · '.number_format((float) $subject->days, 2, '.', '').'d',
                'status' => $subject->status,
                'employee' => $subject->employee?->fullName(),
                'module_url' => '/modules/leave',
            ];
        } elseif ($subject !== null) {
            $subjectSummary = [
                'type' => class_basename($subject),
                'id' => $subject->uuid ?? (string) $subject->getKey(),
                'label' => class_basename($subject),
                'status' => $subject->status ?? null,
                'module_url' => null,
            ];
        }

        return [
            'id' => $this->uuid,
            'status' => $this->status,
            'current_step_order' => $this->current_step_order,
            'current_step_label' => $step?->label,
            'current_step_permission' => $step?->approver_permission,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'definition' => $definition ? [
                'id' => $definition->uuid,
                'code' => $definition->code,
                'name' => $definition->name,
                'steps' => $definition->relationLoaded('steps')
                    ? $definition->steps->map(fn ($s) => [
                        'order' => $s->step_order,
                        'label' => $s->label,
                        'permission' => $s->approver_permission,
                    ])->values()->all()
                    : [],
            ] : null,
            'starter' => $starter ? [
                'id' => $starter->uuid,
                'name' => $starter->name,
            ] : null,
            'subject' => $subjectSummary,
            'actions' => $this->relationLoaded('actions')
                ? $this->actions->map(fn ($action) => [
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
        ];
    }
}
