<?php

namespace App\Http\Controllers\API\Workflow;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workflow\DecideWorkflowInstanceRequest;
use App\Http\Resources\Workflow\WorkflowDefinitionResource;
use App\Http\Resources\Workflow\WorkflowInstanceResource;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Services\Leave\LeaveRequestService;
use App\Services\Overtime\OvertimeRequestService;
use App\Services\Workflow\WorkflowService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class WorkflowController extends Controller
{
    public function __construct(
        private readonly WorkflowService $workflows,
        private readonly OvertimeRequestService $overtimeRequests,
        private readonly LeaveRequestService $leaveRequests,
    ) {}

    public function definitions(): JsonResponse
    {
        $items = $this->workflows->listDefinitions();

        return ApiResponse::success('Workflow definitions retrieved.', [
            'items' => WorkflowDefinitionResource::collection($items)->resolve(),
        ]);
    }

    public function inbox(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 10), 1), 100);

        $paginator = $this->workflows->listInstances($request->user(), [
            'search' => (string) $request->query('search', ''),
            'definition' => (string) $request->query('definition', ''),
            'inbox' => true,
        ], $perPage);

        return ApiResponse::success('Workflow inbox retrieved.', [
            'items' => WorkflowInstanceResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function instances(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 10), 1), 100);

        $paginator = $this->workflows->listInstances($request->user(), [
            'search' => (string) $request->query('search', ''),
            'status' => (string) $request->query('status', ''),
            'definition' => (string) $request->query('definition', ''),
            'inbox' => false,
        ], $perPage);

        return ApiResponse::success('Workflow instances retrieved.', [
            'items' => WorkflowInstanceResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(string $instance): JsonResponse
    {
        $model = $this->workflows->findInstance($instance);

        return ApiResponse::success('Workflow instance retrieved.', [
            'instance' => (new WorkflowInstanceResource($model))->resolve(),
        ]);
    }

    public function approve(DecideWorkflowInstanceRequest $request, string $instance): JsonResponse
    {
        try {
            $wf = $this->workflows->findInstance($instance);
            $subject = $wf->subject;
            $notes = $request->validated();

            if ($subject instanceof OvertimeRequest) {
                $ot = $this->overtimeRequests->approve($request->user(), $subject->uuid, $notes);

                return ApiResponse::success(
                    $ot->status === 'approved'
                        ? 'Request fully approved.'
                        : 'Step approved; awaiting next approver.',
                    [
                        'instance' => (new WorkflowInstanceResource($ot->workflowInstance))->resolve(),
                    ]
                );
            }

            if ($subject instanceof LeaveRequest) {
                $leave = $this->leaveRequests->approve($request->user(), $subject->uuid, [
                    'approver_notes' => $notes['notes'] ?? null,
                ]);

                return ApiResponse::success(
                    $leave->status === 'approved'
                        ? 'Request fully approved.'
                        : 'Step approved; awaiting next approver.',
                    [
                        'instance' => (new WorkflowInstanceResource($leave->workflowInstance))->resolve(),
                    ]
                );
            }

            $model = $this->workflows->approve($request->user(), $instance, $notes);
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        return ApiResponse::success('Workflow step approved.', [
            'instance' => (new WorkflowInstanceResource($model))->resolve(),
        ]);
    }

    public function reject(DecideWorkflowInstanceRequest $request, string $instance): JsonResponse
    {
        try {
            $wf = $this->workflows->findInstance($instance);
            $subject = $wf->subject;
            $notes = $request->validated();

            if ($subject instanceof OvertimeRequest) {
                $ot = $this->overtimeRequests->reject($request->user(), $subject->uuid, $notes);

                return ApiResponse::success('Request rejected.', [
                    'instance' => (new WorkflowInstanceResource($ot->workflowInstance))->resolve(),
                ]);
            }

            if ($subject instanceof LeaveRequest) {
                $leave = $this->leaveRequests->reject($request->user(), $subject->uuid, [
                    'approver_notes' => $notes['notes'] ?? null,
                ]);

                return ApiResponse::success('Request rejected.', [
                    'instance' => (new WorkflowInstanceResource($leave->workflowInstance))->resolve(),
                ]);
            }

            $model = $this->workflows->reject($request->user(), $instance, $notes);
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        return ApiResponse::success('Workflow instance rejected.', [
            'instance' => (new WorkflowInstanceResource($model))->resolve(),
        ]);
    }
}
