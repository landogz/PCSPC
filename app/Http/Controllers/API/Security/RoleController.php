<?php

namespace App\Http\Controllers\API\Security;

use App\Http\Controllers\Controller;
use App\Http\Requests\Security\StoreRoleRequest;
use App\Http\Requests\Security\UpdateRoleRequest;
use App\Http\Resources\Security\RoleResource;
use App\Services\Security\RoleService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleService $roles,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 10), 1), 100);

        $paginator = $this->roles->list([
            'search' => (string) $request->query('search', ''),
        ], $perPage);

        return ApiResponse::success('Roles retrieved.', [
            'items' => RoleResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->roles->create($request->validated());

        return ApiResponse::success('Role created.', [
            'role' => (new RoleResource($role))->resolve(),
        ], 201);
    }

    public function show(string $role): JsonResponse
    {
        $model = $this->roles->find($role);

        return ApiResponse::success('Role retrieved.', [
            'role' => (new RoleResource($model))->resolve(),
        ]);
    }

    public function update(UpdateRoleRequest $request, string $role): JsonResponse
    {
        $model = $this->roles->update($role, $request->validated());

        return ApiResponse::success('Role updated.', [
            'role' => (new RoleResource($model))->resolve(),
        ]);
    }

    public function destroy(string $role): JsonResponse
    {
        $this->roles->delete($role);

        return ApiResponse::success('Role deleted.');
    }

    public function permissions(): JsonResponse
    {
        $grouped = $this->roles->permissions()
            ->groupBy(fn ($permission) => $permission->module ?: 'general')
            ->map(fn ($items, $module) => [
                'module' => $module,
                'permissions' => $items->map(fn ($permission) => [
                    'id' => $permission->uuid,
                    'name' => $permission->name,
                    'slug' => $permission->slug,
                    'module' => $permission->module,
                    'description' => $permission->description,
                ])->values()->all(),
            ])
            ->values()
            ->all();

        return ApiResponse::success('Permissions retrieved.', [
            'groups' => $grouped,
            'permissions' => $this->roles->permissions()->map(fn ($permission) => [
                'id' => $permission->uuid,
                'name' => $permission->name,
                'slug' => $permission->slug,
                'module' => $permission->module,
                'description' => $permission->description,
            ])->values()->all(),
        ]);
    }
}
