<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LeaveBalance extends Model
{
    protected $fillable = [
        'uuid',
        'employee_id',
        'leave_type_id',
        'leave_year',
        'beginning',
        'earned',
        'used',
        'adjusted',
    ];

    protected function casts(): array
    {
        return [
            'leave_year' => 'integer',
            'beginning' => 'decimal:2',
            'earned' => 'decimal:2',
            'used' => 'decimal:2',
            'adjusted' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (LeaveBalance $balance): void {
            if (empty($balance->uuid)) {
                $balance->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function ending(): float
    {
        return round(
            (float) $this->beginning + (float) $this->earned + (float) $this->adjusted - (float) $this->used,
            2
        );
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LeaveLedgerEntry::class)->orderByDesc('effective_date')->orderByDesc('id');
    }
}
