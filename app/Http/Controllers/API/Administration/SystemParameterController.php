<?php

namespace App\Http\Controllers\API\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\UpdateSystemParametersRequest;
use App\Http\Requests\Administration\UploadSystemLogoRequest;
use App\Services\Administration\SystemParameterService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class SystemParameterController extends Controller
{
    public function __construct(
        private readonly SystemParameterService $parameters,
    ) {}

    public function show(): JsonResponse
    {
        return ApiResponse::success('System parameters retrieved.', [
            'parameters' => $this->parameters->current(),
            'meta' => $this->parameters->meta(),
        ]);
    }

    public function update(UpdateSystemParametersRequest $request): JsonResponse
    {
        $parameters = $this->parameters->update($request->validated());

        return ApiResponse::success('System parameters updated.', [
            'parameters' => $parameters,
            'meta' => $this->parameters->meta(),
        ]);
    }

    public function uploadLogo(UploadSystemLogoRequest $request): JsonResponse
    {
        $parameters = $this->parameters->storeLogo($request->file('logo'));

        return ApiResponse::success('Company logo updated.', [
            'parameters' => $parameters,
            'meta' => $this->parameters->meta(),
        ]);
    }

    public function removeLogo(): JsonResponse
    {
        $parameters = $this->parameters->clearLogo();

        return ApiResponse::success('Company logo reset to default.', [
            'parameters' => $parameters,
            'meta' => $this->parameters->meta(),
        ]);
    }
}
