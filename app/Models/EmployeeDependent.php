<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EmployeeDependent extends Model
{
    public const RELATIONSHIPS = [
        'spouse',
        'child',
        'parent',
        'sibling',
        'other',
    ];

    protected $fillable = [
        'uuid',
        'employee_id',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'relationship',
        'birth_date',
        'gender',
        'mobile',
        'is_beneficiary',
        'is_emergency_contact',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_beneficiary' => 'boolean',
            'is_emergency_contact' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (EmployeeDependent $dependent): void {
            if (empty($dependent->uuid)) {
                $dependent->uuid = (string) Str::uuid();
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

    public function fullName(): string
    {
        $parts = array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
            $this->suffix,
        ], fn ($part) => filled($part));

        return implode(' ', $parts);
    }
}
