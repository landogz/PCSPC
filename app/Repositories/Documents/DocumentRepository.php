<?php

namespace App\Repositories\Documents;

use App\Models\EmployeeDocument;
use App\Models\EmployeeDocumentVersion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DocumentRepository
{
    /**
     * @param  array{search?: string, category?: string, employee_id?: string, expiry?: string}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = EmployeeDocument::query()
            ->with([
                'employee:id,uuid,employee_number,first_name,last_name,email',
                'uploader:id,name,email',
            ])
            ->withCount('versions')
            ->orderByDesc('created_at');

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * @param  array{search?: string, category?: string, employee_id?: string, expiry?: string}  $filters
     */
    public function applyFilters(Builder $query, array $filters = []): void
    {
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('original_name', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('employee', function (Builder $employee) use ($search): void {
                        $employee->where('employee_number', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $category = trim((string) ($filters['category'] ?? ''));
        if ($category !== '') {
            $query->where('category', $category);
        }

        $employeeUuid = trim((string) ($filters['employee_id'] ?? ''));
        if ($employeeUuid !== '') {
            $query->whereHas('employee', static function (Builder $employee) use ($employeeUuid): void {
                $employee->where('uuid', $employeeUuid);
            });
        }

        $withinDays = (int) config('documents.expiring_within_days', 30);
        $expiry = trim((string) ($filters['expiry'] ?? ''));
        if ($expiry === 'expired') {
            $query->whereNotNull('expires_at')->whereDate('expires_at', '<', now()->toDateString());
        } elseif ($expiry === 'expiring') {
            $query->whereNotNull('expires_at')
                ->whereDate('expires_at', '>=', now()->toDateString())
                ->whereDate('expires_at', '<=', now()->addDays($withinDays)->toDateString());
        } elseif ($expiry === 'valid') {
            $query->where(function (Builder $inner) use ($withinDays): void {
                $inner->whereNull('expires_at')
                    ->orWhereDate('expires_at', '>', now()->addDays($withinDays)->toDateString());
            });
        }
    }

    public function findByUuid(string $uuid): ?EmployeeDocument
    {
        return EmployeeDocument::query()
            ->with([
                'employee:id,uuid,employee_number,first_name,last_name,email',
                'uploader:id,name,email',
                'versions' => static function ($query): void {
                    $query->with('uploader:id,name,email')->orderByDesc('version_number');
                },
            ])
            ->withCount('versions')
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * @param  list<string>  $uuids
     * @return Collection<int, EmployeeDocument>
     */
    public function findManyByUuids(array $uuids): Collection
    {
        if ($uuids === []) {
            return collect();
        }

        return EmployeeDocument::query()
            ->with([
                'employee:id,uuid,employee_number,first_name,last_name,email',
                'uploader:id,name,email',
            ])
            ->whereIn('uuid', $uuids)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): EmployeeDocument
    {
        return EmployeeDocument::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(EmployeeDocument $document, array $data): EmployeeDocument
    {
        $document->fill($data);
        $document->save();

        return $document->fresh([
            'employee:id,uuid,employee_number,first_name,last_name,email',
            'uploader:id,name,email',
            'versions.uploader:id,name,email',
        ])?->loadCount('versions') ?? $document;
    }

    public function delete(EmployeeDocument $document): void
    {
        $document->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createVersion(array $data): EmployeeDocumentVersion
    {
        return EmployeeDocumentVersion::query()->create($data);
    }

    public function nextVersionNumber(EmployeeDocument $document): int
    {
        $max = (int) EmployeeDocumentVersion::query()
            ->where('employee_document_id', $document->id)
            ->max('version_number');

        return $max + 1;
    }

    public function findVersion(EmployeeDocument $document, string $versionUuid): ?EmployeeDocumentVersion
    {
        return EmployeeDocumentVersion::query()
            ->with('uploader:id,name,email')
            ->where('employee_document_id', $document->id)
            ->where('uuid', $versionUuid)
            ->first();
    }

    public function expiringCount(int $withinDays = 30): int
    {
        return EmployeeDocument::query()
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '>=', now()->toDateString())
            ->whereDate('expires_at', '<=', now()->addDays($withinDays)->toDateString())
            ->count();
    }

    public function expiredCount(): int
    {
        return EmployeeDocument::query()
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<', now()->toDateString())
            ->count();
    }

    public function totalCount(): int
    {
        return EmployeeDocument::query()->count();
    }

    public function validCount(int $withinDays = 30): int
    {
        return EmployeeDocument::query()
            ->where(function (Builder $inner) use ($withinDays): void {
                $inner->whereNull('expires_at')
                    ->orWhereDate('expires_at', '>', now()->addDays($withinDays)->toDateString());
            })
            ->count();
    }

    /**
     * @return array<string, int>
     */
    public function countsByCategory(): array
    {
        $rows = EmployeeDocument::query()
            ->select('category', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('category')
            ->pluck('aggregate', 'category')
            ->all();

        $counts = [];
        foreach (EmployeeDocument::CATEGORIES as $category) {
            $counts[$category] = (int) ($rows[$category] ?? 0);
        }

        return $counts;
    }

    public function totalStorageBytes(): int
    {
        $current = (int) EmployeeDocument::query()->sum('file_size');
        $versions = (int) EmployeeDocumentVersion::query()->sum('file_size');

        return $current + $versions;
    }

    /**
     * @return Collection<int, EmployeeDocument>
     */
    public function expiringWithinDays(int $withinDays = 30): Collection
    {
        return EmployeeDocument::query()
            ->with([
                'employee:id,uuid,employee_number,first_name,last_name,email',
            ])
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '>=', now()->toDateString())
            ->whereDate('expires_at', '<=', now()->addDays($withinDays)->toDateString())
            ->orderBy('expires_at')
            ->get();
    }
}
