<?php

namespace App\Services\Leave;

use App\Models\Employee;
use App\Models\User;
use App\Repositories\Leave\LeaveBalanceRepository;
use App\Repositories\Leave\LeaveTypeRepository;
use App\Services\Audit\AuditLogger;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LeaveAccrualService
{
    public function __construct(
        private readonly LeaveBalanceRepository $balances,
        private readonly LeaveTypeRepository $types,
        private readonly LeaveBalanceService $balanceService,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Monthly VL rate from tenure tiers (docs/LEAVE_AND_OT_POLICY.md).
     */
    public function monthlyRateForYearsOfService(int $years): float
    {
        $tiers = config('leave.vl_tiers', []);
        foreach ($tiers as $tier) {
            $max = $tier['max_years'] ?? null;
            if ($max === null || $years <= (int) $max) {
                return (float) $tier['monthly_days'];
            }
        }

        return 1.66;
    }

    public function yearsOfService(Employee $employee, Carbon $asOf): int
    {
        if ($employee->date_hired === null) {
            return 0;
        }

        $hired = $employee->date_hired->copy()->startOfDay();
        if ($hired->gt($asOf)) {
            return 0;
        }

        return (int) $hired->diffInYears($asOf);
    }

    public function isEligibleForAccrual(Employee $employee, Carbon $monthEnd): bool
    {
        if (! $employee->isActiveEmployment()) {
            return false;
        }

        if ($employee->date_regularized === null) {
            return false;
        }

        return ! $employee->date_regularized->copy()->startOfDay()->gt($monthEnd);
    }

    /**
     * Run monthly accrual for accruing leave types (VL). Idempotent per employee + period.
     *
     * @return array{year_month: string, leave_year: int, processed: int, accrued: int, skipped: int, amount_total: float}
     */
    public function runMonthly(string $yearMonth): array
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $yearMonth)) {
            throw new InvalidArgumentException('year_month must be YYYY-MM.');
        }

        $monthStart = Carbon::parse($yearMonth.'-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth()->startOfDay();
        $periodKey = $yearMonth;
        $leaveYear = $this->balanceService->leaveYearForPeriod($yearMonth);

        $leaveType = $this->types->firstAccruing();
        if ($leaveType === null) {
            throw new InvalidArgumentException('No accruing leave type is configured.');
        }

        /** @var User|null $actor */
        $actor = Auth::user();

        $processed = 0;
        $accrued = 0;
        $skipped = 0;
        $amountTotal = 0.0;

        $employeeIds = $this->balances->eligibleEmployeeIdsForAccrual();

        foreach ($employeeIds as $employeeId) {
            $employee = Employee::query()->find($employeeId);
            if ($employee === null) {
                continue;
            }

            $processed++;

            if (! $this->isEligibleForAccrual($employee, $monthEnd)) {
                $skipped++;
                continue;
            }

            $years = $this->yearsOfService($employee, $monthEnd);
            $amount = round($this->monthlyRateForYearsOfService($years), 2);
            if ($amount <= 0) {
                $skipped++;
                continue;
            }

            $didAccrue = DB::transaction(function () use (
                $employee,
                $leaveType,
                $leaveYear,
                $amount,
                $monthEnd,
                $periodKey,
                $years,
                $actor
            ): bool {
                $balance = $this->balances->findOrCreate($employee->id, $leaveType->id, $leaveYear);

                if ($this->balances->hasEarnForPeriod($balance->id, $periodKey)) {
                    return false;
                }

                $balance = $this->balances->applyEarn($balance, $amount);

                $this->balances->createLedgerEntry([
                    'leave_balance_id' => $balance->id,
                    'employee_id' => $employee->id,
                    'leave_type_id' => $leaveType->id,
                    'entry_type' => 'earn',
                    'amount' => $amount,
                    'effective_date' => $monthEnd->toDateString(),
                    'period_key' => $periodKey,
                    'reason' => 'Monthly VL accrual',
                    'meta' => [
                        'years_of_service' => $years,
                        'monthly_rate' => $amount,
                        'ending_after' => $balance->ending(),
                    ],
                    'created_by' => $actor?->id,
                ]);

                return true;
            });

            if ($didAccrue) {
                $accrued++;
                $amountTotal = round($amountTotal + $amount, 2);
            } else {
                $skipped++;
            }
        }

        $this->audit->log('leave.accrual.run', [
            'year_month' => $yearMonth,
            'leave_year' => $leaveYear,
            'leave_type' => $leaveType->code,
            'processed' => $processed,
            'accrued' => $accrued,
            'skipped' => $skipped,
            'amount_total' => $amountTotal,
        ]);

        return [
            'year_month' => $yearMonth,
            'leave_year' => $leaveYear,
            'leave_type' => $leaveType->code,
            'processed' => $processed,
            'accrued' => $accrued,
            'skipped' => $skipped,
            'amount_total' => $amountTotal,
        ];
    }
}
