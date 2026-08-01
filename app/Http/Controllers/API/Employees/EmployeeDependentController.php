<?php

namespace App\Http\Controllers\API\Employees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\Dependents\StoreEmployeeDependentRequest;
use App\Http\Requests\Employees\Dependents\UpdateEmployeeDependentRequest;
use App\Http\Resources\Employees\EmployeeDependentResource;
use App\Services\Employees\EmployeeDependentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class EmployeeDependentController extends Controller
{
    public function __construct(
        private readonly EmployeeDependentService $dependents,
    ) {}

    public function index(string $employee): JsonResponse
    {
        $items = $this->dependents->list($employee)->map(
            fn ($dependent) => (new EmployeeDependentResource($dependent))->resolve()
        )->values()->all();

        return ApiResponse::success('Dependents retrieved.', [
            'items' => $items,
            'relationships' => $this->dependents->relationships(),
        ]);
    }

    public function store(StoreEmployeeDependentRequest $request, string $employee): JsonResponse
    {
        $dependent = $this->dependents->create($employee, $request->validated());

        return ApiResponse::success('Dependent added.', [
            'dependent' => (new EmployeeDependentResource($dependent))->resolve(),
        ], 201);
    }

    public function update(UpdateEmployeeDependentRequest $request, string $employee, string $dependent): JsonResponse
    {
        $model = $this->dependents->update($employee, $dependent, $request->validated());

        return ApiResponse::success('Dependent updated.', [
            'dependent' => (new EmployeeDependentResource($model))->resolve(),
        ]);
    }

    public function destroy(string $employee, string $dependent): JsonResponse
    {
        $this->dependents->delete($employee, $dependent);

        return ApiResponse::success('Dependent removed.');
    }
}
