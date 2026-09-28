<?php

namespace App\Observers;

use App\Models\Person;
use App\Services\EventDispatcher;

class PersonObserver
{
    public function __construct(private EventDispatcher $dispatcher)
    {
    }

    public bool $afterCommit = false;

    public function created(Person $person): void
    {
        $this->dispatcher->dispatch('person.created', $this->payload($person));
    }

    public function updated(Person $person): void
    {
        $this->dispatcher->dispatch('person.updated', $this->payload($person));
    }

    private function payload(Person $person): array
    {
        return [
            'person' => [
                'id' => $person->id,
                'name' => $person->name,
                'phone_e164' => $person->phone_e164,
                'email' => $person->email,
                'source' => $person->source,
                'owner_id' => $person->owner_id,
                'chatwoot_contact_id' => $person->chatwoot_contact_id,
            ],
        ];
    }
}
