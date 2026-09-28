<?php

namespace App\Livewire\Reports;

use App\Models\Deal;
use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dirección')]
class Direction extends Component
{
    public function render()
    {
        $byOffering = Deal::query()
            ->select('offerings.name', 'offerings.type', DB::raw('count(deals.id) as total'), DB::raw("sum(case when deals.status = 'won' then 1 else 0 end) as won"))
            ->leftJoin('offerings', 'offerings.id', '=', 'deals.offering_id')
            ->groupBy('offerings.id', 'offerings.name', 'offerings.type')
            ->get();

        $byOwner = Deal::query()
            ->select('users.name', DB::raw('count(deals.id) as total'), DB::raw("sum(case when deals.status = 'open' then 1 else 0 end) as open_count"))
            ->leftJoin('users', 'users.id', '=', 'deals.owner_id')
            ->groupBy('users.id', 'users.name')
            ->get();

        $b2b = Deal::query()->whereHas('pipeline', fn ($q) => $q->where('slug', 'neurobusiness-b2b'));
        $b2c = Deal::query()->whereHas('pipeline', fn ($q) => $q->where('slug', 'programas-b2c'));

        $retainers = Deal::query()
            ->where('status', 'won')
            ->whereHas('offering', fn ($q) => $q->where('is_retainer', true))
            ->with(['person', 'offering'])
            ->latest()
            ->limit(10)
            ->get();

        return view('livewire.reports.direction', [
            'leadsWeek' => Person::query()->where('created_at', '>=', now()->startOfWeek())->count(),
            'b2bOpen' => (clone $b2b)->where('status', 'open')->count(),
            'b2bWon' => (clone $b2b)->where('status', 'won')->count(),
            'b2cOpen' => (clone $b2c)->where('status', 'open')->count(),
            'b2cWon' => (clone $b2c)->where('status', 'won')->count(),
            'byOffering' => $byOffering,
            'byOwner' => $byOwner,
            'retainers' => $retainers,
            'staleDeals' => Deal::query()
                ->where('status', 'open')
                ->where('stage_changed_at', '<', now()->subDays(7))
                ->with(['person', 'stage', 'owner'])
                ->orderBy('stage_changed_at')
                ->limit(10)
                ->get(),
        ]);
    }
}
