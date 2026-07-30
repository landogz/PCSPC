<?php

use App\Http\Controllers\API\Administration\DepartmentController;
use App\Http\Controllers\API\Audit\AuditLogController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\Security\UserController as SecurityUserController;
use App\Http\Controllers\Web\DocsPageController;
use App\Http\Controllers\Web\ModulePageController;
use App\Support\Navigation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function (): void {
    Route::view('/login', 'auth.login')->name('login');
});

Route::middleware('auth')->group(function (): void {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::get('/modules/{module}', [ModulePageController::class, 'show'])
        ->whereIn('module', Navigation::moduleKeys())
        ->name('modules.show');

    Route::get('/docs/{doc}', [DocsPageController::class, 'show'])
        ->whereIn('doc', ['modules', 'project-plan'])
        ->name('docs.show');
});

/*
| Cookie/session auth endpoints use the web stack so SPA login persists
| for /dashboard (Sanctum stateful + session). Mobile can still pass device_name
| for token issuance on the same login action.
*/
Route::prefix('api/v1/auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:'.config('auth_security.login_throttle', '5,1'));

    Route::post('/mfa/verify', [AuthController::class, 'verifyMfa'])
        ->middleware('throttle:'.config('auth_security.login_throttle', '5,1'));

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-others', [AuthController::class, 'logoutOthers']);
    });
});

Route::prefix('api/v1')->middleware('auth:sanctum')->group(function (): void {
    Route::middleware('permission:users.manage')->prefix('security')->group(function (): void {
        Route::get('/roles', [SecurityUserController::class, 'roles']);
        Route::get('/users', [SecurityUserController::class, 'index']);
        Route::post('/users', [SecurityUserController::class, 'store']);
        Route::get('/users/{user}', [SecurityUserController::class, 'show']);
        Route::put('/users/{user}', [SecurityUserController::class, 'update']);
        Route::post('/users/{user}/unlock', [SecurityUserController::class, 'unlock']);
        Route::post('/users/{user}/deactivate', [SecurityUserController::class, 'deactivate']);
    });

    Route::middleware('permission:audit.view')->prefix('audit')->group(function (): void {
        Route::get('/logs', [AuditLogController::class, 'index']);
        Route::get('/events', [AuditLogController::class, 'events']);
        Route::get('/logs/{log}', [AuditLogController::class, 'show']);
    });

    Route::middleware('permission:administration.manage')->prefix('administration')->group(function (): void {
        Route::get('/departments', [DepartmentController::class, 'index']);
        Route::post('/departments', [DepartmentController::class, 'store']);
        Route::get('/departments/{department}', [DepartmentController::class, 'show']);
        Route::put('/departments/{department}', [DepartmentController::class, 'update']);
        Route::delete('/departments/{department}', [DepartmentController::class, 'destroy']);
    });
});
