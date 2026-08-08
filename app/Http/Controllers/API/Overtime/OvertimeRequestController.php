<?php

namespace App\Http\Controllers\API\Overtime;

use App\Http\Controllers\Controller;
use App\Http\Requests\Overtime\DecideOvertimeRequestRequest;
use App\Http\Requests\Overtime\StoreOvertimeRequestRequest;
use App\Http\Resources\Overtime\OvertimeRequestResource;
use App\Services\Overtime\OvertimeRequestService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class OvertimeRequestController extends Controller
{
    public function __construct(
        private readonly OvertimeRequestService $overtimeRequests,
    ) {}

    public function mine(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 10), 1), 100);

        $paginator = $this->overtimeRequests->listMine($request->user(), [
            'search' => (string) $request->query('search', ''),
            'status' => (string) $request->query('status', ''),
            'kind' => (string) $request->query('kind', ''),
        ], $perPage);

        return ApiResponse::success('My overtime requests retrieved.', [
            'items' => OvertimeRequestResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 10), 1), 100);

        $paginator = $this->overtimeRequests->listForApproval([
            'search' => (string) $request->query('search', ''),
            'status' => (string) $request->query('status', ''),
            'kind' => (string) $request->query('kind', ''),
        ], $perPage);

        return ApiResponse::success('Overtime requests retrieved.', [
            'items' => OvertimeRequestResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreOvertimeRequestRequest $request): JsonResponse
    {
        try {
            $model = $this->overtimeRequests->submit($request->user(), $request->validated());
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        return ApiResponse::success('Overtime request submitted.', [
            'request' => (new OvertimeRequestResource($model))->resolve(),
        ], 201);
    }

    public function show(string $overtimeRequest): JsonResponse
    {
        $model = $this->overtimeRequests->find($overtimeRequest);

        return ApiResponse::success('Overtime request retrieved.', [
            'request' => (new OvertimeRequestResource($model))->resolve(),
        ]);
    }

    public function approve(DecideOvertimeRequestRequest $request, string $overtimeRequest): JsonResponse
    {
        try {
            $model = $this->overtimeRequests->approve($request->user(), $overtimeRequest, $request->validated());
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        $message = $model->status === 'approved'
            ? 'Overtime request fully approved.'
            : 'Overtime step approved; awaiting next approver.';

        return ApiResponse::success($message, [
            'request' => (new OvertimeRequestResource($model))->resolve(),
        ]);
    }

    public function reject(DecideOvertimeRequestRequest $request, string $overtimeRequest): JsonResponse
    {
        try {
            $model = $this->overtimeRequests->reject($request->user(), $overtimeRequest, $request->validated());
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        return ApiResponse::success('Overtime request rejected.', [
            'request' => (new OvertimeRequestResource($model))->resolve(),
        ]);
    }

    public function cancel(Request $request, string $overtimeRequest): JsonResponse
    {
        try {
            $model = $this->overtimeRequests->cancel($request->user(), $overtimeRequest);
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        return ApiResponse::success('Overtime request cancelled.', [
            'request' => (new OvertimeRequestResource($model))->resolve(),
        ]);
    }
}
