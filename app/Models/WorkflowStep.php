<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WorkflowStep extends Model
{
    protected $fillable = [
        'uuid',
        'workflow_definition_id',
        'step_order',
        'label',
        'approver_permission',
    ];

    protected function casts(): array
    {
        return [
            'step_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (WorkflowStep $step): void {
            if (empty($step->uuid)) {
                $step->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_definition_id');
    }
}
