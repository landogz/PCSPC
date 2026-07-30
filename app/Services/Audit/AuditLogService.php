<?php

namespace App\Services\Audit;

use App\Models\AuthActivityLog;
use App\Repositories\Audit\AuditLogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AuditLogService
{
    public function __construct(
        private readonly AuditLogRepository $logs,
    ) {}

    /**
     * @param  array{search?: string, event?: string, from?: string, to?: string}  $filters
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->logs->paginate($filters, $perPage);
    }

    public function find(string $uuid): AuthActivityLog
    {
        $log = $this->logs->findByUuid($uuid);

        if ($log === null) {
            abort(404, 'Audit log not found.');
        }

        return $log;
    }

    /**
     * @return list<string>
     */
    public function events(): array
    {
        return $this->logs->distinctEvents();
    }
}
