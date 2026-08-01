<?php

namespace App\Http\Controllers\API\Shifts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shifts\StoreShiftRequest;
use App\Http\Requests\Shifts\UpdateShiftRequest;
use App\Http\Resources\Shifts\ShiftResource;
use App\Services\Shifts\ShiftService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function __construct(
        private readonly ShiftService $shifts,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 10), 1), 100);

        $paginator = $this->shifts->list([
            'search' => (string) $request->query('search', ''),
            'status' => (string) $request->query('status', ''),
        ], $perPage);

        return ApiResponse::success('Shifts retrieved.', [
            'items' => ShiftResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreShiftRequest $request): JsonResponse
    {
        $shift = $this->shifts->create($request->validated());

        return ApiResponse::success('Shift created.', [
            'shift' => (new ShiftResource($shift))->resolve(),
        ], 201);
    }

    public function show(string $shift): JsonResponse
    {
        $model = $this->shifts->find($shift);

        return ApiResponse::success('Shift retrieved.', [
            'shift' => (new ShiftResource($model))->resolve(),
        ]);
    }

    public function update(UpdateShiftRequest $request, string $shift): JsonResponse
    {
        $model = $this->shifts->update($shift, $request->validated());

        return ApiResponse::success('Shift updated.', [
            'shift' => (new ShiftResource($model))->resolve(),
        ]);
    }

    public function destroy(string $shift): JsonResponse
    {
        $this->shifts->delete($shift);

        return ApiResponse::success('Shift deleted.');
    }
}
