<?php

namespace App\Livewire\Organizations;

use App\Models\Organization;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?int $organizationId = null;

    public string $name = '';

    public string $industry = '';

    public string $size = '';

    public string $city = '';

    public string $notes = '';

    public function mount(?Organization $organization = null): void
    {
        if ($organization?->exists) {
            $this->organizationId = $organization->id;
            $this->fill($organization->only(['name', 'industry', 'size', 'city', 'notes']));
        }
    }

    public function save()
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:120'],
            'size' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string'],
        ]);

        $organization = Organization::query()->updateOrCreate(
            ['id' => $this->organizationId],
            [...$data, 'owner_id' => auth()->id()]
        );

        return $this->redirect(route('organizations.show', $organization), navigate: true);
    }

    public function render()
    {
        return view('livewire.organizations.form')
            ->title($this->organizationId ? 'Editar empresa' : 'Nueva empresa');
    }
}
