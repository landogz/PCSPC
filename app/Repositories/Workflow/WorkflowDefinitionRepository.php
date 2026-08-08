<?php

namespace App\Repositories\Workflow;

use App\Models\WorkflowDefinition;
use Illuminate\Support\Collection;

class WorkflowDefinitionRepository
{
    public function findByCode(string $code): ?WorkflowDefinition
    {
        return WorkflowDefinition::query()
            ->with('steps')
            ->where('code', $code)
            ->first();
    }

    public function findByUuid(string $uuid): ?WorkflowDefinition
    {
        return WorkflowDefinition::query()
            ->with('steps')
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * @return Collection<int, WorkflowDefinition>
     */
    public function allActive(): Collection
    {
        return WorkflowDefinition::query()
            ->with('steps')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
