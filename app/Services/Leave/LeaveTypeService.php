<?php

namespace App\Services\Leave;

use App\Models\LeaveType;
use App\Repositories\Leave\LeaveTypeRepository;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class LeaveTypeService
{
    public function __construct(
        private readonly LeaveTypeRepository $types,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return Collection<int, LeaveType>
     */
    public function list(bool $activeOnly = false): Collection
    {
        return $this->types->all($activeOnly);
    }

    public function find(string $uuid): LeaveType
    {
        $type = $this->types->findByUuid($uuid);
        if ($type === null) {
            abort(404, 'Leave type not found.');
        }

        return $type;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): LeaveType
    {
        $type = $this->types->create($this->mapPayload($payload));

        $this->audit->log('leave.type.created', [
            'leave_type_id' => $type->uuid,
            'code' => $type->code,
            'name' => $type->name,
            'is_accruing' => (bool) $type->is_accruing,
            'is_active' => (bool) $type->is_active,
        ]);

        return $type;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(string $uuid, array $payload): LeaveType
    {
        $type = $this->find($uuid);
        $updated = $this->types->update($type, $this->mapPayload($payload));

        $this->audit->log('leave.type.updated', [
            'leave_type_id' => $updated->uuid,
            'code' => $updated->code,
            'name' => $updated->name,
            'is_accruing' => (bool) $updated->is_accruing,
            'is_active' => (bool) $updated->is_active,
        ]);

        return $updated;
    }

    public function delete(string $uuid): void
    {
        $type = $this->find($uuid);

        if ($this->types->hasUsage($type)) {
            throw new InvalidArgumentException(
                'This leave type is in use by balances or requests. Deactivate it instead of deleting.'
            );
        }

        $meta = [
            'leave_type_id' => $type->uuid,
            'code' => $type->code,
            'name' => $type->name,
        ];

        $this->types->delete($type);

        $this->audit->log('leave.type.deleted', $meta);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function mapPayload(array $payload): array
    {
        return [
            'code' => strtoupper(trim((string) ($payload['code'] ?? ''))),
            'name' => trim((string) ($payload['name'] ?? '')),
            'is_accruing' => (bool) ($payload['is_accruing'] ?? false),
            'requires_reason' => (bool) ($payload['requires_reason'] ?? true),
            'requires_hr' => (bool) ($payload['requires_hr'] ?? false),
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'sort_order' => (int) ($payload['sort_order'] ?? 0),
        ];
    }
}
