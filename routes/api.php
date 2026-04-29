<?php

use App\Http\Controllers\Api\V1\MessageStatusController;
use App\Http\Controllers\Api\V1\SendBulkController;
use App\Http\Controllers\Api\V1\SendMessageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Public API v1 endpoints for external integrations.
| All routes require API token authentication and the 'api' feature.
|
*/

Route::prefix('v1')->middleware(['api.token', 'feature:api'])->group(function () {
    Route::post('/send-message', SendMessageController::class)->name('api.v1.send-message');
    Route::post('/send-bulk', SendBulkController::class)->name('api.v1.send-bulk');
    Route::get('/message-status/{jobId}', MessageStatusController::class)->name('api.v1.message-status');
});
