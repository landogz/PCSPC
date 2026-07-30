<?php

namespace App\Repositories\Audit;

use App\Models\AuthActivityLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AuditLogRepository
{
    /**
     * @param  array{search?: string, event?: string, from?: string, to?: string}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = AuthActivityLog::query()
            ->with(['user:id,uuid,name,email,employee_number'])
            ->latest('id');

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    public function findByUuid(string $uuid): ?AuthActivityLog
    {
        return AuthActivityLog::query()
            ->with(['user:id,uuid,name,email,employee_number'])
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * @return list<string>
     */
    public function distinctEvents(): array
    {
        return AuthActivityLog::query()
            ->whereNotNull('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event')
            ->all();
    }

    /**
     * @param  Builder<AuthActivityLog>  $query
     * @param  array{search?: string, event?: string, from?: string, to?: string}  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('email', 'like', "%{$search}%")
                    ->orWhere('event', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function (Builder $user) use ($search): void {
                        $user->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('employee_number', 'like', "%{$search}%");
                    });
            });
        }

        $event = trim((string) ($filters['event'] ?? ''));
        if ($event !== '') {
            $query->where('event', $event);
        }

        $from = $filters['from'] ?? null;
        if (is_string($from) && $from !== '') {
            $query->whereDate('created_at', '>=', $from);
        }

        $to = $filters['to'] ?? null;
        if (is_string($to) && $to !== '') {
            $query->whereDate('created_at', '<=', $to);
        }
    }
}
