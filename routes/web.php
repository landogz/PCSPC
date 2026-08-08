<?php

use App\Http\Controllers\API\Administration\PasswordPolicyController;
use App\Http\Controllers\API\Administration\SystemParameterController;
use App\Http\Controllers\API\Audit\AuditLogController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\Profile\ProfileController;
use App\Http\Controllers\API\Dashboard\DashboardController;
use App\Http\Controllers\API\Documents\DocumentController;
use App\Http\Controllers\API\Departments\DepartmentController;
use App\Http\Controllers\API\Lookups\LookupController;
use App\Http\Controllers\API\Notifications\NotificationController;
use App\Http\Controllers\API\Search\SearchController;
use App\Http\Controllers\API\Holidays\HolidayController;
use App\Http\Controllers\API\Leave\LeaveAccrualController;
use App\Http\Controllers\API\Leave\LeaveBalanceController;
use App\Http\Controllers\API\Leave\LeaveRequestController;
use App\Http\Controllers\API\Leave\LeaveTypeController;
use App\Http\Controllers\API\Overtime\OvertimeRequestController;
use App\Http\Controllers\API\Workflow\WorkflowController;
use App\Http\Controllers\API\Schedules\ScheduleController;
use App\Http\Controllers\API\Shifts\ShiftController;
use App\Http\Controllers\API\Employees\EmployeeController;
use App\Http\Controllers\API\Employees\EmployeeDependentController;
use App\Http\Controllers\API\Employees\EmployeeEducationController;
use App\Http\Controllers\API\Employees\EmployeeCareerHistoryController;
use App\Http\Controllers\API\Employees\EmployeeEmploymentHistoryController;
use App\Http\Controllers\API\Security\RoleController as SecurityRoleController;
use App\Http\Controllers\API\Security\UserController as SecurityUserController;
use App\Http\Controllers\Web\ApiDocsController;
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

Route::get('/api-docs', [ApiDocsController::class, 'index'])->name('api-docs');
Route::get('/api-docs.json', [ApiDocsController::class, 'json'])
    ->middleware('throttle:60,1')
    ->name('api-docs.json');

Route::middleware('guest')->group(function (): void {
    Route::view('/login', 'auth.login')->name('login');
});

Route::middleware(['auth', 'password.current'])->group(function (): void {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::get('/modules/{module}', [ModulePageController::class, 'show'])
        ->whereIn('module', Navigation::moduleKeys())
        ->name('modules.show');

    Route::get('/docs/{doc}', [DocsPageController::class, 'show'])
        ->whereIn('doc', ['modules', 'project-plan', 'flowcharts'])
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

    Route::post('/mfa/resend', [AuthController::class, 'resendMfa'])
        ->middleware('throttle:'.config('auth_security.login_throttle', '5,1'));

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/password/policy', [AuthController::class, 'passwordPolicy']);
        Route::post('/password', [AuthController::class, 'changePassword']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-others', [AuthController::class, 'logoutOthers'])
            ->middleware('password.current');

        Route::middleware('password.current')->prefix('profile')->group(function (): void {
            Route::get('/', [ProfileController::class, 'show']);
            Route::put('/', [ProfileController::class, 'update']);
            Route::post('/avatar', [ProfileController::class, 'uploadAvatar']);
            Route::delete('/avatar', [ProfileController::class, 'removeAvatar']);
        });
    });
});

