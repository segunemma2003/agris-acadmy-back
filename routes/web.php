<?php

use App\Http\Controllers\Admin\CertificateTemplatePreviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Signed link used by the "Preview" button in the admin's certificate template list
Route::get('/admin-tools/certificate-templates/{certificateTemplate}/preview', CertificateTemplatePreviewController::class)
    ->middleware('signed')
    ->name('admin.certificate-templates.preview');

// Password reset route (required by Laravel's password reset system)
Route::get('/reset-password/{token}', function () {
    return response()->json(['message' => 'Use the API endpoint /api/reset-password with token in request body']);
})->name('password.reset');
