<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EmployeeEducation extends Model
{
    protected $table = 'employee_educations';

    public const LEVELS = [
        'elementary',
        'high_school',
        'vocational',
        'associate',
        'bachelor',
        'master',
        'doctorate',
        'other',
    ];

    protected $fillable = [
        'uuid',
        'employee_id',
        'institution',
        'level',
        'degree_or_course',
        'year_started',
        'year_ended',
        'is_highest',
        'honors',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'year_started' => 'integer',
            'year_ended' => 'integer',
            'is_highest' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (EmployeeEducation $education): void {
            if (empty($education->uuid)) {
                $education->uuid = (string) Str::uuid();
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
