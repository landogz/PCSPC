<?php

namespace App\Services\Lookups;

use App\Models\Employee;
use App\Models\EmployeeCareerHistory;
use App\Models\EmployeeDocument;
use App\Models\EmployeeDependent;
use App\Models\EmployeeEducation;
use App\Models\Holiday;
use App\Models\LookupValue;
use App\Repositories\Lookups\LookupRepository;
use App\Services\Audit\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LookupService
{
    public function __construct(
        private readonly LookupRepository $lookups,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return list<array{type: string, label: string, description: string, module: string, count: int}>
     */
    public function types(): array
    {
        $counts = $this->lookups->countsByType();
        $types = [];

        foreach (config('lookups.types', []) as $type => $meta) {
            $types[] = [
                'type' => $type,
                'label' => (string) ($meta['label'] ?? Str::headline($type)),
                'description' => (string) ($meta['description'] ?? ''),
                'module' => (string) ($meta['module'] ?? ''),
                'count' => (int) ($counts[$type] ?? 0),
            ];
        }

        return $types;
    }

    /**
     * @param  array{search?: string, type?: string, status?: string}  $filters
     */
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->lookups->paginate($filters, $perPage);
    }

    public function find(string $uuid): LookupValue
    {
        $lookup = $this->lookups->findByUuid($uuid);
        if ($lookup === null) {
            abort(404, 'Lookup value not found.');
        }

        return $lookup;
    }

    /**
     * Active options for dropdowns: [{code, label}, ...]
     *
     * @return list<array{code: string, label: string}>
     */
    public function activeOptions(string $type, ?array $fallback = null): array
    {
        $this->assertKnownType($type);

        $rows = $this->lookups->activeByType($type);
        if ($rows->isNotEmpty()) {
            return $rows->map(static fn (LookupValue $row): array => [
                'code' => $row->code,
                'label' => $row->label,
            ])->values()->all();
        }

        $fallback ??= $this->defaultCodes($type);

        return array_map(static fn (string $code): array => [
            'code' => $code,
            'label' => Str::headline(str_replace('_', ' ', $code)),
        ], $fallback);
    }

    /**
     * Active codes for Rule::in validation.
     *
     * @return list<string>
     */
    public function activeCodes(string $type, ?array $fallback = null): array
    {
        return array_values(array_map(
            static fn (array $option): string => $option['code'],
            $this->activeOptions($type, $fallback),
        ));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): LookupValue
    {
        $type = (string) ($payload['type'] ?? '');
        $this->assertKnownType($type);

        $code = Str::snake(Str::lower(trim((string) ($payload['code'] ?? ''))));
        if ($code === '' || $this->lookups->codeExists($type, $code)) {
            throw ValidationException::withMessages([
                'code' => ['This code already exists for the selected lookup type.'],
            ]);
        }

        $lookup = $this->lookups->create([
            'type' => $type,
            'code' => $code,
            'label' => trim((string) ($payload['label'] ?? '')),
            'sort_order' => (int) ($payload['sort_order'] ?? 0),
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'is_system' => false,
            'description' => $this->nullableString($payload['description'] ?? null),
        ]);

        $this->audit->log('lookup.created', [
            'lookup_id' => $lookup->uuid,
            'type' => $lookup->type,
            'code' => $lookup->code,
            'label' => $lookup->label,
        ]);

        return $lookup;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(string $uuid, array $payload): LookupValue
    {
        $lookup = $this->find($uuid);

        $data = [
            'label' => trim((string) ($payload['label'] ?? $lookup->label)),
            'sort_order' => array_key_exists('sort_order', $payload)
                ? (int) $payload['sort_order']
                : $lookup->sort_order,
            'is_active' => array_key_exists('is_active', $payload)
                ? (bool) $payload['is_active']
                : $lookup->is_active,
            'description' => array_key_exists('description', $payload)
                ? $this->nullableString($payload['description'])
                : $lookup->description,
        ];

        // System rows keep their code/type; custom rows may rename code.
        if (! $lookup->is_system && isset($payload['code'])) {
            $code = Str::snake(Str::lower(trim((string) $payload['code'])));
            if ($code === '' || $this->lookups->codeExists($lookup->type, $code, $lookup->uuid)) {
                throw ValidationException::withMessages([
                    'code' => ['This code already exists for the selected lookup type.'],
                ]);
            }
            $data['code'] = $code;
        }

        $updated = $this->lookups->update($lookup, $data);

        $this->audit->log('lookup.updated', [
            'lookup_id' => $updated->uuid,
            'type' => $updated->type,
            'code' => $updated->code,
            'label' => $updated->label,
            'is_active' => $updated->is_active,
        ]);

        return $updated;
    }

    public function delete(string $uuid): void
    {
        $lookup = $this->find($uuid);
        if ($lookup->is_system) {
            abort(422, 'System lookup values cannot be deleted. Deactivate them instead.');
        }

        $meta = [
            'lookup_id' => $lookup->uuid,
            'type' => $lookup->type,
            'code' => $lookup->code,
            'label' => $lookup->label,
        ];

        $this->lookups->delete($lookup);
        $this->audit->log('lookup.deleted', $meta);
    }

    /**
     * @return list<string>
     */
    public function defaultCodes(string $type): array
    {
        return match ($type) {
            'gender' => ['male', 'female', 'other'],
            'civil_status' => ['single', 'married', 'widowed', 'separated'],
            'employment_status' => Employee::STATUSES,
            'employment_category' => EmployeeCareerHistory::CATEGORIES,
            'dependent_relationship' => EmployeeDependent::RELATIONSHIPS,
            'education_level' => EmployeeEducation::LEVELS,
            'holiday_type' => Holiday::TYPES,
            'document_category' => EmployeeDocument::CATEGORIES,
            default => [],
        };
    }

    /**
     * Default seed rows: code => label
     *
     * @return array<string, string>
     */
    public function defaultLabels(string $type): array
    {
        $codes = $this->defaultCodes($type);
        $labels = [];
        foreach ($codes as $code) {
            $labels[$code] = match ($type.'.'.$code) {
                'holiday_type.regular' => 'Regular',
                'holiday_type.special_non_working' => 'Special non-working',
                'holiday_type.special_working' => 'Special working',
                'holiday_type.company' => 'Company',
                'document_category.government_id' => 'Government ID',
                'employment_status.on_leave' => 'On leave',
                'employment_category.project_based' => 'Project-based',
                'education_level.high_school' => 'High school',
                default => Str::headline(str_replace('_', ' ', $code)),
            };
        }

        return $labels;
    }

    private function assertKnownType(string $type): void
    {
        if (! array_key_exists($type, config('lookups.types', []))) {
            abort(422, 'Unknown lookup type.');
        }
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
