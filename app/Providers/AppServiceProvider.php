<?php

namespace App\Providers;

use App\Models\Deal;
use App\Models\Person;
use App\Observers\DealObserver;
use App\Observers\PersonObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Person::observe(PersonObserver::class);
        Deal::observe(DealObserver::class);
    }
}
