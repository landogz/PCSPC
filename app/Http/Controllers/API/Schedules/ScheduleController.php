<?php

namespace App\Http\Controllers\API\Schedules;

use App\Http\Controllers\Controller;
use App\Http\Requests\Schedules\StoreScheduleRequest;
use App\Http\Requests\Schedules\UpdateScheduleRequest;
use App\Http\Resources\Schedules\ScheduleResource;
use App\Services\Schedules\ScheduleService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function __construct(
        private readonly ScheduleService $schedules,
    ) {}

    public function meta(): JsonResponse
    {
        return ApiResponse::success('Schedule meta retrieved.', [
            'shifts' => $this->schedules->shiftOptions(),
            'departments' => $this->schedules->departmentOptions(),
            'assignee_types' => [
                ['code' => 'employee', 'label' => 'Employee'],
                ['code' => 'department', 'label' => 'Department'],
            ],
            'days_of_week' => [
                ['code' => 1, 'label' => 'Mon'],
                ['code' => 2, 'label' => 'Tue'],
                ['code' => 3, 'label' => 'Wed'],
                ['code' => 4, 'label' => 'Thu'],
                ['code' => 5, 'label' => 'Fri'],
                ['code' => 6, 'label' => 'Sat'],
                ['code' => 7, 'label' => 'Sun'],
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 10), 1), 100);

        $paginator = $this->schedules->list([
            'search' => (string) $request->query('search', ''),
            'status' => (string) $request->query('status', ''),
            'shift_id' => (string) $request->query('shift_id', ''),
            'assignee_type' => (string) $request->query('assignee_type', ''),
            'employee_id' => (string) $request->query('employee_id', ''),
            'department_id' => (string) $request->query('department_id', ''),
            'effective' => (string) $request->query('effective', ''),
        ], $perPage);

        return ApiResponse::success('Schedules retrieved.', [
            'items' => ScheduleResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreScheduleRequest $request): JsonResponse
    {
        $schedule = $this->schedules->create(
            $request->validated(),
            $request->user()?->id,
        );

        return ApiResponse::success('Schedule assigned.', [
            'schedule' => (new ScheduleResource($schedule))->resolve(),
        ], 201);
    }

    public function show(string $schedule): JsonResponse
    {
        $model = $this->schedules->find($schedule);

        return ApiResponse::success('Schedule retrieved.', [
            'schedule' => (new ScheduleResource($model))->resolve(),
        ]);
    }

    public function update(UpdateScheduleRequest $request, string $schedule): JsonResponse
    {
        $model = $this->schedules->update($schedule, $request->validated());

        return ApiResponse::success('Schedule updated.', [
            'schedule' => (new ScheduleResource($model))->resolve(),
        ]);
    }

    public function destroy(string $schedule): JsonResponse
    {
        $this->schedules->delete($schedule);

        return ApiResponse::success('Schedule deleted.');
    }
}
