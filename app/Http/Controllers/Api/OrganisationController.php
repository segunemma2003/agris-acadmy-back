<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class OrganisationController extends Controller
{
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
