<?php

namespace App\Http\Controllers\API\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\UpdatePasswordPolicyRequest;
use App\Services\Administration\PasswordPolicyService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class PasswordPolicyController extends Controller
{
    public function __construct(
        private readonly PasswordPolicyService $passwordPolicy,
    ) {}

    public function show(): JsonResponse
    {
        return ApiResponse::success('Password policy retrieved.', [
            'policy' => $this->passwordPolicy->current(),
        ]);
    }

    public function update(UpdatePasswordPolicyRequest $request): JsonResponse
    {
        $policy = $this->passwordPolicy->update($request->validated());

        return ApiResponse::success('Password policy updated.', [
            'policy' => $policy,
        ]);
    }
}
