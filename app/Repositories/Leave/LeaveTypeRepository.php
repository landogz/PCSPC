<?php

namespace App\Repositories\Leave;

use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Collection;

class LeaveTypeRepository
{
    /**
     * @return Collection<int, LeaveType>
     */
    public function all(bool $activeOnly = false): Collection
    {
        $query = LeaveType::query()->orderBy('sort_order')->orderBy('name');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    public function findByUuid(string $uuid): ?LeaveType
    {
        return LeaveType::query()->where('uuid', $uuid)->first();
    }

    public function findByCode(string $code): ?LeaveType
    {
        return LeaveType::query()->where('code', $code)->first();
    }

    public function firstAccruing(): ?LeaveType
    {
        return LeaveType::query()
            ->where('is_accruing', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): LeaveType
    {
        return LeaveType::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(LeaveType $type, array $data): LeaveType
    {
        $type->fill($data);
        $type->save();

        return $type->fresh();
    }

    public function delete(LeaveType $type): void
    {
        $type->delete();
    }

    public function hasUsage(LeaveType $type): bool
    {
        if ($type->balances()->exists()) {
            return true;
        }

        return $type->requests()->exists();
    }
}
