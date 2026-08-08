<?php

namespace App\Http\Resources\Workflow;

use App\Models\WorkflowDefinition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkflowDefinition */
class WorkflowDefinitionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'steps' => $this->relationLoaded('steps')
                ? $this->steps->map(fn ($step) => [
                    'id' => $step->uuid,
                    'order' => $step->step_order,
                    'label' => $step->label,
                    'permission' => $step->approver_permission,
                ])->values()->all()
                : [],
        ];
    }
}
