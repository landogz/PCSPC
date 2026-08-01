<?php

namespace App\Repositories\Shifts;

use App\Models\Shift;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ShiftRepository
{
    /**
     * @param  array{search?: string, status?: string}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Shift::query()->orderBy('code');

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $status = $filters['status'] ?? null;
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        return $query->paginate($perPage);
    }

    public function findByUuid(string $uuid): ?Shift
    {
        return Shift::query()->where('uuid', $uuid)->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Shift
    {
        return Shift::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Shift $shift, array $data): Shift
    {
        $shift->fill($data);
        $shift->save();

        return $shift->fresh();
    }

    public function delete(Shift $shift): void
    {
        $shift->delete();
    }
}
