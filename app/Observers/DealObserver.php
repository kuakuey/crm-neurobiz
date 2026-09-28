<?php

namespace App\Observers;

use App\Models\Deal;
use App\Services\Chatwoot\ChatwootSyncService;
use App\Services\EventDispatcher;

class DealObserver
{
    public bool $afterCommit = false;

    public function __construct(
        private EventDispatcher $dispatcher,
        private ChatwootSyncService $chatwoot,
    ) {
    }

    public function created(Deal $deal): void
    {
        $deal->loadMissing(['person', 'stage', 'offering', 'owner']);
        $this->dispatcher->dispatch('deal.created', $this->payload($deal));
        $this->chatwoot->syncDeal($deal);
    }

    public function updated(Deal $deal): void
    {
        $deal->loadMissing(['person', 'stage', 'offering', 'owner']);

        if ($deal->wasChanged('stage_id')) {
            $this->dispatcher->dispatch('deal.stage_changed', $this->payload($deal) + [
                'previous_stage_id' => $deal->getOriginal('stage_id'),
            ]);
        }

        if ($deal->wasChanged('status') && $deal->status === 'won') {
            $this->dispatcher->dispatch('deal.won', $this->payload($deal));
        }

        if ($deal->wasChanged('status') && $deal->status === 'lost') {
            $this->dispatcher->dispatch('deal.lost', $this->payload($deal));
        }

        if ($deal->wasChanged(['stage_id', 'status', 'title', 'amount'])) {
            $this->chatwoot->syncDeal($deal);
        }
    }

    private function payload(Deal $deal): array
    {
        return [
            'deal' => [
                'id' => $deal->id,
                'title' => $deal->title,
                'status' => $deal->status,
                'amount' => $deal->amount,
                'stage' => $deal->stage?->slug,
                'stage_name' => $deal->stage?->name,
                'offering' => $deal->offering?->slug,
                'person_id' => $deal->person_id,
                'organization_id' => $deal->organization_id,
                'owner_id' => $deal->owner_id,
                'chatwoot_conversation_id' => $deal->chatwoot_conversation_id,
            ],
            'person' => $deal->person ? [
                'id' => $deal->person->id,
                'name' => $deal->person->name,
                'phone_e164' => $deal->person->phone_e164,
                'email' => $deal->person->email,
            ] : null,
        ];
    }
}
