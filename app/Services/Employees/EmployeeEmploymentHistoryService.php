<?php

namespace App\Services\Employees;

use App\Models\Employee;
use App\Models\EmployeeEmploymentHistory;
use App\Repositories\Employees\EmployeeEmploymentHistoryRepository;
use App\Repositories\Employees\EmployeeRepository;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EmployeeEmploymentHistoryService
{
    public function __construct(
        private readonly EmployeeEmploymentHistoryRepository $histories,
        private readonly EmployeeRepository $employees,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return Collection<int, EmployeeEmploymentHistory>
     */
    public function list(string $employeeUuid): Collection
    {
        return $this->histories->listForEmployee($this->resolveEmployee($employeeUuid));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(string $employeeUuid, array $payload): EmployeeEmploymentHistory
    {
        $employee = $this->resolveEmployee($employeeUuid);
        $data = $this->mapPayload($payload);

        $history = DB::transaction(function () use ($employee, $data): EmployeeEmploymentHistory {
            if ($data['is_current']) {
                $this->histories->clearCurrentFlags($employee);
            }

            return $this->histories->create($employee, $data);
        });

        $this->audit->log('employee.employment_history.created', [
            'employee_id' => $employee->uuid,
            'employee_number' => $employee->employee_number,
            'history_id' => $history->uuid,
            'employer_name' => $history->employer_name,
            'position_title' => $history->position_title,
            'is_current' => $history->is_current,
        ]);

        return $history;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(string $employeeUuid, string $historyUuid, array $payload): EmployeeEmploymentHistory
    {
        $employee = $this->resolveEmployee($employeeUuid);
        $history = $this->resolveHistory($employee, $historyUuid);
        $data = $this->mapPayload($payload);

        $history = DB::transaction(function () use ($employee, $history, $data): EmployeeEmploymentHistory {
            if ($data['is_current']) {
                $this->histories->clearCurrentFlags($employee, $history->id);
            }

            return $this->histories->update($history, $data);
        });

        $this->audit->log('employee.employment_history.updated', [
            'employee_id' => $employee->uuid,
            'employee_number' => $employee->employee_number,
            'history_id' => $history->uuid,
            'employer_name' => $history->employer_name,
            'position_title' => $history->position_title,
            'is_current' => $history->is_current,
        ]);

        return $history;
    }

    public function delete(string $employeeUuid, string $historyUuid): void
    {
        $employee = $this->resolveEmployee($employeeUuid);
        $history = $this->resolveHistory($employee, $historyUuid);
        $meta = [
            'employee_id' => $employee->uuid,
            'employee_number' => $employee->employee_number,
            'history_id' => $history->uuid,
            'employer_name' => $history->employer_name,
            'position_title' => $history->position_title,
        ];

        $this->histories->delete($history);
        $this->audit->log('employee.employment_history.deleted', $meta);
    }

    private function resolveEmployee(string $uuid): Employee
    {
        $employee = $this->employees->findByUuid($uuid);
        if ($employee === null) {
            abort(404, 'Employee not found.');
        }

        return $employee;
    }

    private function resolveHistory(Employee $employee, string $uuid): EmployeeEmploymentHistory
    {
        $history = $this->histories->findForEmployee($employee, $uuid);
        if ($history === null) {
            abort(404, 'Employment history record not found.');
        }

        return $history;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function mapPayload(array $payload): array
    {
        $isCurrent = (bool) ($payload['is_current'] ?? false);

        return [
            'employer_name' => trim((string) ($payload['employer_name'] ?? '')),
            'position_title' => trim((string) ($payload['position_title'] ?? '')),
            'location' => $this->nullableString($payload['location'] ?? null),
            'date_from' => $payload['date_from'] ?? null,
            'date_to' => $isCurrent ? null : ($payload['date_to'] ?? null),
            'is_current' => $isCurrent,
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
