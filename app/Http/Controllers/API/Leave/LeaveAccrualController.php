<?php

namespace App\Http\Controllers\API\Leave;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\RunLeaveAccrualRequest;
use App\Services\Leave\LeaveAccrualService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class LeaveAccrualController extends Controller
{
    public function __construct(
        private readonly LeaveAccrualService $accruals,
    ) {}

    /**
     * Run monthly VL accrual for a year-month (idempotent).
     */
    public function run(RunLeaveAccrualRequest $request): JsonResponse
    {
        try {
            $result = $this->accruals->runMonthly($request->validated('year_month'));
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        return ApiResponse::success('Leave accrual completed.', $result);
    }
}
