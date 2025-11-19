<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Password reset route (required by Laravel's password reset system)
Route::get('/reset-password/{token}', function () {
    return response()->json(['message' => 'Use the API endpoint /api/reset-password with token in request body']);
})->name('password.reset');
