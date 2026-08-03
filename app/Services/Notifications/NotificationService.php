<?php

namespace App\Services\Notifications;

use App\Models\User;
use App\Models\UserNotification;
use App\Repositories\Notifications\NotificationRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function __construct(
        private readonly NotificationRepository $notifications,
    ) {}

    /**
     * Persist an in-app notification for a user (topbar + /modules/notifications).
     *
     * @param  array<string, mixed>  $meta
     */
    public function notify(
        User $user,
        string $type,
        string $title,
        ?string $body = null,
        ?string $actionUrl = null,
        array $meta = [],
    ): ?UserNotification {
        try {
            return $this->notifications->createForUser($user, [
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'action_url' => $actionUrl,
                'meta' => $meta === [] ? null : $meta,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Failed to create in-app notification.', [
                'user_id' => $user->uuid,
                'type' => $type,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  iterable<int, User>  $users
     * @param  array<string, mixed>  $meta
     * @return list<UserNotification>
     */
    public function notifyMany(
        iterable $users,
        string $type,
        string $title,
        ?string $body = null,
        ?string $actionUrl = null,
        array $meta = [],
    ): array {
        $created = [];

        foreach ($users as $user) {
            $notification = $this->notify($user, $type, $title, $body, $actionUrl, $meta);
            if ($notification !== null) {
                $created[] = $notification;
            }
        }

        return $created;
    }

    /**
     * @param  array{search?: string, type?: string, unread?: bool|string}  $filters
     */
    public function listForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->notifications->paginateForUser($user, $filters, $perPage);
    }

    /**
     * @return Collection<int, UserNotification>
     */
    public function recentForUser(User $user, int $limit = 8): Collection
    {
        return $this->notifications->recentForUser($user, $limit);
    }

    public function unreadCount(User $user): int
    {
        return $this->notifications->unreadCount($user);
    }

    public function findForUser(User $user, string $uuid): UserNotification
    {
        $notification = $this->notifications->findForUser($user, $uuid);

        if ($notification === null) {
            abort(404, 'Notification not found.');
        }

        return $notification;
    }

    public function markRead(User $user, string $uuid): UserNotification
    {
        return $this->notifications->markRead($this->findForUser($user, $uuid));
    }

    public function markAllRead(User $user): int
    {
        return $this->notifications->markAllRead($user);
    }

    /**
     * @return list<string>
     */
    public function typesForUser(User $user): array
    {
        return $this->notifications->distinctTypesForUser($user);
    }
}
