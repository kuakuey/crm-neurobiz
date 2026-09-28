<?php

namespace App\Livewire\People;

use App\Models\Person;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Personas')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $q = '';

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $people = Person::query()
            ->with('owner')
            ->when($this->q, function ($query) {
                $term = '%'.$this->q.'%';
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone_e164', 'like', $term)
                        ->orWhere('phone_raw', 'like', $term);
                });
            })
            ->latest()
            ->paginate(15);

        return view('livewire.people.index', compact('people'));
    }
}
