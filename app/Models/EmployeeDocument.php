<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EmployeeDocument extends Model
{
    public const CATEGORIES = [
        'contract',
        'government_id',
        'certificate',
        'clearance',
        'policy',
        'other',
    ];

    public const DISK = 'local';

    protected $fillable = [
        'uuid',
        'employee_id',
        'title',
        'category',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'issued_at',
        'expires_at',
        'notes',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'expires_at' => 'date',
            'file_size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (EmployeeDocument $document): void {
            if (empty($document->uuid)) {
                $document->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(EmployeeDocumentVersion::class, 'employee_document_id')
            ->orderByDesc('version_number');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isExpiringSoon(?int $withinDays = null): bool
    {
        $withinDays ??= (int) config('documents.expiring_within_days', 30);

        if ($this->expires_at === null || $this->isExpired()) {
            return false;
        }

        return $this->expires_at->lte(now()->addDays($withinDays)->startOfDay());
    }

    public function expiryStatus(): string
    {
        if ($this->expires_at === null) {
            return 'none';
        }
        if ($this->isExpired()) {
            return 'expired';
        }
        if ($this->isExpiringSoon()) {
            return 'expiring';
        }

        return 'valid';
    }

    public function fileKind(): string
    {
        $mime = strtolower((string) $this->mime_type);
        $name = strtolower((string) $this->original_name);

        if (str_starts_with($mime, 'image/') || preg_match('/\.(jpe?g|png|webp|gif)$/', $name) === 1) {
            return 'image';
        }
        if ($mime === 'application/pdf' || str_ends_with($name, '.pdf')) {
            return 'pdf';
        }
        if (
            in_array($mime, [
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ], true)
            || preg_match('/\.docx?$/', $name) === 1
        ) {
            return 'word';
        }

        return 'file';
    }

    public function isPreviewable(): bool
    {
        return in_array($this->fileKind(), ['image', 'pdf'], true);
    }

    public function isRestricted(): bool
    {
        $restricted = config('documents.restricted_categories', ['government_id', 'clearance']);

        return in_array($this->category, $restricted, true);
    }
}
