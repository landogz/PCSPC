<?php

namespace App\Http\Controllers\API\Leave;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\DecideLeaveRequestRequest;
use App\Http\Requests\Leave\StoreLeaveRequestRequest;
use App\Http\Resources\Leave\LeaveRequestResource;
use App\Services\Leave\LeaveRequestService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class LeaveRequestController extends Controller
{
    public function __construct(
        private readonly LeaveRequestService $leaveRequests,
    ) {}

    /**
     * Current employee's leave requests.
     */
    public function mine(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 10), 1), 100);

        $paginator = $this->leaveRequests->listMine($request->user(), [
            'search' => (string) $request->query('search', ''),
            'status' => (string) $request->query('status', ''),
            'leave_type' => (string) $request->query('leave_type', ''),
        ], $perPage);

        return ApiResponse::success('My leave requests retrieved.', [
            'items' => LeaveRequestResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Approver queue (defaults to pending).
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 10), 1), 100);

        $paginator = $this->leaveRequests->listForApproval([
            'search' => (string) $request->query('search', ''),
            'status' => (string) $request->query('status', ''),
            'leave_type' => (string) $request->query('leave_type', ''),
        ], $perPage);

        return ApiResponse::success('Leave requests retrieved.', [
            'items' => LeaveRequestResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreLeaveRequestRequest $request): JsonResponse
    {
        try {
            $leaveRequest = $this->leaveRequests->submit($request->user(), $request->validated());
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        return ApiResponse::success('Leave request submitted.', [
            'request' => (new LeaveRequestResource($leaveRequest))->resolve(),
        ], 201);
    }

    public function show(string $leaveRequest): JsonResponse
    {
        $model = $this->leaveRequests->find($leaveRequest);

        return ApiResponse::success('Leave request retrieved.', [
            'request' => (new LeaveRequestResource($model))->resolve(),
        ]);
    }

    public function approve(DecideLeaveRequestRequest $request, string $leaveRequest): JsonResponse
    {
        try {
            $model = $this->leaveRequests->approve($request->user(), $leaveRequest, $request->validated());
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        return ApiResponse::success(
            $model->status === 'approved'
                ? 'Leave request fully approved.'
                : 'Leave step approved; awaiting next approver.',
            [
                'request' => (new LeaveRequestResource($model))->resolve(),
            ]
        );
    }

    public function reject(DecideLeaveRequestRequest $request, string $leaveRequest): JsonResponse
    {
        try {
            $model = $this->leaveRequests->reject($request->user(), $leaveRequest, $request->validated());
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        return ApiResponse::success('Leave request rejected.', [
            'request' => (new LeaveRequestResource($model))->resolve(),
        ]);
    }

    public function cancel(Request $request, string $leaveRequest): JsonResponse
    {
        try {
            $model = $this->leaveRequests->cancel($request->user(), $leaveRequest);
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        return ApiResponse::success('Leave request cancelled.', [
            'request' => (new LeaveRequestResource($model))->resolve(),
        ]);
    }
}
