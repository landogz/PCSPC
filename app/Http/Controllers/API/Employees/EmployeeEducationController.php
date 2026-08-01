<?php

namespace App\Http\Controllers\API\Employees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\Educations\StoreEmployeeEducationRequest;
use App\Http\Requests\Employees\Educations\UpdateEmployeeEducationRequest;
use App\Http\Resources\Employees\EmployeeEducationResource;
use App\Services\Employees\EmployeeEducationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class EmployeeEducationController extends Controller
{
    public function __construct(
        private readonly EmployeeEducationService $educations,
    ) {}

    public function index(string $employee): JsonResponse
    {
        $items = $this->educations->list($employee)->map(
            fn ($education) => (new EmployeeEducationResource($education))->resolve()
        )->values()->all();

        return ApiResponse::success('Education records retrieved.', [
            'items' => $items,
            'levels' => $this->educations->levels(),
        ]);
    }

    public function store(StoreEmployeeEducationRequest $request, string $employee): JsonResponse
    {
        $education = $this->educations->create($employee, $request->validated());

        return ApiResponse::success('Education record added.', [
            'education' => (new EmployeeEducationResource($education))->resolve(),
        ], 201);
    }

    public function update(UpdateEmployeeEducationRequest $request, string $employee, string $education): JsonResponse
    {
        $model = $this->educations->update($employee, $education, $request->validated());

        return ApiResponse::success('Education record updated.', [
            'education' => (new EmployeeEducationResource($model))->resolve(),
        ]);
    }

    public function destroy(string $employee, string $education): JsonResponse
    {
        $this->educations->delete($employee, $education);

        return ApiResponse::success('Education record removed.');
    }
}
