<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Stateless / mobile-oriented API routes. Session auth lives on web routes
| under the same /api/v1/auth/* paths so SPA cookies persist.
|
*/

Route::prefix('v1')->group(function (): void {
    Route::get('/health', function () {
        return response()->json([
            'status' => true,
            'message' => 'PCSPC API is healthy',
            'data' => [
                'app' => config('app.name'),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    })->middleware('throttle:60,1');
});
