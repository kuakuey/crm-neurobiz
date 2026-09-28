<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InboundWebhookLog extends Model
{
    protected $fillable = [
        'provider',
        'event_type',
        'external_event_id',
        'signature_ok',
        'processed',
        'skip_reason',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'signature_ok' => 'boolean',
            'processed' => 'boolean',
            'payload' => 'array',
        ];
    }
}
