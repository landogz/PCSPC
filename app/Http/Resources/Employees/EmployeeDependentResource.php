<?php

namespace App\Http\Resources\Employees;

use App\Models\EmployeeDependent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EmployeeDependent
 */
class EmployeeDependentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'suffix' => $this->suffix,
            'full_name' => $this->fullName(),
            'relationship' => $this->relationship,
            'birth_date' => $this->birth_date?->toDateString(),
            'gender' => $this->gender,
            'mobile' => $this->mobile,
            'is_beneficiary' => (bool) $this->is_beneficiary,
            'is_emergency_contact' => (bool) $this->is_emergency_contact,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
