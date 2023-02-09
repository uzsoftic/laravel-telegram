<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Service\Telegram\Default\DefaultBot;

// Telegram API Routes
Route::prefix('telegram')->group(function () {

    // Default bot route
    Route::prefix('default')->group(function (){
        Route::any('/', [DefaultBot::class, 'index']);
        Route::get('/test', [DefaultBot::class, 'test']);
        Route::get('/setWebhook', [DefaultBot::class, 'setWebhook']);
    });

});
