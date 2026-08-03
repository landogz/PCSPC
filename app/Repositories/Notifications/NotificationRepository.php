<?php

namespace App\Repositories\Notifications;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class NotificationRepository
{
    /**
     * @param  array{search?: string, type?: string, unread?: bool|string}  $filters
     */
    public function paginateForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = UserNotification::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at');

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
            });
        }

        $type = trim((string) ($filters['type'] ?? ''));
        if ($type !== '') {
            $query->where('type', $type);
        }

        $unread = $filters['unread'] ?? null;
        if ($unread === true || $unread === '1' || $unread === 'true') {
            $query->whereNull('read_at');
        }

        return $query->paginate($perPage);
    }

    /**
     * @return Collection<int, UserNotification>
     */
    public function recentForUser(User $user, int $limit = 8): Collection
    {
        return UserNotification::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function unreadCount(User $user): int
    {
        return UserNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    public function findForUser(User $user, string $uuid): ?UserNotification
    {
        return UserNotification::query()
            ->where('user_id', $user->id)
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * @param  array{type: string, title: string, body?: ?string, action_url?: ?string, meta?: array<string, mixed>}  $payload
     */
    public function createForUser(User $user, array $payload): UserNotification
    {
        return UserNotification::query()->create([
            'user_id' => $user->id,
            'type' => $payload['type'],
            'title' => $payload['title'],
            'body' => $payload['body'] ?? null,
            'action_url' => $payload['action_url'] ?? null,
            'meta' => $payload['meta'] ?? null,
        ]);
    }

    public function markRead(UserNotification $notification): UserNotification
    {
        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        return $notification->refresh();
    }

    public function markAllRead(User $user): int
    {
        return UserNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * @return list<string>
     */
    public function distinctTypesForUser(User $user): array
    {
        return UserNotification::query()
            ->where('user_id', $user->id)
            ->distinct()
            ->orderBy('type')
            ->pluck('type')
            ->all();
    }
}
