<?php

namespace App\Http\Resources\Employees;

use App\Models\EmployeeCareerHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EmployeeCareerHistory
 */
class EmployeeCareerHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $salary = $this->basic_salary;

        return [
            'id' => $this->uuid,
            'position_title' => $this->position_title,
            'employment_category' => $this->employment_category,
            'basic_salary' => $salary !== null && $salary !== '' ? (string) $salary : null,
            'salary_rate_type' => $this->salary_rate_type,
            'currency' => $this->currency,
            'effective_from' => $this->effective_from?->toDateString(),
            'effective_to' => $this->effective_to?->toDateString(),
            'is_current' => (bool) $this->is_current,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
