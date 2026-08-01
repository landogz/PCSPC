<?php

namespace App\Repositories\Lookups;

use App\Models\LookupValue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class LookupRepository
{
    private const CACHE_TTL_SECONDS = 300;

    /**
     * @param  array{search?: string, type?: string, status?: string}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = LookupValue::query()->ordered();

        $type = trim((string) ($filters['type'] ?? ''));
        if ($type !== '') {
            $query->where('type', $type);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('code', 'like', "%{$search}%")
                    ->orWhere('label', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        return $query->paginate($perPage);
    }

    public function findByUuid(string $uuid): ?LookupValue
    {
        return LookupValue::query()->where('uuid', $uuid)->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): LookupValue
    {
        $lookup = LookupValue::query()->create($data);
        $this->forgetCache($lookup->type);

        return $lookup;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(LookupValue $lookup, array $data): LookupValue
    {
        $lookup->fill($data);
        $lookup->save();
        $this->forgetCache($lookup->type);

        return $lookup->fresh() ?? $lookup;
    }

    public function delete(LookupValue $lookup): void
    {
        $type = $lookup->type;
        $lookup->delete();
        $this->forgetCache($type);
    }

    /**
     * @return Collection<int, LookupValue>
     */
    public function activeByType(string $type): Collection
    {
        $rows = Cache::remember(
            $this->cacheKey($type),
            self::CACHE_TTL_SECONDS,
            static function () use ($type): array {
                return LookupValue::query()
                    ->ofType($type)
                    ->active()
                    ->ordered()
                    ->get(['uuid', 'type', 'code', 'label', 'sort_order', 'is_active', 'is_system'])
                    ->map(static fn (LookupValue $lookup): array => [
                        'uuid' => $lookup->uuid,
                        'type' => $lookup->type,
                        'code' => $lookup->code,
                        'label' => $lookup->label,
                        'sort_order' => $lookup->sort_order,
                        'is_active' => $lookup->is_active,
                        'is_system' => $lookup->is_system,
                    ])
                    ->all();
            }
        );

        if (! is_array($rows)) {
            Cache::forget($this->cacheKey($type));

            return LookupValue::query()
                ->ofType($type)
                ->active()
                ->ordered()
                ->get(['uuid', 'type', 'code', 'label', 'sort_order', 'is_active', 'is_system']);
        }

        return collect($rows)->map(static function (array $row): LookupValue {
            $lookup = new LookupValue;
            $lookup->forceFill($row);
            $lookup->exists = true;

            return $lookup;
        });
    }

    /**
     * @return array<string, int>
     */
    public function countsByType(): array
    {
        $rows = LookupValue::query()
            ->selectRaw('type, COUNT(*) as aggregate')
            ->groupBy('type')
            ->pluck('aggregate', 'type')
            ->all();

        $counts = [];
        foreach (array_keys(config('lookups.types', [])) as $type) {
            $counts[$type] = (int) ($rows[$type] ?? 0);
        }

        return $counts;
    }

    public function codeExists(string $type, string $code, ?string $exceptUuid = null): bool
    {
        $query = LookupValue::query()->where('type', $type)->where('code', $code);
        if ($exceptUuid !== null) {
            $query->where('uuid', '!=', $exceptUuid);
        }

        return $query->exists();
    }

    public function forgetCache(?string $type = null): void
    {
        if ($type !== null && $type !== '') {
            Cache::forget($this->cacheKey($type));

            return;
        }

        foreach (array_keys(config('lookups.types', [])) as $configuredType) {
            Cache::forget($this->cacheKey($configuredType));
        }
    }

    private function cacheKey(string $type): string
    {
        return 'lookups.active.'.$type;
    }
}
