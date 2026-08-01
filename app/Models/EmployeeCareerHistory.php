<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EmployeeCareerHistory extends Model
{
    public const CATEGORIES = [
        'permanent',
        'probationary',
        'contractual',
        'casual',
        'temporary',
        'project_based',
        'consultant',
        'other',
    ];

    public const RATE_TYPES = [
        'monthly',
        'daily',
        'hourly',
    ];

    protected $table = 'employee_career_histories';

    protected $fillable = [
        'uuid',
        'employee_id',
        'position_title',
        'employment_category',
        'basic_salary',
        'salary_rate_type',
        'currency',
        'effective_from',
        'effective_to',
        'is_current',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'encrypted',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_current' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (EmployeeCareerHistory $history): void {
            if (empty($history->uuid)) {
                $history->uuid = (string) Str::uuid();
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
}
