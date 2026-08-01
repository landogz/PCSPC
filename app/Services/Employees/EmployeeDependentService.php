<?php

namespace App\Services\Employees;

use App\Models\Employee;
use App\Models\EmployeeDependent;
use App\Repositories\Employees\EmployeeDependentRepository;
use App\Repositories\Employees\EmployeeRepository;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Collection;

class EmployeeDependentService
{
    public function __construct(
        private readonly EmployeeDependentRepository $dependents,
        private readonly EmployeeRepository $employees,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return Collection<int, EmployeeDependent>
     */
    public function list(string $employeeUuid): Collection
    {
        return $this->dependents->listForEmployee($this->resolveEmployee($employeeUuid));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(string $employeeUuid, array $payload): EmployeeDependent
    {
        $employee = $this->resolveEmployee($employeeUuid);
        $dependent = $this->dependents->create($employee, $this->mapPayload($payload));

        $this->audit->log('employee.dependent.created', [
            'employee_id' => $employee->uuid,
            'employee_number' => $employee->employee_number,
            'dependent_id' => $dependent->uuid,
            'relationship' => $dependent->relationship,
            'full_name' => $dependent->fullName(),
            'is_beneficiary' => $dependent->is_beneficiary,
            'is_emergency_contact' => $dependent->is_emergency_contact,
        ]);

        return $dependent;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(string $employeeUuid, string $dependentUuid, array $payload): EmployeeDependent
    {
        $employee = $this->resolveEmployee($employeeUuid);
        $dependent = $this->resolveDependent($employee, $dependentUuid);
        $dependent = $this->dependents->update($dependent, $this->mapPayload($payload));

        $this->audit->log('employee.dependent.updated', [
            'employee_id' => $employee->uuid,
            'employee_number' => $employee->employee_number,
            'dependent_id' => $dependent->uuid,
            'relationship' => $dependent->relationship,
            'full_name' => $dependent->fullName(),
            'is_beneficiary' => $dependent->is_beneficiary,
            'is_emergency_contact' => $dependent->is_emergency_contact,
        ]);

        return $dependent;
    }

    public function delete(string $employeeUuid, string $dependentUuid): void
    {
        $employee = $this->resolveEmployee($employeeUuid);
        $dependent = $this->resolveDependent($employee, $dependentUuid);
        $meta = [
            'employee_id' => $employee->uuid,
            'employee_number' => $employee->employee_number,
            'dependent_id' => $dependent->uuid,
            'relationship' => $dependent->relationship,
            'full_name' => $dependent->fullName(),
        ];

        $this->dependents->delete($dependent);

        $this->audit->log('employee.dependent.deleted', $meta);
    }

    /**
     * @return list<string>
     */
    public function relationships(): array
    {
        return EmployeeDependent::RELATIONSHIPS;
    }

    private function resolveEmployee(string $uuid): Employee
    {
        $employee = $this->employees->findByUuid($uuid);

        if ($employee === null) {
            abort(404, 'Employee not found.');
        }

        return $employee;
    }

    private function resolveDependent(Employee $employee, string $uuid): EmployeeDependent
    {
        $dependent = $this->dependents->findForEmployee($employee, $uuid);

        if ($dependent === null) {
            abort(404, 'Dependent not found.');
        }

        return $dependent;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function mapPayload(array $payload): array
    {
        return [
            'first_name' => trim((string) ($payload['first_name'] ?? '')),
            'middle_name' => $this->nullableString($payload['middle_name'] ?? null),
            'last_name' => trim((string) ($payload['last_name'] ?? '')),
            'suffix' => $this->nullableString($payload['suffix'] ?? null),
            'relationship' => (string) ($payload['relationship'] ?? ''),
            'birth_date' => $payload['birth_date'] ?? null,
            'gender' => $this->nullableString($payload['gender'] ?? null),
            'mobile' => $this->nullableString($payload['mobile'] ?? null),
            'is_beneficiary' => (bool) ($payload['is_beneficiary'] ?? false),
            'is_emergency_contact' => (bool) ($payload['is_emergency_contact'] ?? false),
            'notes' => $this->nullableString($payload['notes'] ?? null),
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
