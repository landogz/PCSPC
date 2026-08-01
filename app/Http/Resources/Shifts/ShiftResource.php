<?php

namespace App\Http\Resources\Shifts;

use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Shift
 */
class ShiftResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $workMinutes = $this->resource->workMinutes();

        return [
            'id' => $this->uuid,
            'code' => $this->code,
            'name' => $this->name,
            'time_in' => $this->time_in,
            'time_out' => $this->time_out,
            'break_minutes' => (int) $this->break_minutes,
            'grace_minutes' => (int) $this->grace_minutes,
            'crosses_midnight' => (bool) $this->crosses_midnight,
            'work_hours' => round($workMinutes / 60, 2),
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
