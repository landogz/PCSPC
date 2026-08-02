<?php

namespace App\Http\Controllers\API\Departments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Departments\StoreDepartmentRequest;
use App\Http\Requests\Departments\UpdateDepartmentRequest;
use App\Http\Resources\Departments\DepartmentResource;
use App\Services\Departments\DepartmentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    /**
     * Inject the department service.
     */
    public function __construct(
        private readonly DepartmentService $departments,
    ) {}

    /**
     * List departments with search, status filters, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 10), 1), 100);

        $paginator = $this->departments->list([
            'search' => (string) $request->query('search', ''),
            'status' => (string) $request->query('status', ''),
        ], $perPage);

        return ApiResponse::success('Departments retrieved.', [
            'items' => DepartmentResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Create a new department record.
     */
    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $department = $this->departments->create($request->validated());

        return ApiResponse::success('Department created.', [
            'department' => (new DepartmentResource($department))->resolve(),
        ], 201);
    }

    /**
     * Return a single department by UUID.
     */
    public function show(string $department): JsonResponse
    {
        $model = $this->departments->find($department);

        return ApiResponse::success('Department retrieved.', [
            'department' => (new DepartmentResource($model))->resolve(),
        ]);
    }

    /**
     * Update an existing department.
     */
    public function update(UpdateDepartmentRequest $request, string $department): JsonResponse
    {
        $model = $this->departments->update($department, $request->validated());

        return ApiResponse::success('Department updated.', [
            'department' => (new DepartmentResource($model))->resolve(),
        ]);
    }

    /**
     * Permanently delete a department.
     */
    public function destroy(string $department): JsonResponse
    {
        $this->departments->delete($department);

        return ApiResponse::success('Department deleted.');
    }
}
