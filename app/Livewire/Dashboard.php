<?php

namespace App\Livewire;

use App\Models\Activity;
use App\Models\Deal;
use App\Models\Person;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Inicio')]
class Dashboard extends Component
{
    public function render()
    {
        $openDeals = Deal::query()->where('status', 'open');

        return view('livewire.dashboard', [
            'leadsThisWeek' => Person::query()->where('created_at', '>=', now()->startOfWeek())->count(),
            'openDeals' => (clone $openDeals)->count(),
            'pipelineValue' => (clone $openDeals)->sum('amount'),
            'tasksToday' => Activity::query()->dueToday()->count(),
            'overdue' => Activity::query()->overdue()->count(),
            'recentPeople' => Person::query()->latest()->limit(6)->get(),
            'todayTasks' => Activity::query()->pending()->where(function ($q) {
                $q->whereDate('due_at', today())
                    ->orWhere(fn ($q2) => $q2->whereNotNull('due_at')->where('due_at', '<', now()));
            })->with(['person', 'deal'])->orderBy('due_at')->limit(8)->get(),
        ]);
    }
}
