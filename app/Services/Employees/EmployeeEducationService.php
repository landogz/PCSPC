<?php

namespace App\Services\Employees;

use App\Models\Employee;
use App\Models\EmployeeEducation;
use App\Repositories\Employees\EmployeeEducationRepository;
use App\Repositories\Employees\EmployeeRepository;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EmployeeEducationService
{
    public function __construct(
        private readonly EmployeeEducationRepository $educations,
        private readonly EmployeeRepository $employees,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return Collection<int, EmployeeEducation>
     */
    public function list(string $employeeUuid): Collection
    {
        return $this->educations->listForEmployee($this->resolveEmployee($employeeUuid));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(string $employeeUuid, array $payload): EmployeeEducation
    {
        $employee = $this->resolveEmployee($employeeUuid);
        $data = $this->mapPayload($payload);

        $education = DB::transaction(function () use ($employee, $data): EmployeeEducation {
            if ($data['is_highest']) {
                $this->educations->clearHighestFlags($employee);
            }

            return $this->educations->create($employee, $data);
        });

        $this->audit->log('employee.education.created', [
            'employee_id' => $employee->uuid,
            'employee_number' => $employee->employee_number,
            'education_id' => $education->uuid,
            'institution' => $education->institution,
            'level' => $education->level,
            'is_highest' => $education->is_highest,
        ]);

        return $education;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(string $employeeUuid, string $educationUuid, array $payload): EmployeeEducation
    {
        $employee = $this->resolveEmployee($employeeUuid);
        $education = $this->resolveEducation($employee, $educationUuid);
        $data = $this->mapPayload($payload);

        $education = DB::transaction(function () use ($employee, $education, $data): EmployeeEducation {
            if ($data['is_highest']) {
                $this->educations->clearHighestFlags($employee, $education->id);
            }

            return $this->educations->update($education, $data);
        });

        $this->audit->log('employee.education.updated', [
            'employee_id' => $employee->uuid,
            'employee_number' => $employee->employee_number,
            'education_id' => $education->uuid,
            'institution' => $education->institution,
            'level' => $education->level,
            'is_highest' => $education->is_highest,
        ]);

        return $education;
    }

    public function delete(string $employeeUuid, string $educationUuid): void
    {
        $employee = $this->resolveEmployee($employeeUuid);
        $education = $this->resolveEducation($employee, $educationUuid);
        $meta = [
            'employee_id' => $employee->uuid,
            'employee_number' => $employee->employee_number,
            'education_id' => $education->uuid,
            'institution' => $education->institution,
            'level' => $education->level,
        ];

        $this->educations->delete($education);
        $this->audit->log('employee.education.deleted', $meta);
    }

    /**
     * @return list<string>
     */
    public function levels(): array
    {
        return EmployeeEducation::LEVELS;
    }

    private function resolveEmployee(string $uuid): Employee
    {
        $employee = $this->employees->findByUuid($uuid);
        if ($employee === null) {
            abort(404, 'Employee not found.');
        }

        return $employee;
    }

    private function resolveEducation(Employee $employee, string $uuid): EmployeeEducation
    {
        $education = $this->educations->findForEmployee($employee, $uuid);
        if ($education === null) {
            abort(404, 'Education record not found.');
        }

        return $education;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function mapPayload(array $payload): array
    {
        $yearStarted = $payload['year_started'] ?? null;
        $yearEnded = $payload['year_ended'] ?? null;

        return [
            'institution' => trim((string) ($payload['institution'] ?? '')),
            'level' => (string) ($payload['level'] ?? ''),
            'degree_or_course' => $this->nullableString($payload['degree_or_course'] ?? null),
            'year_started' => $yearStarted !== null && $yearStarted !== '' ? (int) $yearStarted : null,
            'year_ended' => $yearEnded !== null && $yearEnded !== '' ? (int) $yearEnded : null,
            'is_highest' => (bool) ($payload['is_highest'] ?? false),
            'honors' => $this->nullableString($payload['honors'] ?? null),
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
