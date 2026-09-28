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
        'chatwoot_contact_id',
        'chatwoot_identifier',
        'notes',
    ];

    protected static function booted(): void
    {
        static::saving(function (Person $person) {
            if ($person->phone_raw) {
                $person->phone_e164 = PhoneNormalizer::toE164($person->phone_raw) ?? $person->phone_e164;
            } elseif ($person->phone_e164) {
                $person->phone_e164 = PhoneNormalizer::toE164($person->phone_e164);
            }

            if ($person->email) {
                $person->email = strtolower(trim($person->email));
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
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
