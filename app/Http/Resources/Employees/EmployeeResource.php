<?php

namespace App\Http\Resources\Employees;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Employee
 */
class EmployeeResource extends JsonResource
{
    public function __construct($resource, private readonly bool $revealStatutory = false)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'employee_number' => $this->employee_number,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'suffix' => $this->suffix,
            'full_name' => $this->fullName(),
            'email' => $this->email,
            'photo_url' => $this->photoUrl(),
            'mobile' => $this->mobile,
            'department_id' => $this->department?->uuid,
            'department' => $this->when($this->relationLoaded('department') && $this->department, fn () => [
                'id' => $this->department->uuid,
                'code' => $this->department->code,
                'name' => $this->department->name,
            ]),
            'position_title' => $this->position_title,
            'employment_status' => $this->employment_status,
            'date_hired' => $this->date_hired?->toDateString(),
            'date_regularized' => $this->date_regularized?->toDateString(),
            'date_separated' => $this->date_separated?->toDateString(),
            'birth_date' => $this->birth_date?->toDateString(),
            'gender' => $this->gender,
            'civil_status' => $this->civil_status,
            'nationality' => $this->nationality,
            'address_line' => $this->address_line,
            'city' => $this->city,
            'province' => $this->province,
            'zip_code' => $this->zip_code,
            'tin' => $this->maskOrReveal($this->tin),
            'sss_number' => $this->maskOrReveal($this->sss_number),
            'philhealth_number' => $this->maskOrReveal($this->philhealth_number),
            'pagibig_number' => $this->maskOrReveal($this->pagibig_number),
            'user' => $this->when($this->relationLoaded('user') && $this->user, fn () => [
                'id' => $this->user->uuid,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'employee_number' => $this->user->employee_number,
                'is_active' => (bool) $this->user->is_active,
                'roles' => $this->user->relationLoaded('roles')
                    ? $this->user->roles->pluck('slug')->values()->all()
                    : $this->user->roleSlugs(),
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    private function maskOrReveal(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($this->revealStatutory) {
            return $value;
        }

        $length = strlen($value);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', max(0, $length - 4)).substr($value, -4);
    }
}
