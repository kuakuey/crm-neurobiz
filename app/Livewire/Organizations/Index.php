<?php

namespace App\Livewire\Organizations;

use App\Models\Organization;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Empresas')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $q = '';

    public function render()
    {
        $organizations = Organization::query()
            ->withCount('people')
            ->when($this->q, fn ($q) => $q->where('name', 'like', '%'.$this->q.'%'))
            ->latest()
            ->paginate(15);

        return view('livewire.organizations.index', compact('organizations'));
    }
}
