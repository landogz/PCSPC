<?php

namespace App\Http\Controllers\API\Notifications;

use App\Http\Controllers\Controller;
use App\Http\Resources\Notifications\NotificationResource;
use App\Services\Notifications\NotificationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Inject the notification service.
     */
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    /**
     * List the authenticated user's notifications (paginated).
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);
        $user = $request->user();

        $paginator = $this->notifications->listForUser($user, [
            'search' => (string) $request->query('search', ''),
            'type' => (string) $request->query('type', ''),
            'unread' => $request->query('unread'),
        ], $perPage);

        return ApiResponse::success('Notifications retrieved.', [
            'items' => NotificationResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'unread_count' => $this->notifications->unreadCount($user),
            ],
        ]);
    }

    /**
     * Return recent notifications for the topbar dropdown.
     */
    public function recent(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = min(max((int) $request->integer('limit', 8), 1), 20);

        return ApiResponse::success('Recent notifications retrieved.', [
            'items' => NotificationResource::collection(
                $this->notifications->recentForUser($user, $limit)
            )->resolve(),
            'unread_count' => $this->notifications->unreadCount($user),
        ]);
    }

    /**
     * Return unread notification count for the topbar badge.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return ApiResponse::success('Unread count retrieved.', [
            'unread_count' => $this->notifications->unreadCount($request->user()),
        ]);
    }

    /**
     * Return distinct notification types for the current user (filter dropdown).
     */
    public function types(Request $request): JsonResponse
    {
        return ApiResponse::success('Notification types retrieved.', [
            'types' => $this->notifications->typesForUser($request->user()),
        ]);
    }

    /**
     * Return a single notification owned by the authenticated user.
     */
    public function show(Request $request, string $notification): JsonResponse
    {
        $model = $this->notifications->findForUser($request->user(), $notification);

        return ApiResponse::success('Notification retrieved.', [
            'notification' => (new NotificationResource($model))->resolve(),
        ]);
    }

    /**
     * Mark one notification as read.
     */
    public function markRead(Request $request, string $notification): JsonResponse
    {
        $model = $this->notifications->markRead($request->user(), $notification);

        return ApiResponse::success('Notification marked as read.', [
            'notification' => (new NotificationResource($model))->resolve(),
            'unread_count' => $this->notifications->unreadCount($request->user()),
        ]);
    }

    /**
     * Mark all of the authenticated user's notifications as read.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $updated = $this->notifications->markAllRead($request->user());

        return ApiResponse::success('All notifications marked as read.', [
            'updated' => $updated,
            'unread_count' => 0,
        ]);
    }
}