Route::prefix('api/v1')->middleware(['auth:sanctum', 'password.current'])->group(function (): void {
    Route::middleware('permission:dashboard.view')->prefix('dashboard')->group(function (): void {
        Route::get('/stats', [DashboardController::class, 'stats']);
    });

    // Own inbox — any authenticated user (topbar + /modules/notifications)
    Route::prefix('notifications')->group(function (): void {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/recent', [NotificationController::class, 'recent']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::get('/types', [NotificationController::class, 'types']);
        Route::post('/mark-all-read', [NotificationController::class, 'markAllRead']);
        Route::get('/{notification}', [NotificationController::class, 'show']);
        Route::post('/{notification}/read', [NotificationController::class, 'markRead']);
    });

    Route::get('/search', SearchController::class)->middleware('throttle:60,1');

    Route::middleware('permission:administration.manage')->prefix('administration')->group(function (): void {
        Route::get('/system-parameters', [SystemParameterController::class, 'show']);
        Route::put('/system-parameters', [SystemParameterController::class, 'update']);
        Route::post('/system-parameters/logo', [SystemParameterController::class, 'uploadLogo']);
        Route::delete('/system-parameters/logo', [SystemParameterController::class, 'removeLogo']);
    });

    Route::middleware('permission:users.manage')->prefix('security')->group(function (): void {
        Route::get('/password-policy', [PasswordPolicyController::class, 'show']);
        Route::put('/password-policy', [PasswordPolicyController::class, 'update']);
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

    // Dropdown options for any authenticated user (Employee 201, Documents, etc.)
    Route::get('/lookups/options', [LookupController::class, 'options'])->middleware('throttle:60,1');

    Route::middleware('permission:administration.manage')->group(function (): void {
        Route::prefix('lookups')->group(function (): void {
            Route::get('/types', [LookupController::class, 'types']);
            Route::get('/', [LookupController::class, 'index']);
            Route::post('/', [LookupController::class, 'store']);
            Route::get('/{lookup}', [LookupController::class, 'show']);
            Route::put('/{lookup}', [LookupController::class, 'update']);
            Route::delete('/{lookup}', [LookupController::class, 'destroy']);
        });

        Route::prefix('holidays')->group(function (): void {
            Route::get('/', [HolidayController::class, 'index']);
            Route::post('/', [HolidayController::class, 'store']);
            Route::get('/{holiday}', [HolidayController::class, 'show']);
            Route::put('/{holiday}', [HolidayController::class, 'update']);
            Route::delete('/{holiday}', [HolidayController::class, 'destroy']);
        });

        Route::prefix('shifts')->group(function (): void {
            Route::get('/', [ShiftController::class, 'index']);
            Route::post('/', [ShiftController::class, 'store']);
            Route::get('/{shift}', [ShiftController::class, 'show']);
            Route::put('/{shift}', [ShiftController::class, 'update']);
            Route::delete('/{shift}', [ShiftController::class, 'destroy']);
        });

        Route::prefix('schedules')->group(function (): void {
            Route::get('/meta', [ScheduleController::class, 'meta']);
            Route::get('/print', [ScheduleController::class, 'print'])->middleware('throttle:30,1');
            Route::get('/', [ScheduleController::class, 'index']);
            Route::post('/', [ScheduleController::class, 'store']);
            Route::get('/{schedule}', [ScheduleController::class, 'show']);
            Route::put('/{schedule}', [ScheduleController::class, 'update']);
            Route::delete('/{schedule}', [ScheduleController::class, 'destroy']);
        });
    });

    Route::prefix('documents')->group(function (): void {
        Route::middleware('permission:documents.view')->group(function (): void {
            Route::get('/', [DocumentController::class, 'index']);
            Route::get('/stats', [DocumentController::class, 'stats']);
            Route::get('/{document}/download', [DocumentController::class, 'download'])->middleware('throttle:60,1');
            Route::get('/{document}/preview', [DocumentController::class, 'preview'])->middleware('throttle:60,1');
            Route::get('/{document}/versions/{version}/download', [DocumentController::class, 'downloadVersion'])->middleware('throttle:60,1');
            Route::get('/{document}', [DocumentController::class, 'show']);
        });

        Route::middleware('permission:documents.manage')->group(function (): void {
            Route::post('/', [DocumentController::class, 'store']);
            Route::post('/bulk-delete', [DocumentController::class, 'bulkDestroy']);
            Route::post('/bulk-category', [DocumentController::class, 'bulkCategory']);
            Route::match(['put', 'post'], '/{document}', [DocumentController::class, 'update']);
            Route::delete('/{document}', [DocumentController::class, 'destroy']);
        });
    });

    Route::prefix('employees')->group(function (): void {
        Route::middleware('permission:employees.view')->group(function (): void {
            Route::get('/meta', [EmployeeController::class, 'meta']);
            Route::get('/search', [EmployeeController::class, 'search'])->middleware('throttle:60,1');
            Route::get('/export', [EmployeeController::class, 'export'])->middleware('throttle:30,1');
            Route::get('/', [EmployeeController::class, 'index']);
            Route::get('/{employee}/dependents', [EmployeeDependentController::class, 'index']);
            Route::get('/{employee}/educations', [EmployeeEducationController::class, 'index']);
            Route::get('/{employee}/employment-history', [EmployeeEmploymentHistoryController::class, 'index']);
            Route::get('/{employee}/career-history', [EmployeeCareerHistoryController::class, 'index']);
            Route::get('/{employee}/leave-balances', [LeaveBalanceController::class, 'forEmployee']);
            Route::get('/{employee}', [EmployeeController::class, 'show']);
        });

        Route::middleware('permission:employees.manage')->group(function (): void {
            Route::post('/', [EmployeeController::class, 'store']);
            Route::post('/{employee}/dependents', [EmployeeDependentController::class, 'store']);
            Route::put('/{employee}/dependents/{dependent}', [EmployeeDependentController::class, 'update']);
            Route::delete('/{employee}/dependents/{dependent}', [EmployeeDependentController::class, 'destroy']);
            Route::post('/{employee}/educations', [EmployeeEducationController::class, 'store']);
            Route::put('/{employee}/educations/{education}', [EmployeeEducationController::class, 'update']);
            Route::delete('/{employee}/educations/{education}', [EmployeeEducationController::class, 'destroy']);
            Route::post('/{employee}/employment-history', [EmployeeEmploymentHistoryController::class, 'store']);
            Route::put('/{employee}/employment-history/{history}', [EmployeeEmploymentHistoryController::class, 'update']);
            Route::delete('/{employee}/employment-history/{history}', [EmployeeEmploymentHistoryController::class, 'destroy']);
            Route::post('/{employee}/career-history', [EmployeeCareerHistoryController::class, 'store']);
            Route::put('/{employee}/career-history/{history}', [EmployeeCareerHistoryController::class, 'update']);
            Route::delete('/{employee}/career-history/{history}', [EmployeeCareerHistoryController::class, 'destroy']);
            Route::post('/{employee}/deactivate', [EmployeeController::class, 'deactivate']);
            Route::match(['put', 'post'], '/{employee}', [EmployeeController::class, 'update']);
            Route::delete('/{employee}', [EmployeeController::class, 'destroy']);
        });
    });

    Route::prefix('leave')->group(function (): void {
        Route::middleware('permission:leave.file,leave.approve,leave.manage')->group(function (): void {
            Route::get('/types', [LeaveTypeController::class, 'index']);
        });

        Route::middleware('permission:leave.file')->group(function (): void {
            Route::get('/requests/mine', [LeaveRequestController::class, 'mine']);
            Route::post('/requests', [LeaveRequestController::class, 'store'])
                ->middleware('throttle:30,1');
        });

        Route::middleware('permission:leave.file,leave.manage')->group(function (): void {
            Route::post('/requests/{leaveRequest}/cancel', [LeaveRequestController::class, 'cancel']);
        });

        Route::middleware('permission:leave.approve,leave.manage')->group(function (): void {
            Route::get('/balances', [LeaveBalanceController::class, 'index']);
            Route::get('/requests', [LeaveRequestController::class, 'index']);
            Route::get('/requests/{leaveRequest}', [LeaveRequestController::class, 'show']);
            Route::post('/requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve']);
            Route::post('/requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject']);
        });

        Route::middleware('permission:leave.manage')->group(function (): void {
            Route::post('/types', [LeaveTypeController::class, 'store']);
            Route::get('/types/{leaveType}', [LeaveTypeController::class, 'show']);
            Route::put('/types/{leaveType}', [LeaveTypeController::class, 'update']);
            Route::delete('/types/{leaveType}', [LeaveTypeController::class, 'destroy']);
            Route::post('/balances/adjust', [LeaveBalanceController::class, 'adjust']);
            Route::post('/accruals/run', [LeaveAccrualController::class, 'run'])
                ->middleware('throttle:20,1');
        });
    });

    Route::prefix('overtime')->group(function (): void {
        Route::middleware('permission:ot.file')->group(function (): void {
            Route::get('/requests/mine', [OvertimeRequestController::class, 'mine']);
            Route::post('/requests', [OvertimeRequestController::class, 'store'])
                ->middleware('throttle:30,1');
        });

        Route::middleware('permission:ot.file,ot.manage')->group(function (): void {
            Route::post('/requests/{overtimeRequest}/cancel', [OvertimeRequestController::class, 'cancel']);
        });

        Route::middleware('permission:ot.approve,ot.manage')->group(function (): void {
            Route::get('/requests', [OvertimeRequestController::class, 'index']);
            Route::get('/requests/{overtimeRequest}', [OvertimeRequestController::class, 'show']);
            Route::post('/requests/{overtimeRequest}/approve', [OvertimeRequestController::class, 'approve']);
            Route::post('/requests/{overtimeRequest}/reject', [OvertimeRequestController::class, 'reject']);
        });
    });

    Route::prefix('workflow')->middleware('permission:ot.approve,ot.manage,leave.approve,leave.manage')->group(function (): void {
        Route::get('/definitions', [WorkflowController::class, 'definitions']);
        Route::get('/inbox', [WorkflowController::class, 'inbox']);
        Route::get('/instances', [WorkflowController::class, 'instances']);
        Route::get('/instances/{instance}', [WorkflowController::class, 'show']);
        Route::post('/instances/{instance}/approve', [WorkflowController::class, 'approve']);
        Route::post('/instances/{instance}/reject', [WorkflowController::class, 'reject']);
    });
});
