<?php

namespace App\Repositories\Holidays;

use App\Models\Holiday;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class HolidayRepository
{
    /**
     * @param  array{search?: string, status?: string, type?: string, year?: string}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Holiday::query()->orderBy('holiday_date')->orderBy('name');

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $status = $filters['status'] ?? null;
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $type = trim((string) ($filters['type'] ?? ''));
        if ($type !== '') {
            $query->where('type', $type);
        }

        $year = trim((string) ($filters['year'] ?? ''));
        if ($year !== '' && ctype_digit($year)) {
            $query->whereYear('holiday_date', (int) $year);
        }

        return $query->paginate($perPage);
    }

    public function findByUuid(string $uuid): ?Holiday
    {
        return Holiday::query()->where('uuid', $uuid)->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Holiday
    {
        return Holiday::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Holiday $holiday, array $data): Holiday
    {
        $holiday->fill($data);
        $holiday->save();

        return $holiday->fresh();
    }

    public function delete(Holiday $holiday): void
    {
        $holiday->delete();
    }
}
