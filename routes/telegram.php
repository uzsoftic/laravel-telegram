<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Service\Telegram\Default\DefaultBot;

// Telegram API Routes
Route::prefix('telegram')->group(function () {

    Route::any('/default', [DefaultBot::class, 'index']);
    Route::get('/default/test', [DefaultBot::class, 'test']);
    Route::get('/default/setWebhook', [DefaultBot::class, 'setWebhook']);

});
