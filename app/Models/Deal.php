<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deal extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id',
        'organization_id',
        'offering_id',
        'pipeline_id',
        'stage_id',
        'owner_id',
        'title',
        'amount',
        'probability',
        'close_date',
        'source',
        'status',
        'lost_reason',
        'chatwoot_conversation_id',
        'diagnostic_risk_level',
        'diagnostic_result',
        'kpis',
        'stage_changed_at',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'close_date' => 'date',
            'diagnostic_result' => 'array',
            'kpis' => 'array',
            'stage_changed_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(Offering::class);
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function moveToStage(Stage $stage): void
    {
        $this->stage_id = $stage->id;
        $this->pipeline_id = $stage->pipeline_id;
        $this->probability = $stage->probability_default;
        $this->stage_changed_at = now();

        if ($stage->is_won) {
            $this->status = 'won';
        } elseif ($stage->is_lost) {
            $this->status = 'lost';
        } else {
            $this->status = 'open';
        }

        $this->save();
    }
}
