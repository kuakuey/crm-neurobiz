<?php

namespace App\Livewire\Embed;

use App\Models\Activity;
use App\Models\Deal;
use App\Models\Person;
use App\Models\Stage;
use App\Models\User;
use App\Services\Chatwoot\ChatwootSyncService;
use App\Services\DealService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.embed')]
class ChatwootPanel extends Component
{
    public ?int $personId = null;

    public ?int $dealId = null;

    public string $status = 'Esperando contexto de Chatwoot…';

    public string $origin = '';

    public string $taskTitle = '';

    public function hydrateFromContext(array $contact, array $conversation, ?array $agent, ChatwootSyncService $sync): void
    {
        $resolved = $sync->resolveFromContext($contact, $conversation);
        $this->personId = $resolved['person']->id;
        $this->dealId = $resolved['deal']->id;
        $this->status = '';

        if (! empty($agent['email'])) {
            $user = User::query()->where('email', $agent['email'])->first();
            if ($user && $resolved['person']->owner_id !== $user->id && ! $resolved['person']->owner_id) {
                $resolved['person']->owner_id = $user->id;
                $resolved['person']->saveQuietly();
            }
        }
    }

    public function changeStage(int $stageId, DealService $deals): void
    {
        if (! $this->dealId) {
            return;
        }
        $deal = Deal::query()->findOrFail($this->dealId);
        $deals->changeStage($deal, $stageId);
        $this->dealId = $deal->id;
    }

    public function addTask(): void
    {
        if (! $this->personId || $this->taskTitle === '') {
            return;
        }

        Activity::query()->create([
            'person_id' => $this->personId,
            'deal_id' => $this->dealId,
            'user_id' => auth()->id() ?? User::query()->value('id'),
            'type' => 'task',
            'title' => $this->taskTitle,
            'due_at' => now()->addDay(),
            'is_done' => false,
        ]);

        $this->taskTitle = '';
    }

    public function render()
    {
        $person = $this->personId ? Person::query()->with('organizations')->find($this->personId) : null;
        $deal = $this->dealId ? Deal::query()->with(['stage', 'pipeline.stages', 'offering'])->find($this->dealId) : null;
        $stages = $deal?->pipeline?->stages ?? collect();

        return view('livewire.embed.chatwoot-panel', compact('person', 'deal', 'stages'))
            ->title('CRM Neurobiz');
    }
}
