<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Offering extends Model
{
    use HasFactory;

    protected $fillable = [
        'pipeline_id',
        'name',
        'slug',
        'type',
        'description',
        'duration_days',
        'default_amount',
        'is_retainer',
        'retainer_months',
        'kpi_notes',
    ];

    protected function casts(): array
    {
        return [
            'default_amount' => 'decimal:2',
            'is_retainer' => 'boolean',
        ];
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }
}
