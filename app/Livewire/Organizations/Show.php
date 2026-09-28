<?php

namespace App\Livewire\Organizations;

use App\Models\Organization;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Empresa')]
class Show extends Component
{
    public Organization $organization;

    public function mount(Organization $organization): void
    {
        $this->organization = $organization->load(['people', 'deals.stage', 'owner']);
    }

    public function render()
    {
        return view('livewire.organizations.show');
    }
}
