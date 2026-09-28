<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Services\EventDispatcher;
use Illuminate\Console\Command;

class MarkOverdueActivitiesCommand extends Command
{
    protected $signature = 'crm:mark-overdue';

    protected $description = 'Emite activity.overdue para tareas vencidas';

    public function handle(EventDispatcher $dispatcher): int
    {
        $activities = Activity::query()
            ->overdue()
            ->where('overdue_event_sent', false)
            ->limit(100)
            ->get();

        foreach ($activities as $activity) {
            $dispatcher->dispatch('activity.overdue', [
                'activity' => [
                    'id' => $activity->id,
                    'title' => $activity->title,
                    'due_at' => $activity->due_at?->toIso8601String(),
                    'person_id' => $activity->person_id,
                    'deal_id' => $activity->deal_id,
                    'user_id' => $activity->user_id,
                ],
            ]);
            $activity->overdue_event_sent = true;
            $activity->save();
        }

        $this->info('Eventos overdue: '.$activities->count());

        return self::SUCCESS;
    }
}
