<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LearnerCompletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Internal Learn → Fund endpoints consumed by Agrisiti Finance (service key auth).
 */
class InternalLearnerController extends Controller
{
    public function __construct(
        private LearnerCompletionService $learners,
    ) {}

    /**
     * Slim completion check for automatic graduate verification.
     */
    public function completionStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:30',
        ]);

        if (empty($validated['email']) && empty($validated['phone'])) {
            return response()->json([
                'success' => false,
                'message' => 'Provide email or phone.',
            ], 422);
        }

        $data = $this->learners->completionStatus(
            $validated['email'] ?? null,
            $validated['phone'] ?? null,
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Full Academy profile (enrollments + certificates) for Finance admin review.
     */
    public function lookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:30',
        ]);

        if (empty($validated['email']) && empty($validated['phone'])) {
            return response()->json([
                'success' => false,
                'message' => 'Provide email or phone.',
            ], 422);
        }

        $data = $this->learners->lookup(
            $validated['email'] ?? null,
            $validated['phone'] ?? null,
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Manual certificate ID path when email/phone auto-match fails.
     */
    public function lookupCertificate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'certificate_number' => 'required|string|max:64',
        ]);

        $data = $this->learners->lookupByCertificateNumber($validated['certificate_number']);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
