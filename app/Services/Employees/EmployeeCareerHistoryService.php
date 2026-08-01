<?php

namespace App\Services\Employees;

use App\Models\Employee;
use App\Models\EmployeeCareerHistory;
use App\Repositories\Employees\EmployeeCareerHistoryRepository;
use App\Repositories\Employees\EmployeeRepository;
use App\Services\Audit\AuditLogger;
use App\Services\Lookups\LookupService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EmployeeCareerHistoryService
{
    public function __construct(
        private readonly EmployeeCareerHistoryRepository $histories,
        private readonly EmployeeRepository $employees,
        private readonly LookupService $lookups,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return Collection<int, EmployeeCareerHistory>
     */
    public function list(string $employeeUuid): Collection
    {
        return $this->histories->listForEmployee($this->resolveEmployee($employeeUuid));
    }

    /**
     * @return list<string>
     */
    public function categories(): array
    {
        return $this->lookups->activeCodes('employment_category', EmployeeCareerHistory::CATEGORIES);
    }

    /**
     * @return list<array{code: string, label: string}>
     */
    public function categoryOptions(): array
    {
        return $this->lookups->activeOptions('employment_category', EmployeeCareerHistory::CATEGORIES);
    }

    /**
     * @return list<string>
     */
    public function rateTypes(): array
    {
        return EmployeeCareerHistory::RATE_TYPES;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(string $employeeUuid, array $payload): EmployeeCareerHistory
    {
        $employee = $this->resolveEmployee($employeeUuid);
        $data = $this->mapPayload($payload);

        $history = DB::transaction(function () use ($employee, $data): EmployeeCareerHistory {
            if ($data['is_current']) {
                $this->histories->clearCurrentFlags($employee);
                $this->syncEmployeePosition($employee, $data['position_title']);
            }

            return $this->histories->create($employee, $data);
        });

        $this->audit->log('employee.career_history.created', $this->auditMeta($employee, $history));

        return $history;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(string $employeeUuid, string $historyUuid, array $payload): EmployeeCareerHistory
    {
        $employee = $this->resolveEmployee($employeeUuid);
        $history = $this->resolveHistory($employee, $historyUuid);
        $data = $this->mapPayload($payload);

        $history = DB::transaction(function () use ($employee, $history, $data): EmployeeCareerHistory {
            if ($data['is_current']) {
                $this->histories->clearCurrentFlags($employee, $history->id);
                $this->syncEmployeePosition($employee, $data['position_title']);
            }

            return $this->histories->update($history, $data);
        });

        $this->audit->log('employee.career_history.updated', $this->auditMeta($employee, $history));

        return $history;
    }

    public function delete(string $employeeUuid, string $historyUuid): void
    {
        $employee = $this->resolveEmployee($employeeUuid);
        $history = $this->resolveHistory($employee, $historyUuid);
        $meta = $this->auditMeta($employee, $history);

        $this->histories->delete($history);
        $this->audit->log('employee.career_history.deleted', $meta);
    }

    private function resolveEmployee(string $uuid): Employee
    {
        $employee = $this->employees->findByUuid($uuid);
        if ($employee === null) {
            abort(404, 'Employee not found.');
        }

        return $employee;
    }

    private function resolveHistory(Employee $employee, string $uuid): EmployeeCareerHistory
    {
        $history = $this->histories->findForEmployee($employee, $uuid);
        if ($history === null) {
            abort(404, 'Career history record not found.');
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
        $salary = $payload['basic_salary'] ?? null;
        $salaryValue = null;
        if ($salary !== null && $salary !== '') {
            $salaryValue = number_format((float) $salary, 2, '.', '');
        }

        return [
            'position_title' => trim((string) ($payload['position_title'] ?? '')),
            'employment_category' => (string) ($payload['employment_category'] ?? ''),
            'basic_salary' => $salaryValue,
            'salary_rate_type' => (string) ($payload['salary_rate_type'] ?? 'monthly'),
            'currency' => strtoupper(trim((string) ($payload['currency'] ?? 'PHP'))) ?: 'PHP',
            'effective_from' => $payload['effective_from'] ?? null,
            'effective_to' => $isCurrent ? null : ($payload['effective_to'] ?? null),
            'is_current' => $isCurrent,
            'notes' => $this->nullableString($payload['notes'] ?? null),
        ];
    }

    private function syncEmployeePosition(Employee $employee, string $positionTitle): void
    {
        $trimmed = trim($positionTitle);
        if ($trimmed === '' || $employee->position_title === $trimmed) {
            return;
        }

        $employee->position_title = $trimmed;
        $employee->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function auditMeta(Employee $employee, EmployeeCareerHistory $history): array
    {
        return [
            'employee_id' => $employee->uuid,
            'employee_number' => $employee->employee_number,
            'history_id' => $history->uuid,
            'position_title' => $history->position_title,
            'employment_category' => $history->employment_category,
            'salary_rate_type' => $history->salary_rate_type,
            'has_salary' => filled($history->basic_salary),
            'is_current' => $history->is_current,
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
