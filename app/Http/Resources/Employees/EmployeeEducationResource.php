<?php

namespace App\Http\Resources\Employees;

use App\Models\EmployeeEducation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EmployeeEducation
 */
class EmployeeEducationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'institution' => $this->institution,
            'level' => $this->level,
            'degree_or_course' => $this->degree_or_course,
            'year_started' => $this->year_started,
            'year_ended' => $this->year_ended,
            'is_highest' => (bool) $this->is_highest,
            'honors' => $this->honors,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
