<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('crm:dispatch-events')->everyMinute();
Schedule::command('crm:mark-overdue')->hourly();
