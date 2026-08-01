<?php

namespace App\Services\Documents;

use App\Mail\Documents\DocumentExpiryDigestMail;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use App\Repositories\Documents\DocumentRepository;
use App\Services\Audit\AuditLogger;
use App\Services\Lookups\LookupService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentService
{
    public function __construct(
        private readonly DocumentRepository $documents,
        private readonly AuditLogger $audit,
        private readonly LookupService $lookups,
    ) {}

    /**
     * @param  array{search?: string, category?: string, employee_id?: string, expiry?: string}  $filters
     */
    public function list(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->documents->paginate($filters, $perPage);
    }

    /**
     * @return list<string>
     */
    public function categories(): array
    {
        return $this->lookups->activeCodes('document_category', EmployeeDocument::CATEGORIES);
    }

    /**
     * @return array<string, mixed>
     */
    public function stats(): array
    {
        $withinDays = (int) config('documents.expiring_within_days', 30);
        $used = $this->documents->totalStorageBytes();
        $limit = max(1, (int) config('documents.storage_limit_bytes', 5 * 1024 * 1024 * 1024));

        return [
            'total' => $this->documents->totalCount(),
            'expiring' => $this->documents->expiringCount($withinDays),
            'expired' => $this->documents->expiredCount(),
            'valid' => $this->documents->validCount($withinDays),
            'by_category' => $this->documents->countsByCategory(),
            'expiring_within_days' => $withinDays,
            'storage' => [
                'used_bytes' => $used,
                'used_label' => $this->formatBytes($used),
                'limit_bytes' => $limit,
                'limit_label' => $this->formatBytes($limit),
                'percent' => min(100, (int) round(($used / $limit) * 100)),
            ],
        ];
    }

    public function find(string $uuid): EmployeeDocument
    {
        $document = $this->documents->findByUuid($uuid);
        if ($document === null) {
            abort(404, 'Document not found.');
        }

        return $document;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload, UploadedFile $file, ?int $uploaderId = null): EmployeeDocument
    {
        $employee = $this->resolveEmployee((string) ($payload['employee_id'] ?? ''));
        $stored = $this->storeFile($employee, $file);

        $document = $this->documents->create([
            'employee_id' => $employee->id,
            'title' => trim((string) ($payload['title'] ?? '')),
            'category' => (string) ($payload['category'] ?? 'other'),
            'file_path' => $stored['path'],
            'original_name' => $stored['original_name'],
            'mime_type' => $stored['mime_type'],
            'file_size' => $stored['file_size'],
            'issued_at' => $payload['issued_at'] ?? null,
            'expires_at' => $payload['expires_at'] ?? null,
            'notes' => $this->nullableString($payload['notes'] ?? null),
            'uploaded_by' => $uploaderId,
        ]);

        $this->audit->log('document.created', [
            'document_id' => $document->uuid,
            'employee_id' => $employee->uuid,
            'title' => $document->title,
            'category' => $document->category,
            'original_name' => $document->original_name,
        ]);

        return $this->find($document->uuid);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(string $uuid, array $payload, ?UploadedFile $file = null, ?int $uploaderId = null): EmployeeDocument
    {
        $document = $this->find($uuid);
        $data = [
            'title' => trim((string) ($payload['title'] ?? $document->title)),
            'category' => (string) ($payload['category'] ?? $document->category),
            'issued_at' => array_key_exists('issued_at', $payload) ? ($payload['issued_at'] ?: null) : $document->issued_at?->toDateString(),
            'expires_at' => array_key_exists('expires_at', $payload) ? ($payload['expires_at'] ?: null) : $document->expires_at?->toDateString(),
            'notes' => array_key_exists('notes', $payload)
                ? $this->nullableString($payload['notes'])
                : $document->notes,
        ];

        if (isset($payload['employee_id']) && filled($payload['employee_id'])) {
            $employee = $this->resolveEmployee((string) $payload['employee_id']);
            $data['employee_id'] = $employee->id;
        }

        if ($file !== null) {
            $employee = $document->employee ?? $this->resolveEmployeeById((int) ($data['employee_id'] ?? $document->employee_id));
            $this->archiveCurrentVersion($document, $uploaderId ?? $document->uploaded_by);
            $stored = $this->storeFile($employee, $file);
            $data['file_path'] = $stored['path'];
            $data['original_name'] = $stored['original_name'];
            $data['mime_type'] = $stored['mime_type'];
            $data['file_size'] = $stored['file_size'];
            if ($uploaderId !== null) {
                $data['uploaded_by'] = $uploaderId;
            }
        }

        $updated = $this->documents->update($document, $data);

        $this->audit->log('document.updated', [
            'document_id' => $updated->uuid,
            'employee_id' => $updated->employee?->uuid,
            'title' => $updated->title,
            'category' => $updated->category,
            'file_replaced' => $file !== null,
        ]);

        return $this->find($updated->uuid);
    }

    public function delete(string $uuid): void
    {
        $document = $this->find($uuid);
        $meta = [
            'document_id' => $document->uuid,
            'employee_id' => $document->employee?->uuid,
            'title' => $document->title,
            'category' => $document->category,
            'original_name' => $document->original_name,
        ];

        foreach ($document->versions as $version) {
            $this->deleteStoredFile($version->file_path);
        }
        $this->deleteStoredFile($document->file_path);
        $this->documents->delete($document);
        $this->audit->log('document.deleted', $meta);
    }

    /**
     * @param  list<string>  $uuids
     * @return array{deleted: int}
     */
    public function bulkDelete(array $uuids): array
    {
        $documents = $this->documents->findManyByUuids($uuids);
        $deleted = 0;

        foreach ($documents as $document) {
            $this->delete($document->uuid);
            $deleted++;
        }

        if ($deleted > 0) {
            $this->audit->log('document.bulk_deleted', [
                'count' => $deleted,
                'document_ids' => $documents->pluck('uuid')->values()->all(),
            ]);
        }

        return ['deleted' => $deleted];
    }

    /**
     * @param  list<string>  $uuids
     * @return array{updated: int}
     */
    public function bulkUpdateCategory(array $uuids, string $category): array
    {
        if (! in_array($category, EmployeeDocument::CATEGORIES, true)) {
            abort(422, 'Invalid category.');
        }

        $documents = $this->documents->findManyByUuids($uuids);
        $updated = 0;

        foreach ($documents as $document) {
            $this->documents->update($document, ['category' => $category]);
            $updated++;
        }

        if ($updated > 0) {
            $this->audit->log('document.bulk_category_updated', [
                'count' => $updated,
                'category' => $category,
                'document_ids' => $documents->pluck('uuid')->values()->all(),
            ]);
        }

        return ['updated' => $updated];
    }

    public function download(string $uuid): StreamedResponse
    {
        $document = $this->find($uuid);

        if (! Storage::disk(EmployeeDocument::DISK)->exists($document->file_path)) {
            abort(404, 'Document file is missing.');
        }

        $this->audit->log('document.downloaded', [
            'document_id' => $document->uuid,
            'employee_id' => $document->employee?->uuid,
            'title' => $document->title,
        ]);

        return Storage::disk(EmployeeDocument::DISK)->download(
            $document->file_path,
            $document->original_name,
        );
    }

    public function preview(string $uuid): StreamedResponse
    {
        $document = $this->find($uuid);

        if (! $document->isPreviewable()) {
            abort(422, 'Preview is only available for images and PDFs.');
        }

        if (! Storage::disk(EmployeeDocument::DISK)->exists($document->file_path)) {
            abort(404, 'Document file is missing.');
        }

        $this->audit->log('document.previewed', [
            'document_id' => $document->uuid,
            'employee_id' => $document->employee?->uuid,
            'title' => $document->title,
        ]);

        return Storage::disk(EmployeeDocument::DISK)->response(
            $document->file_path,
            $document->original_name,
            [
                'Content-Type' => $document->mime_type ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="'.$document->original_name.'"',
                'Cache-Control' => 'private, max-age=60',
            ],
        );
    }

    public function downloadVersion(string $documentUuid, string $versionUuid): StreamedResponse
    {
        $document = $this->find($documentUuid);
        $version = $this->documents->findVersion($document, $versionUuid);
        if ($version === null) {
            abort(404, 'Document version not found.');
        }

        if (! Storage::disk(EmployeeDocument::DISK)->exists($version->file_path)) {
            abort(404, 'Version file is missing.');
        }

        $this->audit->log('document.version_downloaded', [
            'document_id' => $document->uuid,
            'version_id' => $version->uuid,
            'version_number' => $version->version_number,
        ]);

        return Storage::disk(EmployeeDocument::DISK)->download(
            $version->file_path,
            $version->original_name,
        );
    }

    public function expiringCount(int $withinDays = 30): int
    {
        return $this->documents->expiringCount($withinDays);
    }

    public function expiredCount(): int
    {
        return $this->documents->expiredCount();
    }

    /**
     * @return array{sent: int, documents: int}
     */
    public function sendExpiryDigest(?int $withinDays = null): array
    {
        $withinDays ??= (int) config('documents.expiring_within_days', 30);
        $expiring = $this->documents->expiringWithinDays($withinDays);
        $expired = $this->documents->expiredCount();

        if ($expiring->isEmpty() && $expired === 0) {
            return ['sent' => 0, 'documents' => 0];
        }

        $recipients = User::query()
            ->where('is_active', true)
            ->get()
            ->filter(static fn (User $user): bool => $user->hasPermission('documents.manage'));

        $sent = 0;
        foreach ($recipients as $user) {
            Mail::to($user->email)->send(new DocumentExpiryDigestMail(
                user: $user,
                expiring: $expiring,
                expiredCount: $expired,
                withinDays: $withinDays,
            ));
            $sent++;
        }

        $this->audit->log('document.expiry_digest_sent', [
            'recipients' => $sent,
            'expiring_count' => $expiring->count(),
            'expired_count' => $expired,
            'within_days' => $withinDays,
        ]);

        return ['sent' => $sent, 'documents' => $expiring->count()];
    }

    private function archiveCurrentVersion(EmployeeDocument $document, ?int $uploaderId): void
    {
        if (! filled($document->file_path)) {
            return;
        }

        $this->documents->createVersion([
            'employee_document_id' => $document->id,
            'version_number' => $this->documents->nextVersionNumber($document),
            'file_path' => $document->file_path,
            'original_name' => $document->original_name,
            'mime_type' => $document->mime_type,
            'file_size' => (int) $document->file_size,
            'uploaded_by' => $uploaderId,
        ]);
    }

    private function resolveEmployee(string $uuid): Employee
    {
        $employee = Employee::query()->where('uuid', $uuid)->first();
        if ($employee === null) {
            abort(422, 'Employee not found.');
        }

        return $employee;
    }

    private function resolveEmployeeById(int $id): Employee
    {
        $employee = Employee::query()->find($id);
        if ($employee === null) {
            abort(422, 'Employee not found.');
        }

        return $employee;
    }

    /**
     * @return array{path: string, original_name: string, mime_type: string|null, file_size: int}
     */
    private function storeFile(Employee $employee, UploadedFile $file): array
    {
        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin'));
        $filename = Str::uuid()->toString().'.'.$extension;
        $directory = 'employees/documents/'.$employee->uuid;
        $path = $file->storeAs($directory, $filename, EmployeeDocument::DISK);

        if ($path === false) {
            abort(500, 'Unable to store document file.');
        }

        return [
            'path' => $path,
            'original_name' => Str::limit((string) $file->getClientOriginalName(), 255, ''),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => (int) $file->getSize(),
        ];
    }

    private function deleteStoredFile(?string $path): void
    {
        if (! filled($path)) {
            return;
        }

        Storage::disk(EmployeeDocument::DISK)->delete($path);
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }
        if ($bytes < 1073741824) {
            return round($bytes / 1048576, 1).' MB';
        }

        return round($bytes / 1073741824, 2).' GB';
    }
}
