<?php

namespace App\Livewire\Deals;

use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\Stage;
use App\Services\DealService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Pipeline')]
class Kanban extends Component
{
    #[Url]
    public ?int $pipelineId = null;

    public function mount(): void
    {
        $this->pipelineId ??= Pipeline::query()->where('is_default', true)->value('id')
            ?? Pipeline::query()->value('id');
    }

    public function move(int $dealId, int $stageId, DealService $deals): void
    {
        $deal = Deal::query()->findOrFail($dealId);
        $deals->changeStage($deal, $stageId);
    }

    public function render()
    {
        $pipelines = Pipeline::query()->with('stages')->orderBy('id')->get();
        $pipeline = $pipelines->firstWhere('id', $this->pipelineId) ?? $pipelines->first();

        $deals = Deal::query()
            ->with(['person', 'offering', 'owner'])
            ->where('pipeline_id', $pipeline?->id)
            ->where('status', '!=', 'lost')
            ->orderByDesc('updated_at')
            ->get()
            ->groupBy('stage_id');

        return view('livewire.deals.kanban', compact('pipelines', 'pipeline', 'deals'));
    }
}
