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
#[Title('Contactos')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $q = '';

    #[Url]
    public string $filtro = '';

    public array $assignee = [];

    public ?string $assignError = null;

    public function mount(): void
    {
        $this->guardUnassignedFilter();
    }

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function updatingFiltro(): void
    {
        $this->resetPage();
    }

    public function assignOwner(int $personId): void
    {
        abort_unless(auth()->user()?->canViewUnassignedLeads(), 403);

        $this->assignError = null;
        $userId = $this->assignee[$personId] ?? null;
        if (! $userId || ! User::query()->whereKey($userId)->exists()) {
            $this->assignError = 'Elige un gestor.';

            return;
        }

        $person = Person::query()->findOrFail($personId);
        $person->owner_id = (int) $userId;
        $person->save();
        unset($this->assignee[$personId]);
        session()->flash('status', 'Gestor asignado.');
    }

    public function render()
    {
        $this->guardUnassignedFilter();

        $canViewUnassigned = (bool) auth()->user()?->canViewUnassignedLeads();

        $people = Person::query()
            ->with(['owner', 'leadSource'])
            ->withExists([
                'activities as has_open_whatsapp' => fn ($query) => $query
                    ->where('is_done', false)
                    ->whereHas('channel', fn ($channel) => $channel->where('name', 'WhatsApp')),
            ])
            ->when($this->filtro === 'sin-asignar', fn ($query) => $query->whereNull('owner_id'))
            ->when($this->q, function ($query) {
                $term = '%'.$this->q.'%';
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone_e164', 'like', $term)
                        ->orWhere('phone_raw', 'like', $term)
                        ->orWhere('phone_normalized', 'like', $term);
                });
            })
            ->latest()
            ->paginate(15);

        return view('livewire.people.index', [
            'people' => $people,
            'canViewUnassigned' => $canViewUnassigned,
            'users' => $canViewUnassigned
                ? User::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }

    private function guardUnassignedFilter(): void
    {
        if (! in_array($this->filtro, ['', 'sin-asignar'], true)) {
            $this->filtro = '';
        }

        if ($this->filtro === 'sin-asignar' && ! auth()->user()?->canViewUnassignedLeads()) {
            abort(403, 'No tienes permiso para ver leads sin asignar.');
        }
    }
}
