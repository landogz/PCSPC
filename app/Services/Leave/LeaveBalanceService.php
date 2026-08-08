<?php

namespace App\Services\Leave;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\User;
use App\Repositories\Leave\LeaveBalanceRepository;
use App\Repositories\Leave\LeaveTypeRepository;
use App\Services\Administration\SystemParameterService;
use App\Services\Audit\AuditLogger;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LeaveBalanceService
{
    public function __construct(
        private readonly LeaveBalanceRepository $balances,
        private readonly LeaveTypeRepository $types,
        private readonly SystemParameterService $systemParameters,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{search?: string, leave_year?: int|string, leave_type?: string, department_id?: int|string}  $filters
     */
    public function list(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        if (($filters['leave_year'] ?? '') === '' || $filters['leave_year'] === null) {
            $filters['leave_year'] = $this->currentLeaveYear();
        }

        return $this->balances->paginate($filters, $perPage);
    }

    /**
     * @return Collection<int, LeaveBalance>
     */
    public function forEmployee(Employee $employee, ?int $leaveYear = null): Collection
    {
        return $this->balances->forEmployee($employee->id, $leaveYear ?? $this->currentLeaveYear())
            ->loadMissing(['employee.department', 'leaveType']);
    }

    public function find(string $uuid): LeaveBalance
    {
        $balance = $this->balances->findByUuid($uuid);
        if ($balance === null) {
            abort(404, 'Leave balance not found.');
        }

        return $balance;
    }

    public function currentLeaveYear(?Carbon $asOf = null): int
    {
        $asOf ??= Carbon::now(config('app.timezone', 'Asia/Manila'));
        $startMonth = (int) ($this->systemParameters->current()['leave_year_start_month'] ?? 1);
        $startMonth = max(1, min(12, $startMonth));

        if ($asOf->month >= $startMonth) {
            return (int) $asOf->year;
        }

        return (int) $asOf->year - 1;
    }

    /**
     * Resolve leave year for a calendar year-month (YYYY-MM).
     */
    public function leaveYearForPeriod(string $yearMonth): int
    {
        $date = Carbon::parse($yearMonth.'-01')->startOfMonth();

        return $this->currentLeaveYear($date);
    }

    /**
     * @param  array{employee_id: string, leave_type_id: string, leave_year?: int, amount: float|int|string, reason: string, effective_date?: string}  $payload
     */
    public function adjust(array $payload): LeaveBalance
    {
        $employee = Employee::query()->where('uuid', $payload['employee_id'])->first();
        if ($employee === null) {
            abort(404, 'Employee not found.');
        }

        $leaveType = $this->types->findByUuid($payload['leave_type_id']);
        if ($leaveType === null) {
            abort(404, 'Leave type not found.');
        }

        $amount = round((float) $payload['amount'], 2);
        if ($amount == 0.0) {
            throw new InvalidArgumentException('Adjustment amount cannot be zero.');
        }

        $leaveYear = (int) ($payload['leave_year'] ?? $this->currentLeaveYear());
        $effectiveDate = isset($payload['effective_date'])
            ? Carbon::parse($payload['effective_date'])->toDateString()
            : Carbon::now(config('app.timezone', 'Asia/Manila'))->toDateString();

        /** @var User|null $actor */
        $actor = Auth::user();

        return DB::transaction(function () use ($employee, $leaveType, $amount, $leaveYear, $effectiveDate, $payload, $actor): LeaveBalance {
            $balance = $this->balances->findOrCreate($employee->id, $leaveType->id, $leaveYear);
            $balance = $this->balances->applyAdjust($balance, $amount);

            $entry = $this->balances->createLedgerEntry([
                'leave_balance_id' => $balance->id,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'entry_type' => 'adjust',
                'amount' => $amount,
                'effective_date' => $effectiveDate,
                'period_key' => null,
                'reason' => $payload['reason'],
                'meta' => [
                    'ending_after' => $balance->ending(),
                ],
                'created_by' => $actor?->id,
            ]);

            $this->audit->log('leave.balance.adjusted', [
                'balance_id' => $balance->uuid,
                'ledger_id' => $entry->uuid,
                'employee_id' => $employee->uuid,
                'leave_type' => $leaveType->code,
                'leave_year' => $leaveYear,
                'amount' => $amount,
            ]);

            return $balance;
        });
    }
}
