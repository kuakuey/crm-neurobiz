<?php

namespace App\Livewire\People;

use App\Models\Organization;
use App\Models\Person;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?int $personId = null;

    public string $name = '';

    public string $phone_raw = '';

    public string $email = '';

    public string $source = 'manual';

    public ?int $owner_id = null;

    public string $notes = '';

    public ?int $organization_id = null;

    public string $org_role = 'decisor';

    public function mount(?Person $person = null): void
    {
        $this->owner_id = auth()->id();

        if ($person?->exists) {
            $this->personId = $person->id;
            $this->name = $person->name;
            $this->phone_raw = $person->phone_raw ?: ($person->phone_e164 ?? '');
            $this->email = $person->email ?? '';
            $this->source = $person->source;
            $this->owner_id = $person->owner_id;
            $this->notes = $person->notes ?? '';
            $org = $person->organizations()->first();
            $this->organization_id = $org?->id;
            $this->org_role = $org?->pivot->role ?? 'decisor';
        }
    }

    public function save()
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone_raw' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email'],
            'source' => ['required', 'string'],
            'owner_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
        ]);

        $person = Person::query()->updateOrCreate(
            ['id' => $this->personId],
            $data
        );

        if ($this->organization_id) {
            $person->organizations()->syncWithoutDetaching([
                $this->organization_id => ['role' => $this->org_role],
            ]);
        }

        session()->flash('status', 'Persona guardada.');

        return $this->redirect(route('people.show', $person), navigate: true);
    }

    public function render()
    {
        return view('livewire.people.form', [
            'users' => User::query()->orderBy('name')->get(),
            'organizations' => Organization::query()->orderBy('name')->get(),
        ])->title($this->personId ? 'Editar persona' : 'Nueva persona');
    }
}
