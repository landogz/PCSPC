<?php

namespace App\Services\Administration;

use App\Models\Department;
use App\Repositories\Administration\DepartmentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DepartmentService
{
    public function __construct(
        private readonly DepartmentRepository $departments,
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
        return $this->departments->create([
            'code' => strtoupper(trim($payload['code'])),
            'name' => $payload['name'],
            'description' => $payload['description'] ?? null,
            'is_active' => (bool) ($payload['is_active'] ?? true),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(string $uuid, array $payload): Department
    {
        $department = $this->find($uuid);

        return $this->departments->update($department, [
            'code' => strtoupper(trim($payload['code'])),
            'name' => $payload['name'],
            'description' => $payload['description'] ?? null,
            'is_active' => (bool) ($payload['is_active'] ?? $department->is_active),
        ]);
    }

    public function delete(string $uuid): void
    {
        $this->departments->delete($this->find($uuid));
    }
}
