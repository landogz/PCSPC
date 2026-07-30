<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Employee extends Model
{
    public const STATUSES = ['active', 'inactive', 'separated', 'on_leave'];

    protected $fillable = [
        'uuid',
        'employee_number',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'email',
        'photo_path',
        'mobile',
        'department_id',
        'position_title',
        'employment_status',
        'date_hired',
        'date_regularized',
        'date_separated',
        'birth_date',
        'gender',
        'civil_status',
        'nationality',
        'address_line',
        'city',
        'province',
        'zip_code',
        'tin',
        'sss_number',
        'philhealth_number',
        'pagibig_number',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'date_hired' => 'date',
            'date_regularized' => 'date',
            'date_separated' => 'date',
            'birth_date' => 'date',
            'tin' => 'encrypted',
            'sss_number' => 'encrypted',
            'philhealth_number' => 'encrypted',
            'pagibig_number' => 'encrypted',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Employee $employee): void {
            if (empty($employee->uuid)) {
                $employee->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function isActiveEmployment(): bool
    {
        return in_array($this->employment_status, ['active', 'on_leave'], true);
    }

    public function photoUrl(): ?string
    {
        return \App\Support\ProfilePhoto::forEmployee($this);
    }
}
