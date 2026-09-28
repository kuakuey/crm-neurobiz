<?php

namespace App\Livewire\Deals;

use App\Models\Activity;
use App\Models\Deal;
use App\Models\Stage;
use App\Services\DealService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Deal')]
class Show extends Component
{
    public Deal $deal;

    public ?int $stage_id = null;

    public string $taskTitle = '';

    public string $taskType = 'task';

    public ?string $taskDue = null;

    public string $lost_reason = '';

    public function mount(Deal $deal): void
    {
        $this->deal = $deal->load(['person', 'organization', 'offering', 'pipeline.stages', 'stage', 'owner', 'activities.user']);
        $this->stage_id = $deal->stage_id;
    }

    public function changeStage(DealService $deals): void
    {
        $this->validate(['stage_id' => ['required', 'integer']]);
        $this->deal = $deals->changeStage($this->deal, (int) $this->stage_id);
        $this->deal->load(['pipeline.stages', 'activities.user', 'person', 'offering']);
        session()->flash('status', 'Etapa actualizada.');
    }

    public function addActivity(): void
    {
        $this->validate(['taskTitle' => ['required', 'string', 'max:255']]);

        Activity::query()->create([
            'person_id' => $this->deal->person_id,
            'deal_id' => $this->deal->id,
            'user_id' => auth()->id(),
            'type' => $this->taskType,
            'title' => $this->taskTitle,
            'due_at' => $this->taskDue,
            'is_done' => $this->taskType === 'note',
            'done_at' => $this->taskType === 'note' ? now() : null,
        ]);

        $this->taskTitle = '';
        $this->deal->refresh()->load(['activities.user', 'pipeline.stages']);
    }

    public function render()
    {
        return view('livewire.deals.show');
    }
}
