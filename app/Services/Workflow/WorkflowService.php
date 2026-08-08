<?php

namespace App\Services\Workflow;

use App\Models\Permission;
use App\Models\User;
use App\Models\WorkflowAction;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;
use App\Repositories\Workflow\WorkflowDefinitionRepository;
use App\Repositories\Workflow\WorkflowInstanceRepository;
use App\Services\Audit\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WorkflowService
{
    public function __construct(
        private readonly WorkflowDefinitionRepository $definitions,
        private readonly WorkflowInstanceRepository $instances,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return Collection<int, \App\Models\WorkflowDefinition>
     */
    public function listDefinitions(): Collection
    {
        return $this->definitions->allActive();
    }

    /**
     * @param  array{search?: string, status?: string, definition?: string, inbox?: bool}  $filters
     */
    public function listInstances(User $user, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        if (! empty($filters['inbox'])) {
            $perms = $this->instances->userStepPermissions($user);
            if ($perms === []) {
                $filters['inbox_permissions'] = ['__none__'];
            } else {
                $filters['inbox_permissions'] = $perms;
            }
            unset($filters['status']);
        }

        return $this->instances->paginate($filters, $perPage);
    }

    public function findInstance(string $uuid): WorkflowInstance
    {
        $instance = $this->instances->findByUuid($uuid);
        if ($instance === null) {
            abort(404, 'Workflow instance not found.');
        }

        return $instance;
    }

    public function start(string $definitionCode, Model $subject, User $starter): WorkflowInstance
    {
        $definition = $this->definitions->findByCode($definitionCode);
        if ($definition === null || ! $definition->is_active) {
            throw new InvalidArgumentException("Workflow \"{$definitionCode}\" is not available.");
        }

        if ($definition->steps->isEmpty()) {
            throw new InvalidArgumentException("Workflow \"{$definitionCode}\" has no steps configured.");
        }

        $firstStep = $definition->steps->sortBy('step_order')->first();

        $instance = $this->instances->create([
            'workflow_definition_id' => $definition->id,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'current_step_order' => (int) $firstStep->step_order,
            'status' => 'pending',
            'started_by' => $starter->id,
        ]);

        WorkflowAction::query()->create([
            'workflow_instance_id' => $instance->id,
            'step_order' => 0,
            'actor_id' => $starter->id,
            'action' => 'submit',
            'notes' => null,
            'acted_at' => now(),
        ]);

        $this->audit->log('workflow.instance.started', [
            'instance_id' => $instance->uuid,
            'definition' => $definition->code,
            'subject_type' => class_basename($subject),
            'subject_id' => method_exists($subject, 'getAttribute') ? ($subject->uuid ?? $subject->getKey()) : $subject->getKey(),
        ]);

        return $this->findInstance($instance->uuid);
    }

    /**
     * @param  array{notes?: string|null}  $payload
     */
    public function approve(User $actor, string $instanceUuid, array $payload = []): WorkflowInstance
    {
        return DB::transaction(function () use ($actor, $instanceUuid, $payload): WorkflowInstance {
            $instance = $this->findInstance($instanceUuid);
            if (! $instance->isPending()) {
                throw new InvalidArgumentException('Only pending workflow instances can be approved.');
            }

            $step = $this->requireCurrentStep($instance);
            $this->assertCanAct($actor, $step);

            WorkflowAction::query()->create([
                'workflow_instance_id' => $instance->id,
                'step_order' => $step->step_order,
                'actor_id' => $actor->id,
                'action' => 'approve',
                'notes' => isset($payload['notes']) ? trim((string) $payload['notes']) ?: null : null,
                'acted_at' => now(),
            ]);

            $next = $instance->definition->steps
                ->where('step_order', '>', $step->step_order)
                ->sortBy('step_order')
                ->first();

            if ($next !== null) {
                $instance = $this->instances->update($instance, [
                    'current_step_order' => (int) $next->step_order,
                ]);
                $this->audit->log('workflow.step.approved', [
                    'instance_id' => $instance->uuid,
                    'step_order' => $step->step_order,
                    'next_step_order' => $next->step_order,
                ]);
            } else {
                $instance = $this->instances->update($instance, [
                    'status' => 'approved',
                    'completed_at' => now(),
                ]);
                $this->audit->log('workflow.instance.approved', [
                    'instance_id' => $instance->uuid,
                    'final_step' => $step->step_order,
                ]);
            }

            return $instance;
        });
    }

    /**
     * @param  array{notes?: string|null}  $payload
     */
    public function reject(User $actor, string $instanceUuid, array $payload = []): WorkflowInstance
    {
        return DB::transaction(function () use ($actor, $instanceUuid, $payload): WorkflowInstance {
            $instance = $this->findInstance($instanceUuid);
            if (! $instance->isPending()) {
                throw new InvalidArgumentException('Only pending workflow instances can be rejected.');
            }

            $step = $this->requireCurrentStep($instance);
            $this->assertCanAct($actor, $step);

            WorkflowAction::query()->create([
                'workflow_instance_id' => $instance->id,
                'step_order' => $step->step_order,
                'actor_id' => $actor->id,
                'action' => 'reject',
                'notes' => isset($payload['notes']) ? trim((string) $payload['notes']) ?: null : null,
                'acted_at' => now(),
            ]);

            $instance = $this->instances->update($instance, [
                'status' => 'rejected',
                'completed_at' => now(),
            ]);

            $this->audit->log('workflow.instance.rejected', [
                'instance_id' => $instance->uuid,
                'step_order' => $step->step_order,
            ]);

            return $instance;
        });
    }

    public function cancel(User $actor, string $instanceUuid, ?string $notes = null): WorkflowInstance
    {
        return DB::transaction(function () use ($actor, $instanceUuid, $notes): WorkflowInstance {
            $instance = $this->findInstance($instanceUuid);
            if (! $instance->isPending()) {
                throw new InvalidArgumentException('Only pending workflow instances can be cancelled.');
            }

            WorkflowAction::query()->create([
                'workflow_instance_id' => $instance->id,
                'step_order' => $instance->current_step_order,
                'actor_id' => $actor->id,
                'action' => 'cancel',
                'notes' => $notes !== null ? (trim($notes) ?: null) : null,
                'acted_at' => now(),
            ]);

            $instance = $this->instances->update($instance, [
                'status' => 'cancelled',
                'completed_at' => now(),
            ]);

            $this->audit->log('workflow.instance.cancelled', [
                'instance_id' => $instance->uuid,
            ]);

            return $instance;
        });
    }

    /**
     * @return list<User>
     */
    public function usersForCurrentStep(WorkflowInstance $instance): array
    {
        $step = $instance->currentStep();
        if ($step === null) {
            return [];
        }

        return $this->usersWithPermission($step->approver_permission);
    }

    private function requireCurrentStep(WorkflowInstance $instance): WorkflowStep
    {
        $step = $instance->currentStep();
        if ($step === null) {
            throw new InvalidArgumentException('Current workflow step is not configured.');
        }

        return $step;
    }

    private function assertCanAct(User $actor, WorkflowStep $step): void
    {
        if (! $actor->hasPermission($step->approver_permission)) {
            abort(403, "You do not have permission for step \"{$step->label}\" ({$step->approver_permission}).");
        }
    }

    /**
     * @return list<User>
     */
    private function usersWithPermission(string $slug): array
    {
        $permission = Permission::query()->where('slug', $slug)->first();
        if ($permission === null) {
            return [];
        }

        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', function ($query) use ($permission): void {
                $query->whereHas('permissions', function ($inner) use ($permission): void {
                    $inner->where('permissions.id', $permission->id);
                });
            })
            ->get()
            ->all();
    }
}
