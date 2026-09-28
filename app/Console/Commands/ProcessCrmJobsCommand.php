<?php

namespace App\Console\Commands;

use App\Jobs\DispatchOutboundEventJob;
use App\Models\Activity;
use App\Models\OutboundEvent;
use App\Services\EventDispatcher;
use Illuminate\Console\Command;

class ProcessCrmJobsCommand extends Command
{
    protected $signature = 'crm:dispatch-events';

    protected $description = 'Reintenta eventos outbound pendientes hacia n8n';

    public function handle(): int
    {
        OutboundEvent::query()
            ->where('status', 'pending')
            ->orWhere(fn ($q) => $q->where('status', 'failed')->where('attempts', '<', 5))
            ->orderBy('id')
            ->limit(50)
            ->get()
            ->each(fn (OutboundEvent $event) => DispatchOutboundEventJob::dispatch($event));

        return self::SUCCESS;
    }
}
