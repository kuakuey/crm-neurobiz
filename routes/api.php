<?php

use App\Http\Controllers\Api\V1\ActivitiesController;
use App\Http\Controllers\Api\V1\DealsController;
use App\Http\Controllers\Api\V1\PeopleController;
use App\Http\Controllers\Api\V1\WebhookLogsController;
use App\Http\Controllers\Api\V1\WhatsappIngestionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('/people/{person}', [PeopleController::class, 'show']);
    Route::post('/people', [PeopleController::class, 'store']);

    Route::post('/deals', [DealsController::class, 'store']);
    Route::patch('/deals/{deal}/stage', [DealsController::class, 'changeStage']);

    Route::post('/activities', [ActivitiesController::class, 'store']);
    Route::post('/notes', [ActivitiesController::class, 'store']);

    Route::get('/channels', [WhatsappIngestionController::class, 'channels']);
    Route::get('/lead-sources', [WhatsappIngestionController::class, 'leadSources']);
    Route::post('/contacts/find-duplicates', [WhatsappIngestionController::class, 'findDuplicates']);
    Route::post('/whatsapp/messages', [WhatsappIngestionController::class, 'store']);

    Route::get('/webhooks/inbound', [WebhookLogsController::class, 'inbound']);
    Route::post('/events/{event}/retry', [WebhookLogsController::class, 'retryOutbound']);
});
