<?php

namespace App\Http\Resources\Lookups;

use App\Models\LookupValue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LookupValue
 */
class LookupValueResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $typeMeta = config('lookups.types.'.$this->type, []);

        return [
            'id' => $this->uuid,
            'type' => $this->type,
            'type_label' => (string) ($typeMeta['label'] ?? $this->type),
            'code' => $this->code,
            'label' => $this->label,
            'sort_order' => (int) $this->sort_order,
            'is_active' => (bool) $this->is_active,
            'is_system' => (bool) $this->is_system,
            'description' => $this->description,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
