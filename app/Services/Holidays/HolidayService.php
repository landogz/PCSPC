<?php

namespace App\Services\Holidays;

use App\Models\Holiday;
use App\Repositories\Holidays\HolidayRepository;
use App\Services\Audit\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class HolidayService
{
    public function __construct(
        private readonly HolidayRepository $holidays,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{search?: string, status?: string, type?: string, year?: string}  $filters
     */
    public function list(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->holidays->paginate($filters, $perPage);
    }

    /**
     * @return list<string>
     */
    public function types(): array
    {
        return Holiday::TYPES;
    }

    public function find(string $uuid): Holiday
    {
        $holiday = $this->holidays->findByUuid($uuid);
        if ($holiday === null) {
            abort(404, 'Holiday not found.');
        }

        return $holiday;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): Holiday
    {
        $holiday = $this->holidays->create($this->mapPayload($payload));

        $this->audit->log('holiday.created', [
            'holiday_id' => $holiday->uuid,
            'name' => $holiday->name,
            'holiday_date' => $holiday->holiday_date?->toDateString(),
            'type' => $holiday->type,
        ]);

        return $holiday;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(string $uuid, array $payload): Holiday
    {
        $holiday = $this->find($uuid);
        $updated = $this->holidays->update($holiday, $this->mapPayload($payload, $holiday));

        $this->audit->log('holiday.updated', [
            'holiday_id' => $updated->uuid,
            'name' => $updated->name,
            'holiday_date' => $updated->holiday_date?->toDateString(),
            'type' => $updated->type,
            'is_active' => (bool) $updated->is_active,
        ]);

        return $updated;
    }

    public function delete(string $uuid): void
    {
        $holiday = $this->find($uuid);
        $meta = [
            'holiday_id' => $holiday->uuid,
            'name' => $holiday->name,
            'holiday_date' => $holiday->holiday_date?->toDateString(),
        ];

        $this->holidays->delete($holiday);
        $this->audit->log('holiday.deleted', $meta);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function mapPayload(array $payload, ?Holiday $existing = null): array
    {
        return [
            'name' => trim((string) ($payload['name'] ?? '')),
            'holiday_date' => $payload['holiday_date'] ?? null,
            'type' => (string) ($payload['type'] ?? 'regular'),
            'is_recurring' => (bool) ($payload['is_recurring'] ?? false),
            'is_double_pay' => (bool) ($payload['is_double_pay'] ?? false),
            'paid_hours' => (int) ($payload['paid_hours'] ?? 8),
            'description' => $this->nullableString($payload['description'] ?? null),
            'is_active' => (bool) ($payload['is_active'] ?? $existing?->is_active ?? true),
        ];
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
