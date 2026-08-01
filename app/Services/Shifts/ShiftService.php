<?php

namespace App\Services\Shifts;

use App\Models\Shift;
use App\Repositories\Shifts\ShiftRepository;
use App\Services\Audit\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ShiftService
{
    public function __construct(
        private readonly ShiftRepository $shifts,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{search?: string, status?: string}  $filters
     */
    public function list(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->shifts->paginate($filters, $perPage);
    }

    public function find(string $uuid): Shift
    {
        $shift = $this->shifts->findByUuid($uuid);
        if ($shift === null) {
            abort(404, 'Shift not found.');
        }

        return $shift;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): Shift
    {
        $shift = $this->shifts->create($this->mapPayload($payload));

        $this->audit->log('shift.created', [
            'shift_id' => $shift->uuid,
            'code' => $shift->code,
            'name' => $shift->name,
        ]);

        return $shift;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(string $uuid, array $payload): Shift
    {
        $shift = $this->find($uuid);
        $updated = $this->shifts->update($shift, $this->mapPayload($payload, $shift));

        $this->audit->log('shift.updated', [
            'shift_id' => $updated->uuid,
            'code' => $updated->code,
            'name' => $updated->name,
            'is_active' => (bool) $updated->is_active,
        ]);

        return $updated;
    }

    public function delete(string $uuid): void
    {
        $shift = $this->find($uuid);
        $meta = [
            'shift_id' => $shift->uuid,
            'code' => $shift->code,
            'name' => $shift->name,
        ];

        $this->shifts->delete($shift);
        $this->audit->log('shift.deleted', $meta);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function mapPayload(array $payload, ?Shift $existing = null): array
    {
        $timeIn = $this->normalizeClock((string) ($payload['time_in'] ?? ''));
        $timeOut = $this->normalizeClock((string) ($payload['time_out'] ?? ''));
        $crossesMidnight = (bool) ($payload['crosses_midnight'] ?? false);

        if (! $crossesMidnight && $timeIn !== '' && $timeOut !== '' && $timeOut <= $timeIn) {
            $crossesMidnight = true;
        }

        return [
            'code' => strtoupper(trim((string) ($payload['code'] ?? ''))),
            'name' => trim((string) ($payload['name'] ?? '')),
            'time_in' => $timeIn,
            'time_out' => $timeOut,
            'break_minutes' => (int) ($payload['break_minutes'] ?? 60),
            'grace_minutes' => (int) ($payload['grace_minutes'] ?? 0),
            'crosses_midnight' => $crossesMidnight,
            'description' => $this->nullableString($payload['description'] ?? null),
            'is_active' => (bool) ($payload['is_active'] ?? $existing?->is_active ?? true),
        ];
    }

    private function normalizeClock(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $value) !== 1) {
            return $value;
        }

        [$hour, $minute] = explode(':', $value);

        return sprintf('%02d:%02d', (int) $hour, (int) $minute);
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
