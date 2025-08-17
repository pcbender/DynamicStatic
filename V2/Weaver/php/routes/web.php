<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\Auth\SelfAuthController;
use App\Http\Controllers\ProjectSetupController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () { return redirect()->route('dashboard'); });

// Auth view routes
Route::get('/login', function(){ return view('auth.login'); })->name('login');
Route::get('/register', function(){ return view('auth.register'); })->name('register');

Route::get('/auth/{provider}/redirect', [OAuthController::class, 'redirect'])->name('auth.redirect');
Route::get('/auth/{provider}/callback', [OAuthController::class, 'callback'])->name('auth.callback');
Route::get('/auth/collect-email', [OAuthController::class, 'collectEmailForm'])->name('auth.collect-email');
Route::post('/auth/collect-email', [OAuthController::class, 'collectEmailSubmit'])->name('auth.collect-email.submit');
Route::get('/auth/reauth/{provider}', [OAuthController::class, 'reauth'])->name('auth.reauth');

Route::middleware(['throttle:6,1'])->group(function () {
    Route::post('/auth/self/login', [SelfAuthController::class, 'login'])->name('auth.self.login');
    Route::post('/auth/self/register', [SelfAuthController::class, 'register'])->name('auth.self.register');
    Route::post('/auth/collect-email', [OAuthController::class, 'collectEmailSubmit'])->name('auth.collect-email.submit');
});

Route::post('/auth/logout', [SelfAuthController::class, 'logout'])->name('logout');

Route::middleware(['auth', \App\Http\Middleware\EnsureProjectOrRedirect::class])->group(function () {
    Route::get('/project/setup', [ProjectSetupController::class, 'setupForm'])->name('project.setup');
    Route::post('/project/setup', [ProjectSetupController::class, 'setupStore'])->name('project.setup.store');
    Route::get('/project/edit', [ProjectSetupController::class, 'editForm'])->name('project.edit');
    Route::match(['post','patch'],'/project', [ProjectSetupController::class, 'update'])->name('project.update');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

// Route::get('/_cors-debug', function () { /* disabled in iteration */ });
