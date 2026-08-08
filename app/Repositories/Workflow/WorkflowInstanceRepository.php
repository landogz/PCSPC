<?php

namespace App\Repositories\Workflow;

use App\Models\User;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class WorkflowInstanceRepository
{
    /**
     * @param  array{search?: string, status?: string, definition?: string, inbox_permissions?: list<string>|null}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = WorkflowInstance::query()
            ->with(['definition.steps', 'starter', 'actions.actor', 'subject'])
            ->orderByDesc('created_at');

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '') {
            $query->where('status', $status);
        }

        $definition = trim((string) ($filters['definition'] ?? ''));
        if ($definition !== '') {
            $query->whereHas('definition', function (Builder $q) use ($definition): void {
                $q->where('code', $definition)->orWhere('uuid', $definition);
            });
        }

        $inboxPermissions = $filters['inbox_permissions'] ?? null;
        if (is_array($inboxPermissions) && $inboxPermissions !== []) {
            $query->where('status', 'pending')
                ->whereHas('definition.steps', function (Builder $q) use ($inboxPermissions): void {
                    $q->whereColumn('workflow_steps.step_order', 'workflow_instances.current_step_order')
                        ->whereIn('approver_permission', $inboxPermissions);
                });
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search): void {
                $inner->whereHas('definition', function (Builder $q) use ($search): void {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                })->orWhereHas('starter', function (Builder $q) use ($search): void {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            });
        }

        return $query->paginate($perPage);
    }

    public function findByUuid(string $uuid): ?WorkflowInstance
    {
        return WorkflowInstance::query()
            ->with(['definition.steps', 'starter', 'actions.actor', 'subject'])
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): WorkflowInstance
    {
        return WorkflowInstance::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(WorkflowInstance $instance, array $data): WorkflowInstance
    {
        $instance->fill($data);
        $instance->save();

        return $instance->fresh(['definition.steps', 'starter', 'actions.actor', 'subject']);
    }

    /**
     * @return list<string>
     */
    public function userStepPermissions(User $user): array
    {
        $slugs = WorkflowDefinition::query()
            ->where('is_active', true)
            ->with('steps')
            ->get()
            ->flatMap(fn (WorkflowDefinition $d) => $d->steps->pluck('approver_permission'))
            ->unique()
            ->values()
            ->all();

        return array_values(array_filter(
            $slugs,
            fn (string $slug) => $user->hasPermission($slug)
        ));
    }
}
