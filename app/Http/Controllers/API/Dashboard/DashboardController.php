<?php

namespace App\Http\Controllers\API\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Inject the dashboard service.
     */
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {}

    /**
     * Return summary statistics for the authenticated user's dashboard.
     */
    public function stats(Request $request): JsonResponse
    {
        return ApiResponse::success(
            'Dashboard stats retrieved.',
            $this->dashboard->stats($request->user()),
        );
    }
}
