<?php

namespace App\Http\Resources\Documents;

use App\Models\EmployeeDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EmployeeDocument
 */
class DocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $employee = $this->employee;
        $restricted = $this->isRestricted();
        $fileKind = $this->fileKind();
        $previewable = $this->isPreviewable();

        return [
            'id' => $this->uuid,
            'title' => $this->title,
            'category' => $this->category,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'file_kind' => $fileKind,
            'file_size' => (int) $this->file_size,
            'file_size_label' => $this->humanFileSize((int) $this->file_size),
            'issued_at' => $this->issued_at?->toDateString(),
            'expires_at' => $this->expires_at?->toDateString(),
            'expiry_status' => $this->expiryStatus(),
            'is_expired' => $this->isExpired(),
            'is_expiring_soon' => $this->isExpiringSoon(),
            'notes' => $this->notes,
            'is_previewable' => $previewable,
            'preview_url' => $previewable ? url('/api/v1/documents/'.$this->uuid.'/preview') : null,
            'download_url' => url('/api/v1/documents/'.$this->uuid.'/download'),
            'version_count' => (int) ($this->versions_count ?? $this->versions?->count() ?? 0),
            'versions' => $this->when(
                $this->relationLoaded('versions'),
                function () {
                    $documentUuid = $this->uuid;

                    return $this->versions->map(static function ($version) use ($documentUuid) {
                        return [
                            'id' => $version->uuid,
                            'version_number' => (int) $version->version_number,
                            'original_name' => $version->original_name,
                            'mime_type' => $version->mime_type,
                            'file_size' => (int) $version->file_size,
                            'file_size_label' => self::formatBytes((int) $version->file_size),
                            'uploaded_by' => $version->uploader === null ? null : [
                                'name' => $version->uploader->name,
                                'email' => $version->uploader->email,
                            ],
                            'created_at' => $version->created_at?->toIso8601String(),
                            'download_url' => url('/api/v1/documents/'.$documentUuid.'/versions/'.$version->uuid.'/download'),
                        ];
                    })->values()->all();
                }
            ),
            'access' => [
                'level' => $restricted ? 'restricted' : 'hr',
                'label' => $restricted ? 'HR only · sensitive' : 'HR staff',
                'icon' => $restricted ? 'shield' : 'lock',
                'shared_with_employee' => false,
            ],
            'employee' => $employee === null ? null : [
                'id' => $employee->uuid,
                'employee_number' => $employee->employee_number,
                'name' => trim($employee->first_name.' '.$employee->last_name),
                'email' => $employee->email,
            ],
            'uploaded_by' => $this->uploader === null ? null : [
                'name' => $this->uploader->name,
                'email' => $this->uploader->email,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    private function humanFileSize(int $bytes): string
    {
        return self::formatBytes($bytes);
    }

    public static function formatBytes(int $bytes): string
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
