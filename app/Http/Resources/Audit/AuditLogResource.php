<?php

namespace App\Http\Resources\Audit;

use App\Models\AuthActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AuthActivityLog
 */
class AuditLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'email' => $this->email,
            'event' => $this->event,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'meta' => $this->meta ?? (object) [],
            'created_at' => $this->created_at?->toIso8601String(),
            'user' => $this->when($this->relationLoaded('user') && $this->user, fn () => [
                'id' => $this->user->uuid,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'employee_number' => $this->user->employee_number,
            ]),
        ];
    }
}
