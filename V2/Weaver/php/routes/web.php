<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\Auth\SelfAuthController;

Route::get('/', function () {
    return view('home');
});

Route::get('/auth/{provider}/redirect', [OAuthController::class, 'redirect'])->name('auth.redirect');
Route::get('/auth/{provider}/callback', [OAuthController::class, 'callback'])->name('auth.callback');
Route::get('/auth/collect-email', [OAuthController::class, 'collectEmailForm'])->name('auth.collect-email');
Route::post('/auth/collect-email', [OAuthController::class, 'collectEmailSubmit'])->name('auth.collect-email.submit');
Route::get('/auth/reauth/{provider}', [OAuthController::class, 'reauth'])->name('auth.reauth');

Route::middleware(['throttle:6,1'])->group(function () {
    Route::post('/auth/self/login', [SelfAuthController::class, 'login']);
    Route::post('/auth/self/register', [SelfAuthController::class, 'register']);
    Route::post('/auth/collect-email', [OAuthController::class, 'collectEmailSubmit'])->name('auth.collect-email.submit');
});

Route::post('/auth/logout', [SelfAuthController::class, 'logout']);

Route::get('/_cors-debug', function () {
    return response()->json([
        'type'   => gettype(config('cors')),
        'paths'  => config('cors.paths'),
        'origins'=> config('cors.allowed_origins'),
        'methods'=> config('cors.allowed_methods'),
    ]);
});
