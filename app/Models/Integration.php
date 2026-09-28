<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    protected $fillable = [
        'type',
        'name',
        'base_url',
        'credentials',
        'settings',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public static function ofType(string $type): ?self
    {
        return static::query()->where('type', $type)->first();
    }

    public static function active(string $type): ?self
    {
        return static::query()->where('type', $type)->where('is_active', true)->first();
    }

    public function credential(string $key, mixed $default = null): mixed
    {
        return data_get($this->credentials, $key, $default);
    }
}
