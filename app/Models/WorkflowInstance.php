<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class WorkflowInstance extends Model
{
    public const STATUSES = ['pending', 'approved', 'rejected', 'cancelled'];

    protected $fillable = [
        'uuid',
        'workflow_definition_id',
        'subject_type',
        'subject_id',
        'current_step_order',
        'status',
        'started_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'current_step_order' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (WorkflowInstance $instance): void {
            if (empty($instance->uuid)) {
                $instance->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_definition_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(WorkflowAction::class)->orderBy('step_order')->orderBy('id');
    }

    public function currentStep(): ?WorkflowStep
    {
        if (! $this->relationLoaded('definition')) {
            $this->load('definition.steps');
        }

        return $this->definition?->steps
            ->firstWhere('step_order', $this->current_step_order);
    }
}
