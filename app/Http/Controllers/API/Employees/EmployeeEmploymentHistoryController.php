<?php

namespace App\Http\Controllers\API\Employees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\EmploymentHistory\StoreEmployeeEmploymentHistoryRequest;
use App\Http\Requests\Employees\EmploymentHistory\UpdateEmployeeEmploymentHistoryRequest;
use App\Http\Resources\Employees\EmployeeEmploymentHistoryResource;
use App\Services\Employees\EmployeeEmploymentHistoryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class EmployeeEmploymentHistoryController extends Controller
{
    /**
     * Inject the employee employment history service.
     */
    public function __construct(
        private readonly EmployeeEmploymentHistoryService $histories,
    ) {}

    /**
     * List prior employment history entries for an employee.
     */
    public function index(string $employee): JsonResponse
    {
        $items = $this->histories->list($employee)->map(
            fn ($history) => (new EmployeeEmploymentHistoryResource($history))->resolve()
        )->values()->all();

        return ApiResponse::success('Employment history retrieved.', [
            'items' => $items,
        ]);
    }

    /**
     * Add a prior employment history entry to an employee record.
     */
    public function store(StoreEmployeeEmploymentHistoryRequest $request, string $employee): JsonResponse
    {
        $history = $this->histories->create($employee, $request->validated());

        return ApiResponse::success('Employment history added.', [
            'history' => (new EmployeeEmploymentHistoryResource($history))->resolve(),
        ], 201);
    }

    /**
     * Update an existing prior employment history entry.
     */
    public function update(
        UpdateEmployeeEmploymentHistoryRequest $request,
        string $employee,
        string $history,
    ): JsonResponse {
        $model = $this->histories->update($employee, $history, $request->validated());

        return ApiResponse::success('Employment history updated.', [
            'history' => (new EmployeeEmploymentHistoryResource($model))->resolve(),
        ]);
    }

    /**
     * Remove a prior employment history entry from an employee record.
     */
    public function destroy(string $employee, string $history): JsonResponse
    {
        $this->histories->delete($employee, $history);

        return ApiResponse::success('Employment history removed.');
    }
}
