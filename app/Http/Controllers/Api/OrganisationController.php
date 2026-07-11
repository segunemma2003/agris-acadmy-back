<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organisation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Annotations as OA;

class OrganisationController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/organisations/register",
     *     tags={"Organisations"},
     *     summary="Register a new organisation account (pending admin approval before it can post slots)",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"contact_name","email","password","password_confirmation","name"},
     *             @OA\Property(property="contact_name", type="string", description="Name of the person registering on behalf of the organisation"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password", minLength=8),
     *             @OA\Property(property="password_confirmation", type="string", format="password"),
     *             @OA\Property(property="name", type="string", description="Organisation name"),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="sector", type="string", nullable=true),
     *             @OA\Property(property="state", type="string", nullable=true),
     *             @OA\Property(property="lga", type="string", nullable=true),
     *             @OA\Property(property="website", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Organisation registered; account is pending admin approval"),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function register(Request $request)
    {
        $request->validate([
            'contact_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sector' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'lga' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name' => $request->contact_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'organisation',
            'is_active' => true,
        ]);

        $organisation = Organisation::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'description' => $request->description,
            'sector' => $request->sector,
            'state' => $request->state,
            'lga' => $request->lga,
            'website' => $request->website,
            'is_approved' => false,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Organisation registered. An admin will review and approve your account before you can post slots.',
            'data' => [
                'user' => $user,
                'organisation' => $organisation,
                'token' => $token,
            ],
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
     *             @OA\Property(property="website", type="string", nullable=true)
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
            'description' => 'nullable|string',
            'sector' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'lga' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
        ]);

        $organisation->update($request->only(['name', 'description', 'sector', 'state', 'lga', 'website']));

        return response()->json(['success' => true, 'data' => $organisation, 'message' => 'Organisation profile updated']);
    }
}
