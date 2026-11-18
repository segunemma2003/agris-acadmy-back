<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Signed paper consent form (MEL tool, NDPA 2023): a photo or PDF kept on
 * private storage. Uploading marks consent as "physical".
 */
class ConsentFormController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5 MB, same as Finance
        ]);

        $user = $request->user();
        if ($user->consent_form_path) {
            Storage::disk('local')->delete($user->consent_form_path);
        }
        $path = $request->file('file')->store("consent-forms/{$user->id}", 'local');
        $user->update([
            'consent_form_path' => $path,
            'consent_form_uploaded_at' => now(),
            'consent_method' => 'physical',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Signed consent form uploaded',
            'data' => [
                'consent_method' => 'physical',
                'has_consent_form' => true,
                'consent_form_uploaded_at' => $user->consent_form_uploaded_at->toIso8601String(),
            ],
        ]);
    }

    /** The learner's own form (admins open it from the admin panel). */
    public function show(Request $request)
    {
        $path = $request->user()->consent_form_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404, 'No signed consent form uploaded');

        return Storage::disk('local')->download($path, 'signed-consent-form.' . pathinfo($path, PATHINFO_EXTENSION));
    }
}
