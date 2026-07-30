<?php

namespace App\Services\Departments;

use App\Models\Department;
use App\Repositories\Departments\DepartmentRepository;
use App\Services\Audit\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DepartmentService
{
    public function __construct(
        private readonly DepartmentRepository $departments,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{search?: string, status?: string}  $filters
     */
    public function list(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->departments->paginate($filters, $perPage);
    }

    public function find(string $uuid): Department
    {
        $department = $this->departments->findByUuid($uuid);

        if ($department === null) {
            abort(404, 'Department not found.');
        }

        return $department;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): Department
    {
        $department = $this->departments->create([
            'code' => strtoupper(trim($payload['code'])),
            'name' => $payload['name'],
            'description' => $payload['description'] ?? null,
            'is_active' => (bool) ($payload['is_active'] ?? true),
        ]);

        $this->audit->log('department.created', [
            'department_id' => $department->uuid,
            'code' => $department->code,
            'name' => $department->name,
        ]);

        return $department;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(string $uuid, array $payload): Department
    {
        $department = $this->find($uuid);

        $updated = $this->departments->update($department, [
            'code' => strtoupper(trim($payload['code'])),
            'name' => $payload['name'],
            'description' => $payload['description'] ?? null,
            'is_active' => (bool) ($payload['is_active'] ?? $department->is_active),
        ]);

        $this->audit->log('department.updated', [
            'department_id' => $updated->uuid,
            'code' => $updated->code,
            'name' => $updated->name,
            'is_active' => (bool) $updated->is_active,
        ]);

        return $updated;
    }

    public function delete(string $uuid): void
    {
        $department = $this->find($uuid);
        $meta = [
            'department_id' => $department->uuid,
            'code' => $department->code,
            'name' => $department->name,
        ];

        $this->departments->delete($department);

        $this->audit->log('department.deleted', $meta);
    }
}
