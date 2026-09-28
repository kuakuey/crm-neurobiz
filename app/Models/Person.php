<?php

namespace App\Models;

use App\Support\PhoneNormalizer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Person extends Model
{
    use HasFactory;

    protected $table = 'people';

    protected $fillable = [
        'owner_id',
        'name',
        'phone_e164',
        'phone_raw',
        'email',
        'source',
        'source_id',
        'next_action',
        'next_action_at',
        'chatwoot_contact_id',
        'chatwoot_identifier',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'next_action_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Person $person) {
            $person->normalizeIdentity();
        });
    }

    public function normalizeIdentity(): void
    {
        if ($this->phone_raw) {
            $this->phone_e164 = PhoneNormalizer::toE164($this->phone_raw) ?? $this->phone_e164;
        } elseif ($this->phone_e164) {
            $this->phone_e164 = PhoneNormalizer::toE164($this->phone_e164);
        }

        if ($this->email) {
            $this->email = strtolower(trim($this->email));
        }

        if (! $this->source_id && $this->source && ($this->isDirty('source') || ! $this->exists)) {
            $sourceId = LeadSource::query()->where('name', $this->source)->value('id');
            if ($sourceId) {
                $this->source_id = $sourceId;
            }
        }

        if ($this->phoneNormalizedIsGenerated()) {
            unset($this->attributes['phone_normalized']);

            return;
        }

        $this->phone_normalized = PhoneNormalizer::normalized($this->phone_raw ?: $this->phone_e164);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function leadSource(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'source_id');
    }

    public function sourceLabel(): string
    {
        if ($this->relationLoaded('leadSource') && $this->leadSource) {
            return $this->leadSource->name;
        }

        return $this->source ?: '—';
    }

    public function requiresResponse(): bool
    {
        if ($this->next_action_at === null) {
            return false;
        }

        if ($this->next_action_at->copy()->startOfDay()->gt(today())) {
            return false;
        }

        if ($this->relationLoaded('activities')) {
            return $this->activities->contains(
                fn (Activity $activity) => $activity->isOpenInboundWhatsapp()
            );
        }

        if (array_key_exists('has_open_whatsapp', $this->attributes)) {
            return (bool) $this->has_open_whatsapp;
        }

        return $this->activities()
            ->where('is_done', false)
            ->whereHas('channel', fn ($query) => $query->where('name', 'WhatsApp'))
            ->exists();
    }

    private function phoneNormalizedIsGenerated(): bool
    {
        return in_array($this->getConnection()->getDriverName(), ['mysql', 'sqlite'], true);
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_person')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function identities(): HasMany
    {
        return $this->hasMany(ExternalIdentity::class);
    }

    public function openDeal(): ?Deal
    {
        return $this->deals()->where('status', 'open')->latest()->first();
    }
}
