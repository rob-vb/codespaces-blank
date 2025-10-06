<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('test');
});

Route::get('/login', function () {
    return view('login');
});

Route::get('/customizer', function () {
    return view('customizer');
});

Route::get('/no-beta-for-you-yet', function () {
    return view('no-beta');
});