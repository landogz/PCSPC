<?php

namespace App\Http\Controllers\API\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Requests\Profile\UploadAvatarRequest;
use App\Http\Resources\UserResource;
use App\Services\Profile\ProfileService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Inject the profile service.
     */
    public function __construct(
        private readonly ProfileService $profiles,
    ) {}

    /**
     * Return the authenticated user's editable profile.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return ApiResponse::error('Unauthenticated.', [], 401);
        }

        $profile = $this->profiles->show($user);

        return ApiResponse::success('Profile retrieved.', [
            'user' => (new UserResource($profile))->resolve(),
        ]);
    }

    /**
     * Update the authenticated user's display name.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return ApiResponse::error('Unauthenticated.', [], 401);
        }

        $updated = $this->profiles->update($user, $request->validated());

        return ApiResponse::success('Profile updated successfully.', [
            'user' => (new UserResource($updated))->resolve(),
        ]);
    }

    /**
     * Upload or replace the authenticated user's profile photo.
     */
    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return ApiResponse::error('Unauthenticated.', [], 401);
        }

        $updated = $this->profiles->uploadAvatar($user, $request->file('photo'));

        return ApiResponse::success('Profile photo updated.', [
            'user' => (new UserResource($updated))->resolve(),
        ]);
    }

    /**
     * Remove the authenticated user's profile photo.
     */
    public function removeAvatar(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return ApiResponse::error('Unauthenticated.', [], 401);
        }

        $updated = $this->profiles->removeAvatar($user);

        return ApiResponse::success('Profile photo removed.', [
            'user' => (new UserResource($updated))->resolve(),
        ]);
    }
}
