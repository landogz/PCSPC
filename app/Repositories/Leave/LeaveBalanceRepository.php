<?php

namespace App\Repositories\Leave;

use App\Models\LeaveBalance;
use App\Models\LeaveLedgerEntry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class LeaveBalanceRepository
{
    /**
     * @param  array{search?: string, leave_year?: int|string, leave_type?: string, department_id?: int|string}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = LeaveBalance::query()
            ->with(['employee.department', 'leaveType'])
            ->join('employees', 'employees.id', '=', 'leave_balances.employee_id')
            ->select('leave_balances.*')
            ->orderBy('employees.last_name')
            ->orderBy('employees.first_name')
            ->orderBy('leave_balances.leave_year', 'desc');

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('employees.first_name', 'like', "%{$search}%")
                    ->orWhere('employees.last_name', 'like', "%{$search}%")
                    ->orWhere('employees.employee_number', 'like', "%{$search}%")
                    ->orWhere('employees.email', 'like', "%{$search}%");
            });
        }

        $year = $filters['leave_year'] ?? null;
        if ($year !== null && $year !== '' && ctype_digit((string) $year)) {
            $query->where('leave_balances.leave_year', (int) $year);
        }

        $type = trim((string) ($filters['leave_type'] ?? ''));
        if ($type !== '') {
            $query->whereHas('leaveType', function (Builder $q) use ($type): void {
                $q->where('uuid', $type)->orWhere('code', $type);
            });
        }

        $departmentId = $filters['department_id'] ?? null;
        if ($departmentId !== null && $departmentId !== '' && ctype_digit((string) $departmentId)) {
            $query->where('employees.department_id', (int) $departmentId);
        }

        return $query->paginate($perPage);
    }

    /**
     * @return Collection<int, LeaveBalance>
     */
    public function forEmployee(int $employeeId, ?int $leaveYear = null): Collection
    {
        $query = LeaveBalance::query()
            ->with('leaveType')
            ->where('employee_id', $employeeId)
            ->orderBy('leave_year', 'desc');

        if ($leaveYear !== null) {
            $query->where('leave_year', $leaveYear);
        }

        return $query->get();
    }

    public function findByUuid(string $uuid): ?LeaveBalance
    {
        return LeaveBalance::query()->with(['employee', 'leaveType'])->where('uuid', $uuid)->first();
    }

    public function findOrCreate(int $employeeId, int $leaveTypeId, int $leaveYear): LeaveBalance
    {
        return LeaveBalance::query()->firstOrCreate(
            [
                'employee_id' => $employeeId,
                'leave_type_id' => $leaveTypeId,
                'leave_year' => $leaveYear,
            ],
            [
                'beginning' => 0,
                'earned' => 0,
                'used' => 0,
                'adjusted' => 0,
            ]
        );
    }

    public function hasEarnForPeriod(int $balanceId, string $periodKey): bool
    {
        return LeaveLedgerEntry::query()
            ->where('leave_balance_id', $balanceId)
            ->where('entry_type', 'earn')
            ->where('period_key', $periodKey)
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createLedgerEntry(array $data): LeaveLedgerEntry
    {
        return LeaveLedgerEntry::query()->create($data);
    }

    public function applyEarn(LeaveBalance $balance, float $amount): LeaveBalance
    {
        $balance->earned = round((float) $balance->earned + $amount, 2);
        $balance->save();

        return $balance->fresh(['employee', 'leaveType']);
    }

    public function applyAdjust(LeaveBalance $balance, float $amount): LeaveBalance
    {
        $balance->adjusted = round((float) $balance->adjusted + $amount, 2);
        $balance->save();

        return $balance->fresh(['employee', 'leaveType']);
    }

    public function applyUse(LeaveBalance $balance, float $amount): LeaveBalance
    {
        $balance->used = round((float) $balance->used + $amount, 2);
        $balance->save();

        return $balance->fresh(['employee', 'leaveType']);
    }

    /**
     * @return list<int>
     */
    public function eligibleEmployeeIdsForAccrual(): array
    {
        return DB::table('employees')
            ->whereIn('employment_status', ['active', 'on_leave'])
            ->whereNotNull('date_regularized')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
