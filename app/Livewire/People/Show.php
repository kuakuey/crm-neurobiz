<?php

namespace App\Livewire\People;

use App\Models\Activity;
use App\Models\Person;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Ficha de persona')]
class Show extends Component
{
    public Person $person;

    public string $taskTitle = '';

    public string $taskType = 'task';

    public ?string $taskDue = null;

    public function mount(Person $person): void
    {
        $this->person = $person->load(['owner', 'organizations', 'deals.stage', 'deals.offering', 'activities.user']);
    }

    public function addActivity(): void
    {
        $this->validate([
            'taskTitle' => ['required', 'string', 'max:255'],
            'taskType' => ['required', 'string'],
            'taskDue' => ['nullable', 'date'],
        ]);

        Activity::query()->create([
            'person_id' => $this->person->id,
            'deal_id' => $this->person->openDeal()?->id,
            'user_id' => auth()->id(),
            'type' => $this->taskType,
            'title' => $this->taskTitle,
            'due_at' => $this->taskDue,
            'is_done' => $this->taskType === 'note',
            'done_at' => $this->taskType === 'note' ? now() : null,
        ]);

        $this->taskTitle = '';
        $this->taskDue = null;
        $this->person->refresh()->load(['activities.user', 'deals.stage']);
        session()->flash('status', 'Actividad registrada.');
    }

    public function render()
    {
        return view('livewire.people.show');
    }
}
