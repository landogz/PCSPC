<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LeaveType extends Model
{
    protected $fillable = [
        'uuid',
        'code',
        'name',
        'is_accruing',
        'requires_reason',
        'requires_hr',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_accruing' => 'boolean',
            'requires_reason' => 'boolean',
            'requires_hr' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (LeaveType $type): void {
            if (empty($type->uuid)) {
                $type->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
