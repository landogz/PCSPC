<?php

use App\Http\Controllers\API\Administration\PasswordPolicyController;
use App\Http\Controllers\API\Audit\AuditLogController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\Departments\DepartmentController;
use App\Http\Controllers\API\Employees\EmployeeController;
use App\Http\Controllers\API\Security\RoleController as SecurityRoleController;
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

Route::middleware(['auth', 'password.current'])->group(function (): void {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::get('/modules/{module}', [ModulePageController::class, 'show'])
        ->whereIn('module', Navigation::moduleKeys())
        ->name('modules.show');

    Route::get('/docs/{doc}', [DocsPageController::class, 'show'])
        ->whereIn('doc', ['modules', 'project-plan'])
        ->name('docs.show');
});

Route::middleware('auth')->group(function (): void {
    Route::view('/account/password', 'auth.password')->name('account.password');
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
        Route::get('/password/policy', [AuthController::class, 'passwordPolicy']);
        Route::post('/password', [AuthController::class, 'changePassword']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-others', [AuthController::class, 'logoutOthers'])
            ->middleware('password.current');
    });
});

Route::prefix('api/v1')->middleware(['auth:sanctum', 'password.current'])->group(function (): void {
    Route::middleware('permission:administration.manage')->prefix('administration')->group(function (): void {
        Route::get('/password-policy', [PasswordPolicyController::class, 'show']);
        Route::put('/password-policy', [PasswordPolicyController::class, 'update']);
    });

    Route::middleware('permission:users.manage')->prefix('security')->group(function (): void {
        Route::get('/roles', [SecurityUserController::class, 'roles']);
        Route::get('/employees/search', [SecurityUserController::class, 'searchEmployees']);
        Route::get('/users', [SecurityUserController::class, 'index']);
        Route::post('/users', [SecurityUserController::class, 'store']);
        Route::get('/users/{user}', [SecurityUserController::class, 'show']);
        Route::put('/users/{user}', [SecurityUserController::class, 'update']);
        Route::post('/users/{user}/unlock', [SecurityUserController::class, 'unlock']);
        Route::post('/users/{user}/deactivate', [SecurityUserController::class, 'deactivate']);
        Route::delete('/users/{user}', [SecurityUserController::class, 'destroy']);
    });

    Route::middleware('permission:roles.manage')->prefix('security')->group(function (): void {
        Route::get('/rbac/roles', [SecurityRoleController::class, 'index']);
        Route::post('/rbac/roles', [SecurityRoleController::class, 'store']);
        Route::get('/rbac/roles/{role}', [SecurityRoleController::class, 'show']);
        Route::put('/rbac/roles/{role}', [SecurityRoleController::class, 'update']);
        Route::delete('/rbac/roles/{role}', [SecurityRoleController::class, 'destroy']);
        Route::get('/rbac/permissions', [SecurityRoleController::class, 'permissions']);
    });

    Route::middleware('permission:audit.view')->prefix('audit')->group(function (): void {
        Route::get('/logs', [AuditLogController::class, 'index']);
        Route::get('/events', [AuditLogController::class, 'events']);
        Route::get('/logs/{log}', [AuditLogController::class, 'show']);
    });

    Route::middleware('permission:departments.manage')->prefix('departments')->group(function (): void {
        Route::get('/', [DepartmentController::class, 'index']);
        Route::post('/', [DepartmentController::class, 'store']);
        Route::get('/{department}', [DepartmentController::class, 'show']);
        Route::put('/{department}', [DepartmentController::class, 'update']);
        Route::delete('/{department}', [DepartmentController::class, 'destroy']);
    });

    Route::prefix('employees')->group(function (): void {
        Route::middleware('permission:employees.view')->group(function (): void {
            Route::get('/meta', [EmployeeController::class, 'meta']);
            Route::get('/', [EmployeeController::class, 'index']);
            Route::get('/{employee}', [EmployeeController::class, 'show']);
        });

        Route::middleware('permission:employees.manage')->group(function (): void {
            Route::post('/', [EmployeeController::class, 'store']);
            Route::put('/{employee}', [EmployeeController::class, 'update']);
            Route::post('/{employee}/deactivate', [EmployeeController::class, 'deactivate']);
            Route::delete('/{employee}', [EmployeeController::class, 'destroy']);
        });
    });
});
