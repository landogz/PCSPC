<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LookupValue extends Model
{
    protected $fillable = [
        'uuid',
        'type',
        'code',
        'label',
        'sort_order',
        'is_active',
        'is_system',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (LookupValue $lookup): void {
            if (empty($lookup->uuid)) {
                $lookup->uuid = (string) Str::uuid();
            }
            $lookup->code = Str::snake(Str::lower(trim((string) $lookup->code)));
            $lookup->type = Str::snake(Str::lower(trim((string) $lookup->type)));
        });

        static::updating(function (LookupValue $lookup): void {
            if ($lookup->isDirty('code')) {
                $lookup->code = Str::snake(Str::lower(trim((string) $lookup->code)));
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('label');
    }
}
