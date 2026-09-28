<?php

namespace App\Livewire\Activities;

use App\Models\Activity;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Actividades')]
class Index extends Component
{
    use WithPagination;

    public string $filter = 'pending';

    public function complete(int $id): void
    {
        $activity = Activity::query()->findOrFail($id);
        $activity->markDone();
    }

    public function render()
    {
        $query = Activity::query()->with(['person', 'deal', 'user'])->latest('due_at');

        $query = match ($this->filter) {
            'today' => $query->dueToday(),
            'overdue' => $query->overdue(),
            'done' => $query->where('is_done', true),
            default => $query->pending(),
        };

        return view('livewire.activities.index', [
            'activities' => $query->paginate(20),
        ]);
    }
}
