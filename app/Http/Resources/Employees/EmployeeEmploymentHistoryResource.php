<?php

namespace App\Http\Resources\Employees;

use App\Models\EmployeeEmploymentHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EmployeeEmploymentHistory
 */
class EmployeeEmploymentHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'employer_name' => $this->employer_name,
            'position_title' => $this->position_title,
            'location' => $this->location,
            'date_from' => $this->date_from?->toDateString(),
            'date_to' => $this->date_to?->toDateString(),
            'is_current' => (bool) $this->is_current,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
