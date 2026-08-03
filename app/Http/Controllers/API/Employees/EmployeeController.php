<?php

namespace App\Http\Controllers\API\Employees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\StoreEmployeeRequest;
use App\Http\Requests\Employees\UpdateEmployeeRequest;
use App\Http\Resources\Employees\EmployeeResource;
use App\Services\Employees\EmployeeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeController extends Controller
{
    /**
     * Inject the employee service.
     */
    public function __construct(
        private readonly EmployeeService $employees,
    ) {}

    /**
     * Return form metadata such as departments, statuses, and lookup options.
     */
    public function meta(): JsonResponse
    {
        return ApiResponse::success('Employee meta retrieved.', $this->employees->meta());
    }

    /**
     * Search employees by name, email, or employee number for typeahead pickers.
     */
    public function search(Request $request): JsonResponse
    {
        $items = $this->employees->searchLookup(
            (string) $request->query('search', ''),
            min(max((int) $request->integer('limit', 15), 1), 30),
        );

        return ApiResponse::success('Employees found.', [
            'items' => $items,
        ]);
    }

    /**
     * List employees with search, status, and department filters and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 10), 1), 100);
        $reveal = $request->user()?->hasPermission('employees.manage') ?? false;

        $paginator = $this->employees->list([
            'search' => (string) $request->query('search', ''),
            'status' => (string) $request->query('status', ''),
            'department' => (string) $request->query('department', ''),
        ], $perPage);

        $items = $paginator->getCollection()->map(
            fn ($employee) => (new EmployeeResource($employee, false))->resolve()
        )->values()->all();

        return ApiResponse::success('Employees retrieved.', [
            'items' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'can_manage' => $reveal,
        ]);
    }

    /**
     * Export filtered employees to an Excel spreadsheet download.
     */
    public function export(Request $request): StreamedResponse
    {
        $reveal = $request->user()?->hasPermission('employees.manage') ?? false;
        $result = $this->employees->export([
            'search' => (string) $request->query('search', ''),
            'status' => (string) $request->query('status', ''),
            'department' => (string) $request->query('department', ''),
        ], $reveal);

        return response()->streamDownload(function () use ($result): void {
            echo $result['binary'];
        }, $result['filename'], [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Create a new employee record and provision an initial login account.
     */
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $payload = collect($request->validated())->except(['photo'])->all();
        $result = $this->employees->create($payload, $request->file('photo'));

        $message = $result['welcome_email_sent']
            ? 'Employee created. Login credentials were emailed to the employee.'
            : 'Employee created and login provisioned.';

        return ApiResponse::success($message, [
            'employee' => (new EmployeeResource($result['employee'], true))->resolve(),
            'temporary_password' => $result['temporary_password'],
            'welcome_email_sent' => $result['welcome_email_sent'],
        ], 201);
    }

    /**
     * Return a single employee profile by UUID with field visibility based on permissions.
     */
    public function show(Request $request, string $employee): JsonResponse
    {
        $model = $this->employees->find($employee);
        $reveal = $request->user()?->hasPermission('employees.manage') ?? false;

        return ApiResponse::success('Employee retrieved.', [
            'employee' => (new EmployeeResource($model, $reveal))->resolve(),
        ]);
    }

    /**
     * Update employee details and optionally replace or remove the profile photo.
     */
    public function update(UpdateEmployeeRequest $request, string $employee): JsonResponse
    {
        $payload = collect($request->validated())->except(['photo', 'remove_photo'])->all();
        $result = $this->employees->update(
            $employee,
            $payload,
            $request->file('photo'),
            (bool) $request->boolean('remove_photo'),
        );

        return ApiResponse::success('Employee updated.', [
            'employee' => (new EmployeeResource($result['employee'], true))->resolve(),
            'temporary_password' => $result['temporary_password'],
        ]);
    }

    /**
     * Mark an employee as inactive without deleting their record.
     */
    public function deactivate(string $employee): JsonResponse
    {
        $model = $this->employees->deactivate($employee);

        return ApiResponse::success('Employee deactivated.', [
            'employee' => (new EmployeeResource($model, true))->resolve(),
        ]);
    }

    /**
     * Permanently delete an employee record.
     */
    public function destroy(string $employee): JsonResponse
    {
        $this->employees->delete($employee);

        return ApiResponse::success('Employee deleted.');
    }
}
