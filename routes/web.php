<?php

use App\Services\RemoteApi\TokenManager;
use Illuminate\Support\Facades\Route;

Route::get('/login', fn() => view('login'))->name('login');

Route::middleware('api.auth')->group(function () {
    Route::get('/', fn() => view('test'))->name('home');
    Route::get('/customizer', fn() => view('customizer'))->name('customizer');
    Route::get('/no-beta-for-you-yet', fn() => view('no-beta'))->name('no-beta');
    Route::get('/profiles', fn() => view('profiles'))->name('profiles');
    Route::get('/settings', fn() => view('settings'))->name('settings');
});

Route::get('/logout', function (TokenManager $tokenManager) {
    $tokenManager->clearToken();

    return redirect()->route('login');
})->name('logout');
