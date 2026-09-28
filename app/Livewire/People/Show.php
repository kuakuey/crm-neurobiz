<?php

namespace App\Livewire\People;

use App\Models\Activity;
use App\Models\Person;
use App\Models\User;
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

    public ?string $assigneeId = null;

    public ?string $assignError = null;

    public function mount(Person $person): void
    {
        $this->person = $person;
        $this->reload();
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
        $this->reload();
        session()->flash('status', 'Actividad registrada.');
    }

    public function assignOwner(): void
    {
        abort_unless(auth()->user()?->canViewUnassignedLeads(), 403);

        $this->assignError = null;
        if (! $this->assigneeId || ! User::query()->whereKey($this->assigneeId)->exists()) {
            $this->assignError = 'Elige un gestor.';

            return;
        }

        $this->person->owner_id = (int) $this->assigneeId;
        $this->person->save();
        $this->reload();
        session()->flash('status', 'Gestor asignado.');
    }

    public function render()
    {
        $canAssign = (bool) auth()->user()?->canViewUnassignedLeads();

        return view('livewire.people.show', [
            'canAssign' => $canAssign,
            'users' => $canAssign ? User::query()->orderBy('name')->get(['id', 'name']) : collect(),
        ]);
    }

    private function reload(): void
    {
        $this->person->refresh()->load([
            'owner',
            'leadSource',
            'organizations',
            'deals.stage',
            'deals.offering',
            'activities.user',
            'activities.channel',
        ]);
    }
}
