<?php

namespace App\Livewire\Deals;

use App\Models\Offering;
use App\Models\Organization;
use App\Models\Person;
use App\Models\Pipeline;
use App\Models\User;
use App\Services\DealService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?int $person_id = null;

    public ?int $organization_id = null;

    public ?int $offering_id = null;

    public ?int $pipeline_id = null;

    public ?int $stage_id = null;

    public ?int $owner_id = null;

    public string $title = '';

    public ?string $amount = null;

    public ?string $close_date = null;

    public string $source = 'manual';

    public function mount(): void
    {
        $this->owner_id = auth()->id();
        $this->person_id = request()->integer('person_id') ?: null;
        $this->pipeline_id = Pipeline::query()->where('is_default', true)->value('id');
        $this->updatedPipelineId($this->pipeline_id);
    }

    public function updatedPipelineId($value): void
    {
        $pipeline = Pipeline::query()->with('stages')->find($value);
        $this->stage_id = $pipeline?->firstStage()?->id;
    }

    public function updatedOfferingId($value): void
    {
        $offering = Offering::query()->find($value);
        if ($offering) {
            if (! $this->title) {
                $this->title = $offering->name;
            }
            if (! $this->amount && $offering->default_amount) {
                $this->amount = (string) $offering->default_amount;
            }
            if ($offering->pipeline_id) {
                $this->pipeline_id = $offering->pipeline_id;
                $this->updatedPipelineId($this->pipeline_id);
            }
        }
    }

    public function save(DealService $deals)
    {
        $data = $this->validate([
            'person_id' => ['nullable', 'integer', 'exists:people,id'],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'offering_id' => ['nullable', 'integer', 'exists:offerings,id'],
            'pipeline_id' => ['required', 'integer', 'exists:pipelines,id'],
            'stage_id' => ['required', 'integer', 'exists:stages,id'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric'],
            'close_date' => ['nullable', 'date'],
            'source' => ['required', 'string'],
        ]);

        $deal = $deals->create($data);

        return $this->redirect(route('deals.show', $deal), navigate: true);
    }

    public function render()
    {
        $pipelines = Pipeline::query()->with('stages')->get();

        return view('livewire.deals.form', [
            'people' => Person::query()->orderBy('name')->get(),
            'organizations' => Organization::query()->orderBy('name')->get(),
            'offerings' => Offering::query()->orderBy('name')->get(),
            'users' => User::query()->orderBy('name')->get(),
            'pipelines' => $pipelines,
            'stages' => $pipelines->firstWhere('id', $this->pipeline_id)?->stages ?? collect(),
        ])->title('Nuevo deal');
    }
}
