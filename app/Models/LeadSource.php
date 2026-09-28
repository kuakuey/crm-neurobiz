<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeadSource extends Model
{
    protected $fillable = [
        'name',
    ];

    public function people(): HasMany
    {
        return $this->hasMany(Person::class, 'source_id');
    }
}
