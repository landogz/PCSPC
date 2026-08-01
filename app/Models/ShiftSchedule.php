<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ShiftSchedule extends Model
{
    public const ASSIGNEE_EMPLOYEE = 'employee';

    public const ASSIGNEE_DEPARTMENT = 'department';

    public const ASSIGNEE_TYPES = [
        self::ASSIGNEE_EMPLOYEE,
        self::ASSIGNEE_DEPARTMENT,
    ];

    protected $fillable = [
        'uuid',
        'shift_id',
        'assignee_type',
        'employee_id',
        'department_id',
        'effective_from',
        'effective_to',
        'days_of_week',
        'notes',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
            'days_of_week' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ShiftSchedule $schedule): void {
            if (empty($schedule->uuid)) {
                $schedule->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isEffectiveOn(?Carbon $date = null): bool
    {
        $date ??= now()->startOfDay();
        if (! $this->is_active) {
            return false;
        }
        if ($this->effective_from !== null && $date->lt($this->effective_from->startOfDay())) {
            return false;
        }
        if ($this->effective_to !== null && $date->gt($this->effective_to->startOfDay())) {
            return false;
        }

        $days = $this->days_of_week;
        if (is_array($days) && $days !== []) {
            // ISO: 1=Mon … 7=Sun
            return in_array((int) $date->dayOfWeekIso, array_map('intval', $days), true);
        }

        return true;
    }
}
