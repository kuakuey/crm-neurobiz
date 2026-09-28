<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pipeline extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function stages(): HasMany
    {
        return $this->hasMany(Stage::class)->orderBy('position');
    }

    public function offerings(): HasMany
    {
        return $this->hasMany(Offering::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function firstStage(): ?Stage
    {
        return $this->stages()->orderBy('position')->first();
    }

    public function stageBySlug(string $slug): ?Stage
    {
        return $this->stages()->where('slug', $slug)->first();
    }
}
