<?php

namespace App\Http\Controllers\API\Employees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\CareerHistory\StoreEmployeeCareerHistoryRequest;
use App\Http\Requests\Employees\CareerHistory\UpdateEmployeeCareerHistoryRequest;
use App\Http\Resources\Employees\EmployeeCareerHistoryResource;
use App\Services\Employees\EmployeeCareerHistoryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class EmployeeCareerHistoryController extends Controller
{
    public function __construct(
        private readonly EmployeeCareerHistoryService $histories,
    ) {}

    public function index(string $employee): JsonResponse
    {
        $items = $this->histories->list($employee)->map(
            fn ($history) => (new EmployeeCareerHistoryResource($history))->resolve()
        )->values()->all();

        return ApiResponse::success('Career history retrieved.', [
            'items' => $items,
            'categories' => $this->histories->categories(),
            'category_options' => $this->histories->categoryOptions(),
            'rate_types' => $this->histories->rateTypes(),
        ]);
    }

    public function store(StoreEmployeeCareerHistoryRequest $request, string $employee): JsonResponse
    {
        $history = $this->histories->create($employee, $request->validated());

        return ApiResponse::success('Career history added.', [
            'history' => (new EmployeeCareerHistoryResource($history))->resolve(),
        ], 201);
    }

    public function update(
        UpdateEmployeeCareerHistoryRequest $request,
        string $employee,
        string $history,
    ): JsonResponse {
        $model = $this->histories->update($employee, $history, $request->validated());

        return ApiResponse::success('Career history updated.', [
            'history' => (new EmployeeCareerHistoryResource($model))->resolve(),
        ]);
    }

    public function destroy(string $employee, string $history): JsonResponse
    {
        $this->histories->delete($employee, $history);

        return ApiResponse::success('Career history removed.');
    }
}
