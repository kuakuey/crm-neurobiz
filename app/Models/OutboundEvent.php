<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutboundEvent extends Model
{
    protected $fillable = [
        'event_id',
        'type',
        'payload',
        'status',
        'attempts',
        'last_error',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function markSent(): void
    {
        $this->status = 'sent';
        $this->sent_at = now();
        $this->last_error = null;
        $this->save();
    }

    public function markFailed(string $error): void
    {
        $this->status = 'failed';
        $this->last_error = $error;
        $this->attempts++;
        $this->save();
    }
}
