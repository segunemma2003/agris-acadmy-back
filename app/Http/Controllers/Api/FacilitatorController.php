<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class FacilitatorController extends Controller
{
    /**
     * Get facilitators by location
     * GET /facilitators?location={location}
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $userLocation = $user->location;

        // If user has no location, return empty
        if (!$userLocation) {
            return response()->json([
                'message' => 'User location not set',
                'data' => []
            ], 200);
        }

        // Get facilitators with matching location
        $facilitators = User::where('role', 'facilitator')
            ->where('is_active', true)
            ->where('location', $userLocation)
            ->select('id', 'name', 'email', 'avatar', 'bio', 'location', 'phone')
            ->get()
            ->map(function ($facilitator) {
                return [
                    'id' => $facilitator->id,
                    'name' => $facilitator->name,
                    'email' => $facilitator->email,
                    'avatar' => $facilitator->avatar ? asset('storage/' . $facilitator->avatar) : null,
                    'bio' => $facilitator->bio,
                    'location' => $facilitator->location,
                    'phone' => $facilitator->phone,
                ];
            });

        return response()->json([
            'message' => 'Facilitators retrieved successfully',
            'data' => $facilitators,
        ]);
    }

    /**
     * Get all instructors/tutors (for admin or general listing)
     * GET /instructors
     */
    public function instructors(Request $request)
    {
        $instructors = User::whereIn('role', ['tutor', 'facilitator'])
            ->where('is_active', true)
            ->select('id', 'name', 'email', 'avatar', 'bio', 'location', 'phone', 'role')
            ->get()
            ->map(function ($instructor) {
                return [
                    'id' => $instructor->id,
                    'name' => $instructor->name,
                    'email' => $instructor->email,
                    'avatar' => $instructor->avatar ? asset('storage/' . $instructor->avatar) : null,
                    'bio' => $instructor->bio,
                    'location' => $instructor->location,
                    'phone' => $instructor->phone,
                    'role' => $instructor->role,
                ];
            });

        return response()->json([
            'message' => 'Instructors retrieved successfully',
            'data' => $instructors,
        ]);
    }
}
