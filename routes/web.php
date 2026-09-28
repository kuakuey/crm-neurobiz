<?php

use App\Livewire\Activities\Index as ActivitiesIndex;
use App\Livewire\Auth\Login;
use App\Livewire\Dashboard;
use App\Livewire\Deals\Form as DealForm;
use App\Livewire\Deals\Kanban;
use App\Livewire\Deals\Show as DealShow;
use App\Livewire\Embed\ChatwootPanel;
use App\Livewire\Organizations\Form as OrganizationForm;
use App\Livewire\Organizations\Index as OrganizationsIndex;
use App\Livewire\Organizations\Show as OrganizationShow;
use App\Livewire\People\Form as PersonForm;
use App\Livewire\People\Index as PeopleIndex;
use App\Livewire\People\Show as PersonShow;
use App\Livewire\Reports\Direction;
use App\Livewire\Settings\Integrations;
use App\Http\Controllers\Webhooks\ChatwootWebhookController;
use App\Http\Controllers\Webhooks\DiagnosticWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/login', Login::class)->name('login')->middleware('guest');

Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->name('logout')->middleware('auth');

Route::post('/webhooks/chatwoot', ChatwootWebhookController::class)->name('webhooks.chatwoot');
Route::post('/webhooks/diagnostico', DiagnosticWebhookController::class)->name('webhooks.diagnostico');

Route::get('/embed/chatwoot', ChatwootPanel::class)->name('embed.chatwoot');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::get('/people', PeopleIndex::class)->name('people.index');
    Route::get('/people/create', PersonForm::class)->name('people.create');
    Route::get('/people/{person}/edit', PersonForm::class)->name('people.edit');
    Route::get('/people/{person}', PersonShow::class)->name('people.show');

    Route::get('/organizations', OrganizationsIndex::class)->name('organizations.index');
    Route::get('/organizations/create', OrganizationForm::class)->name('organizations.create');
    Route::get('/organizations/{organization}/edit', OrganizationForm::class)->name('organizations.edit');
    Route::get('/organizations/{organization}', OrganizationShow::class)->name('organizations.show');

    Route::get('/deals', Kanban::class)->name('deals.index');
    Route::get('/deals/create', DealForm::class)->name('deals.create');
    Route::get('/deals/{deal}', DealShow::class)->name('deals.show');

    Route::get('/activities', ActivitiesIndex::class)->name('activities.index');
    Route::get('/reports', Direction::class)->name('reports.index');
    Route::get('/settings/integrations', Integrations::class)->name('settings.integrations');
});
