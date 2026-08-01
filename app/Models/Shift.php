<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Shift extends Model
{
    protected $fillable = [
        'uuid',
        'code',
        'name',
        'time_in',
        'time_out',
        'break_minutes',
        'grace_minutes',
        'crosses_midnight',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'break_minutes' => 'integer',
            'grace_minutes' => 'integer',
            'crosses_midnight' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Shift $shift): void {
            if (empty($shift->uuid)) {
                $shift->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ShiftSchedule::class);
    }

    public function workMinutes(): int
    {
        $in = $this->minutesFromClock((string) $this->time_in);
        $out = $this->minutesFromClock((string) $this->time_out);
        $span = $this->crosses_midnight
            ? (($out + (24 * 60)) - $in)
            : ($out - $in);

        return max(0, $span - (int) $this->break_minutes);
    }

    private function minutesFromClock(string $clock): int
    {
        [$hour, $minute] = array_pad(explode(':', $clock), 2, '0');

        return ((int) $hour * 60) + (int) $minute;
    }
}
