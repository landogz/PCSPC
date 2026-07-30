<?php

namespace App\Http\Controllers\API\Security;

use App\Http\Controllers\Controller;
use App\Http\Requests\Security\StoreUserRequest;
use App\Http\Requests\Security\UpdateUserRequest;
use App\Http\Resources\Security\SecurityUserResource;
use App\Services\Security\UserService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $users,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 10), 1), 100);

        $paginator = $this->users->list([
            'search' => (string) $request->query('search', ''),
            'status' => (string) $request->query('status', ''),
            'role' => (string) $request->query('role', ''),
        ], $perPage);

        return ApiResponse::success('Users retrieved.', [
            'items' => SecurityUserResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->users->create($request->validated());

        return ApiResponse::success('User created.', [
            'user' => (new SecurityUserResource($user))->resolve(),
        ], 201);
    }

    public function show(string $user): JsonResponse
    {
        $model = $this->users->find($user);

        return ApiResponse::success('User retrieved.', [
            'user' => (new SecurityUserResource($model))->resolve(),
        ]);
    }

    public function update(UpdateUserRequest $request, string $user): JsonResponse
    {
        $model = $this->users->update($user, $request->validated(), $request->user());

        return ApiResponse::success('User updated.', [
            'user' => (new SecurityUserResource($model))->resolve(),
        ]);
    }

    public function unlock(string $user): JsonResponse
    {
        $model = $this->users->unlock($user);

        return ApiResponse::success('User unlocked.', [
            'user' => (new SecurityUserResource($model))->resolve(),
        ]);
    }

    public function deactivate(Request $request, string $user): JsonResponse
    {
        $model = $this->users->deactivate($user, $request->user());

        return ApiResponse::success('User deactivated.', [
            'user' => (new SecurityUserResource($model))->resolve(),
        ]);
    }

    public function roles(): JsonResponse
    {
        $roles = $this->users->roles()->map(fn ($role) => [
            'id' => $role->uuid,
            'name' => $role->name,
            'slug' => $role->slug,
            'requires_mfa' => (bool) $role->requires_mfa,
        ])->values()->all();

        return ApiResponse::success('Roles retrieved.', [
            'roles' => $roles,
        ]);
    }
}
