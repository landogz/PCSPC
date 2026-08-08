<?php

namespace App\Http\Controllers\API\Leave;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\StoreLeaveTypeRequest;
use App\Http\Requests\Leave\UpdateLeaveTypeRequest;
use App\Http\Resources\Leave\LeaveTypeResource;
use App\Services\Leave\LeaveTypeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class LeaveTypeController extends Controller
{
    public function __construct(
        private readonly LeaveTypeService $types,
    ) {}

    /**
     * List leave types (active by default; pass all=1 for every type).
     */
    public function index(Request $request): JsonResponse
    {
        $activeOnly = ! $request->boolean('all', false);

        $items = $this->types->list($activeOnly);

        return ApiResponse::success('Leave types retrieved.', [
            'items' => LeaveTypeResource::collection($items)->resolve(),
        ]);
    }

    public function store(StoreLeaveTypeRequest $request): JsonResponse
    {
        $type = $this->types->create($request->validated());

        return ApiResponse::success('Leave type created.', [
            'leave_type' => (new LeaveTypeResource($type))->resolve(),
        ], 201);
    }

    public function show(string $leaveType): JsonResponse
    {
        $type = $this->types->find($leaveType);

        return ApiResponse::success('Leave type retrieved.', [
            'leave_type' => (new LeaveTypeResource($type))->resolve(),
        ]);
    }

    public function update(UpdateLeaveTypeRequest $request, string $leaveType): JsonResponse
    {
        $type = $this->types->update($leaveType, $request->validated());

        return ApiResponse::success('Leave type updated.', [
            'leave_type' => (new LeaveTypeResource($type))->resolve(),
        ]);
    }

    public function destroy(string $leaveType): JsonResponse
    {
        try {
            $this->types->delete($leaveType);
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        return ApiResponse::success('Leave type deleted.');
    }
}
