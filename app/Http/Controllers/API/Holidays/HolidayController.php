<?php

namespace App\Http\Controllers\API\Holidays;

use App\Http\Controllers\Controller;
use App\Http\Requests\Holidays\StoreHolidayRequest;
use App\Http\Requests\Holidays\UpdateHolidayRequest;
use App\Http\Resources\Holidays\HolidayResource;
use App\Services\Holidays\HolidayService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function __construct(
        private readonly HolidayService $holidays,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 10), 1), 100);

        $paginator = $this->holidays->list([
            'search' => (string) $request->query('search', ''),
            'status' => (string) $request->query('status', ''),
            'type' => (string) $request->query('type', ''),
            'year' => (string) $request->query('year', ''),
        ], $perPage);

        return ApiResponse::success('Holidays retrieved.', [
            'items' => HolidayResource::collection($paginator->getCollection())->resolve(),
            'types' => $this->holidays->types(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreHolidayRequest $request): JsonResponse
    {
        $holiday = $this->holidays->create($request->validated());

        return ApiResponse::success('Holiday created.', [
            'holiday' => (new HolidayResource($holiday))->resolve(),
        ], 201);
    }

    public function show(string $holiday): JsonResponse
    {
        $model = $this->holidays->find($holiday);

        return ApiResponse::success('Holiday retrieved.', [
            'holiday' => (new HolidayResource($model))->resolve(),
        ]);
    }

    public function update(UpdateHolidayRequest $request, string $holiday): JsonResponse
    {
        $model = $this->holidays->update($holiday, $request->validated());

        return ApiResponse::success('Holiday updated.', [
            'holiday' => (new HolidayResource($model))->resolve(),
        ]);
    }

    public function destroy(string $holiday): JsonResponse
    {
        $this->holidays->delete($holiday);

        return ApiResponse::success('Holiday deleted.');
    }
}
