<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id',
        'deal_id',
        'user_id',
        'type',
        'title',
        'body',
        'due_at',
        'done_at',
        'is_done',
        'overdue_event_sent',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'done_at' => 'datetime',
            'is_done' => 'boolean',
            'overdue_event_sent' => 'boolean',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markDone(): void
    {
        $this->is_done = true;
        $this->done_at = now();
        $this->save();
    }

    public function scopePending($query)
    {
        return $query->where('is_done', false);
    }

    public function scopeDueToday($query)
    {
        return $query->pending()->whereDate('due_at', today());
    }

    public function scopeOverdue($query)
    {
        return $query->pending()->whereNotNull('due_at')->where('due_at', '<', now());
    }
}
