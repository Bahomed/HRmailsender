<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MailSenderController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Health check - no authentication required
Route::get('/health', [MailSenderController::class, 'healthCheck']);

// Send email endpoint - requires API key, IP whitelist, and rate limiting
Route::post('/send-email', [MailSenderController::class, 'sendEmail'])
    ->middleware(['api.key', 'ip.whitelist', 'throttle:60,1']);
