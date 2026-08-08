<?php

namespace App\Http\Controllers\API\Leave;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\AdjustLeaveBalanceRequest;
use App\Http\Resources\Leave\LeaveBalanceResource;
use App\Models\Employee;
use App\Services\Leave\LeaveBalanceService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class LeaveBalanceController extends Controller
{
    public function __construct(
        private readonly LeaveBalanceService $balances,
    ) {}

    /**
     * HR list of leave balances with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 10), 1), 100);

        $paginator = $this->balances->list([
            'search' => (string) $request->query('search', ''),
            'leave_year' => $request->query('leave_year', ''),
            'leave_type' => (string) $request->query('leave_type', ''),
            'department_id' => $request->query('department_id', ''),
        ], $perPage);

        return ApiResponse::success('Leave balances retrieved.', [
            'items' => LeaveBalanceResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'leave_year' => (int) ($request->query('leave_year') ?: $this->balances->currentLeaveYear()),
            ],
        ]);
    }

    /**
     * Balances for one employee (nested under /employees/{employee}).
     */
    public function forEmployee(Request $request, Employee $employee): JsonResponse
    {
        $year = $request->query('leave_year');
        $leaveYear = ($year !== null && $year !== '' && ctype_digit((string) $year))
            ? (int) $year
            : null;

        $items = $this->balances->forEmployee($employee, $leaveYear);

        return ApiResponse::success('Employee leave balances retrieved.', [
            'items' => LeaveBalanceResource::collection($items)->resolve(),
            'leave_year' => $leaveYear ?? $this->balances->currentLeaveYear(),
        ]);
    }

    /**
     * Manual HR adjustment with reason (audited ledger).
     */
    public function adjust(AdjustLeaveBalanceRequest $request): JsonResponse
    {
        try {
            $balance = $this->balances->adjust($request->validated());
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        return ApiResponse::success('Leave balance adjusted.', [
            'balance' => (new LeaveBalanceResource($balance->loadMissing(['employee.department', 'leaveType'])))->resolve(),
        ]);
    }
}
