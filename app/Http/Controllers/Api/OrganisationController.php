<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organisation;
use App\Models\User;
use App\Services\LearnerInterventionAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use OpenApi\Annotations as OA;

class OrganisationController extends Controller
{
    private const PROFILE_FIELDS = [
        'description', 'sector', 'organisation_type', 'country', 'state', 'lga', 'city', 'website', 'phone', 'talent_needs',
    ];

    private static function profileRules(): array
    {
        return [
            'description' => 'nullable|string|max:5000',
            'sector' => 'nullable|string|max:255',
            'organisation_type' => 'nullable|in:' . implode(',', array_keys(Organisation::TYPES)),
            'country' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:255',
            'lga' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:120',
            'website' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'talent_needs' => 'nullable|string|max:5000',
        ];
    }

    /**
     * Approved organisations for the learner's "organisation you work with"
     * picker (public, names only).
     */
    public function index()
    {
        $orgs = Organisation::where('is_approved', true)->orderBy('name')
            ->get(['id', 'name', 'state', 'organisation_type']);

        return response()->json(['success' => true, 'data' => $orgs]);
    }

    /**
     * Self-registration for farms, agro-enterprises, recruitment agencies and
     * institutions (Nigeria or abroad) that need talent. The account works
     * straight away, but openings can only be posted — and are only shown to
     * learners — once an admin approves the organisation.
     */
    public function register(Request $request)
    {
        $request->validate([
            'contact_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'name' => 'required|string|max:255',
            ...self::profileRules(),
            'organisation_type' => 'required|in:' . implode(',', array_keys(Organisation::TYPES)),
            'country' => 'required|string|max:100',
            'talent_needs' => 'required|string|max:5000',
            // Nigerian organisations give their state; others their city
            'state' => 'required_if:country,Nigeria|nullable|string|max:255',
            'city' => 'exclude_if:country,Nigeria|required|string|max:120',
        ]);

        [$user, $organisation] = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->contact_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'role' => 'organisation',
                'is_active' => true,
            ]);
            $organisation = Organisation::create([
                'user_id' => $user->id,
                'name' => $request->name,
                ...$request->only(self::PROFILE_FIELDS),
                'is_approved' => false,
            ]);

            return [$user, $organisation];
        });

        try {
            $place = $organisation->country === 'Nigeria'
                ? trim(($organisation->lga ? $organisation->lga . ', ' : '') . $organisation->state)
                : trim(($organisation->city ? $organisation->city . ', ' : '') . $organisation->country);
            Mail::raw(
                "A new organisation registered and is waiting for approval.\n\n"
                . "Organisation: {$organisation->name}\n"
                . 'Type: ' . (Organisation::TYPES[$organisation->organisation_type] ?? '-') . "\n"
                . "Location: {$place}\n"
                . "Contact: {$user->name} <{$user->email}>" . ($organisation->phone ? ", {$organisation->phone}" : '') . "\n\n"
                . "Talent they need:\n{$organisation->talent_needs}\n\n"
                . 'Approve them under Organisations in the admin panel.',
                fn ($m) => $m->to(LearnerInterventionAlertService::ADMIN_EMAIL)->subject("New organisation to approve: {$organisation->name}")
            );
        } catch (\Throwable $e) {
            Log::warning('Organisation registration admin email failed', ['organisation_id' => $organisation->id, 'error' => $e->getMessage()]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Organisation registered. An admin will review and approve your account before you can post openings.',
            'data' => ['user' => $user, 'organisation' => $organisation, 'token' => $token],
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/organisations/me",
     *     tags={"Organisations"},
     *     summary="Get the authenticated organisation's own profile",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=200, description="Organisation profile"),
     *     @OA\Response(response=404, description="No organisation profile for this account")
     * )
     */
    public function me(Request $request)
    {
        $organisation = $request->user()->organisation;

        if (!$organisation) {
            return response()->json(['success' => false, 'message' => 'No organisation profile found for this account'], 404);
        }

        return response()->json(['success' => true, 'data' => $organisation]);
    }

    /**
     * @OA\Put(
     *     path="/api/organisations/me",
     *     tags={"Organisations"},
     *     summary="Update the authenticated organisation's own profile",
     *     security={{"sanctumAuth":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="sector", type="string", nullable=true),
     *             @OA\Property(property="state", type="string", nullable=true),
     *             @OA\Property(property="lga", type="string", nullable=true),
     *             @OA\Property(property="website", type="string", nullable=true),
     *             @OA\Property(property="organisation_type", type="string", nullable=true, enum={"farm","agro_enterprise","recruitment_agency","institution","ngo","government","other"}),
     *             @OA\Property(property="country", type="string", nullable=true),
     *             @OA\Property(property="city", type="string", nullable=true),
     *             @OA\Property(property="phone", type="string", nullable=true),
     *             @OA\Property(property="talent_needs", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Organisation profile updated"),
     *     @OA\Response(response=404, description="No organisation profile for this account")
     * )
     */
    public function update(Request $request)
    {
        $organisation = $request->user()->organisation;

        if (!$organisation) {
            return response()->json(['success' => false, 'message' => 'No organisation profile found for this account'], 404);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            ...self::profileRules(),
        ]);

        $organisation->update($request->only(['name', ...self::PROFILE_FIELDS]));

        return response()->json(['success' => true, 'data' => $organisation, 'message' => 'Organisation profile updated']);
    }
}
