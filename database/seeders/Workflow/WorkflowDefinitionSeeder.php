<?php

namespace Database\Seeders\Workflow;

use App\Models\WorkflowDefinition;
use App\Models\WorkflowStep;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WorkflowDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = config('workflow.seed_definitions', []);

        foreach ($definitions as $def) {
            $model = WorkflowDefinition::query()->firstOrNew(['code' => $def['code']]);
            if (! $model->exists) {
                $model->uuid = (string) Str::uuid();
            }
            $model->fill([
                'name' => $def['name'],
                'description' => $def['description'] ?? null,
                'is_active' => (bool) ($def['is_active'] ?? true),
            ])->save();

            $keepOrders = [];
            foreach ($def['steps'] ?? [] as $step) {
                $keepOrders[] = (int) $step['step_order'];
                $stepModel = WorkflowStep::query()->firstOrNew([
                    'workflow_definition_id' => $model->id,
                    'step_order' => (int) $step['step_order'],
                ]);
                if (! $stepModel->exists) {
                    $stepModel->uuid = (string) Str::uuid();
                }
                $stepModel->fill([
                    'label' => $step['label'],
                    'approver_permission' => $step['approver_permission'],
                ])->save();
            }

            if ($keepOrders !== []) {
                WorkflowStep::query()
                    ->where('workflow_definition_id', $model->id)
                    ->whereNotIn('step_order', $keepOrders)
                    ->delete();
            }
        }
    }
}
