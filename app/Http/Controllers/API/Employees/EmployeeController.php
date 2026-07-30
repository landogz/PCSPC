<?php

namespace App\Http\Controllers\API\Employees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\StoreEmployeeRequest;
use App\Http\Requests\Employees\UpdateEmployeeRequest;
use App\Http\Resources\Employees\EmployeeResource;
use App\Services\Employees\EmployeeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeService $employees,
    ) {}

    public function meta(): JsonResponse
    {
        return ApiResponse::success('Employee meta retrieved.', $this->employees->meta());
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 10), 1), 100);
        $reveal = $request->user()?->hasPermission('employees.manage') ?? false;

        $paginator = $this->employees->list([
            'search' => (string) $request->query('search', ''),
            'status' => (string) $request->query('status', ''),
            'department' => (string) $request->query('department', ''),
        ], $perPage);

        $items = $paginator->getCollection()->map(
            fn ($employee) => (new EmployeeResource($employee, false))->resolve()
        )->values()->all();

        return ApiResponse::success('Employees retrieved.', [
            'items' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'can_manage' => $reveal,
        ]);
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $payload = collect($request->validated())->except(['photo'])->all();
        $result = $this->employees->create($payload, $request->file('photo'));

        return ApiResponse::success('Employee created and login provisioned.', [
            'employee' => (new EmployeeResource($result['employee'], true))->resolve(),
            'temporary_password' => $result['temporary_password'],
        ], 201);
    }

    public function show(Request $request, string $employee): JsonResponse
    {
        $model = $this->employees->find($employee);
        $reveal = $request->user()?->hasPermission('employees.manage') ?? false;

        return ApiResponse::success('Employee retrieved.', [
            'employee' => (new EmployeeResource($model, $reveal))->resolve(),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, string $employee): JsonResponse
    {
        $payload = collect($request->validated())->except(['photo', 'remove_photo'])->all();
        $result = $this->employees->update(
            $employee,
            $payload,
            $request->file('photo'),
            (bool) $request->boolean('remove_photo'),
        );

        return ApiResponse::success('Employee updated.', [
            'employee' => (new EmployeeResource($result['employee'], true))->resolve(),
            'temporary_password' => $result['temporary_password'],
        ]);
    }

    public function deactivate(string $employee): JsonResponse
    {
        $model = $this->employees->deactivate($employee);

        return ApiResponse::success('Employee deactivated.', [
            'employee' => (new EmployeeResource($model, true))->resolve(),
        ]);
    }

    public function destroy(string $employee): JsonResponse
    {
        $this->employees->delete($employee);

        return ApiResponse::success('Employee deleted.');
    }
}
