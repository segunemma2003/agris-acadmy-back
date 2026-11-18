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

// Signed, short-lived link from the admin user page: the learner's signed paper consent form
Route::get('/admin-tools/consent-forms/{user}', function (\App\Models\User $user) {
    $path = $user->consent_form_path;
    abort_unless($path && \Illuminate\Support\Facades\Storage::disk('local')->exists($path), 404);

    return \Illuminate\Support\Facades\Storage::disk('local')->response($path);
})->middleware('signed')->name('admin.consent-forms.show');

// Password reset route (required by Laravel's password reset system)
Route::get('/reset-password/{token}', function () {
    return response()->json(['message' => 'Use the API endpoint /api/reset-password with token in request body']);
})->name('password.reset');

// Admin → YouTube captions: connect Agrisiti's YouTube channel (Google OAuth)
Route::middleware(['web', 'auth'])->prefix('admin-tools/youtube')->group(function () {
    Route::get('/connect', [\App\Http\Controllers\Admin\YouTubeConnectionController::class, 'connect'])->name('admin.youtube.connect');
    Route::get('/callback', [\App\Http\Controllers\Admin\YouTubeConnectionController::class, 'callback'])->name('admin.youtube.callback');
});
