<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Holiday extends Model
{
    public const TYPES = [
        'regular',
        'special_non_working',
        'special_working',
        'company',
    ];

    protected $fillable = [
        'uuid',
        'name',
        'holiday_date',
        'type',
        'is_recurring',
        'is_double_pay',
        'paid_hours',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'holiday_date' => 'date',
            'is_recurring' => 'boolean',
            'is_double_pay' => 'boolean',
            'paid_hours' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Holiday $holiday): void {
            if (empty($holiday->uuid)) {
                $holiday->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
