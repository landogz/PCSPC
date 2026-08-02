<?php

namespace App\Http\Controllers\API\Audit;

use App\Http\Controllers\Controller;
use App\Http\Resources\Audit\AuditLogResource;
use App\Services\Audit\AuditLogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Inject the audit log service.
     */
    public function __construct(
        private readonly AuditLogService $logs,
    ) {}

    /**
     * List audit log entries with search, event, date filters, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);

        $paginator = $this->logs->list([
            'search' => (string) $request->query('search', ''),
            'event' => (string) $request->query('event', ''),
            'from' => (string) $request->query('from', ''),
            'to' => (string) $request->query('to', ''),
        ], $perPage);

        return ApiResponse::success('Audit logs retrieved.', [
            'items' => AuditLogResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Return a single audit log entry by UUID.
     */
    public function show(string $log): JsonResponse
    {
        $model = $this->logs->find($log);

        return ApiResponse::success('Audit log retrieved.', [
            'log' => (new AuditLogResource($model))->resolve(),
        ]);
    }

    /**
     * Return distinct audit event names available for filtering.
     */
    public function events(): JsonResponse
    {
        return ApiResponse::success('Audit events retrieved.', [
            'events' => $this->logs->events(),
        ]);
    }
}
