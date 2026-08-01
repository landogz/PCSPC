<?php

namespace App\Http\Resources\Holidays;

use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Holiday
 */
class HolidayResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'holiday_date' => $this->holiday_date?->toDateString(),
            'type' => $this->type,
            'is_recurring' => (bool) $this->is_recurring,
            'is_double_pay' => (bool) $this->is_double_pay,
            'paid_hours' => (int) $this->paid_hours,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
