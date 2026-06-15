<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Annotations as OA;
use App\Mail\WelcomeStudentMail;
use App\Jobs\ProcessStudentRegistrationWithCSV;
use App\Models\User;
use App\Models\Enrollment;
use App\Models\Certificate;
use App\Models\StudentProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/register",
     *     tags={"Auth"},
     *     summary="Register a new student account",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", minLength=8, example="secret123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="secret123"),
     *             @OA\Property(property="phone", type="string", nullable=true, example="+234801234567"),
     *             @OA\Property(property="location", type="string", nullable=true, example="Lagos")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Registration successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Student registered successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User"),
     *                 @OA\Property(property="token", type="string", example="1|abc123..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'student',
            'phone' => $request->phone,
            'location' => $request->location,
            'is_active' => true,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        // Process registration with CSV check and send emails via queue (non-blocking)
        // This will check CSV, update user info if found, and send appropriate emails
        // If processing fails, registration still succeeds
        try {
            ProcessStudentRegistrationWithCSV::dispatch($user);
        } catch (\Exception $e) {
            // Log error but don't fail registration
            \Log::error('Failed to dispatch CSV processing job for ' . $user->email . ': ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Student registered successfully',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'location' => $user->location,
                    'role' => $user->role,
                    'avatar' => $user->avatar,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Auth"},
     *     summary="Login and obtain Bearer token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="secret123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User"),
     *                 @OA\Property(property="token", type="string", example="1|abc123..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Invalid credentials", @OA\JsonContent(ref="#/components/schemas/ApiError")),
     *     @OA\Response(response=403, description="Account inactive", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is inactive. Please contact support.',
            ], 403);
        }

        // Optional: Only allow students to login through this endpoint
        // Uncomment if you want to restrict this endpoint to students only
        // if ($user->role !== 'student') {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'This login endpoint is for students only.',
        //     ], 403);
        // }

        $user->update(['last_login_at' => now()]);
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'location' => $user->location,
                    'role' => $user->role,
                    'avatar' => $user->avatar,
                    'bio' => $user->bio,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     tags={"Auth"},
     *     summary="Revoke the current Bearer token",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=200, description="Logged out", @OA\JsonContent(@OA\Property(property="message", type="string", example="Logged out successfully")))
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * @OA\Get(
     *     path="/api/user",
     *     tags={"Auth"},
     *     summary="Get authenticated user profile with stats",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User profile",
     *         @OA\JsonContent(
     *             allOf={@OA\Schema(ref="#/components/schemas/User")},
     *             @OA\Property(property="stats", type="object",
     *                 @OA\Property(property="total_courses", type="integer"),
     *                 @OA\Property(property="ongoing_courses", type="integer"),
     *                 @OA\Property(property="completed_courses", type="integer"),
     *                 @OA\Property(property="total_hours_spent", type="number"),
     *                 @OA\Property(property="certificates_acquired", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function user(Request $request)
    {
        $user = $request->user();
        
        $cacheKey = "user_{$user->id}_profile_stats";
        
        $stats = Cache::remember($cacheKey, 300, function () use ($user) {
            $enrollments = $user->enrollments()->get();
            
            // Calculate total hours spent
            $totalHours = StudentProgress::where('user_id', $user->id)
                ->sum('watch_time_seconds') / 3600; // Convert seconds to hours
            
            // Get certificates count
            $certificatesCount = $user->certificates()->count();
            
            // Get course stats
            $ongoingCount = $enrollments->where('status', 'active')->count();
            $completedCount = $enrollments->where('status', 'completed')->count();
            
            return [
                'total_courses' => $enrollments->count(),
                'ongoing_courses' => $ongoingCount,
                'completed_courses' => $completedCount,
                'total_hours_spent' => round($totalHours, 2),
                'certificates_acquired' => $certificatesCount,
            ];
        });
        
        $userData = $user->toArray();
        $userData['stats'] = $stats;
        
        return response()->json($userData);
    }

    /**
     * @OA\Put(
     *     path="/api/user/profile",
     *     tags={"Auth"},
     *     summary="Update authenticated user profile",
     *     security={{"sanctumAuth":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="phone", type="string", nullable=true),
     *             @OA\Property(property="location", type="string", nullable=true),
     *             @OA\Property(property="bio", type="string", nullable=true),
     *             @OA\Property(property="avatar", type="string", nullable=true),
     *             @OA\Property(property="password", type="string", minLength=8, nullable=true),
     *             @OA\Property(property="password_confirmation", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Profile updated", @OA\JsonContent(@OA\Property(property="success", type="boolean"), @OA\Property(property="data", type="object", @OA\Property(property="user", ref="#/components/schemas/User"))))
     * )
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'avatar' => 'nullable|string|max:500',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $updateData = $request->only(['name', 'email', 'phone', 'location', 'bio', 'avatar']);

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'location' => $user->location,
                    'bio' => $user->bio,
                    'avatar' => $user->avatar,
                    'role' => $user->role,
                ],
            ],
        ]);
    }

    /**
     * Generate a 6-character random code
     */
    private function generateResetCode(): string
    {
        // Generate 6-character alphanumeric code (uppercase letters and numbers)
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $code;
    }

    /**
     * @OA\Put(
     *     path="/api/user/password",
     *     tags={"Auth"},
     *     summary="Change password for authenticated user",
     *     security={{"sanctumAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"current_password","password","password_confirmation"},
     *             @OA\Property(property="current_password", type="string"),
     *             @OA\Property(property="password", type="string", minLength=8),
     *             @OA\Property(property="password_confirmation", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Password changed", @OA\JsonContent(ref="#/components/schemas/ApiSuccess")),
     *     @OA\Response(response=400, description="Wrong current password or same password", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     *
     * @OA\Delete(
     *     path="/api/user/account",
     *     tags={"Auth"},
     *     summary="Delete authenticated user account",
     *     security={{"sanctumAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(required={"password"}, @OA\Property(property="password", type="string"))
     *     ),
     *     @OA\Response(response=200, description="Account deleted", @OA\JsonContent(ref="#/components/schemas/ApiSuccess")),
     *     @OA\Response(response=400, description="Incorrect password", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     *
     * @OA\Get(
     *     path="/api/user/certificates",
     *     tags={"Auth"},
     *     summary="Get all certificates earned by the authenticated user",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=200, description="List of certificates", @OA\JsonContent(@OA\Property(property="success", type="boolean"), @OA\Property(property="data", type="array", @OA\Items(type="object"))))
     * )
     *
     * @OA\Post(
     *     path="/api/user/profile/avatar",
     *     tags={"Auth"},
     *     summary="Upload a profile picture (multipart/form-data)",
     *     security={{"sanctumAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(required={"avatar"}, @OA\Property(property="avatar", type="string", format="binary", description="JPEG/PNG/GIF, max 2 MB"))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Avatar uploaded", @OA\JsonContent(ref="#/components/schemas/ApiSuccess"))
     * )
     *
     * @OA\Delete(
     *     path="/api/user/profile/avatar",
     *     tags={"Auth"},
     *     summary="Delete the current profile picture",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=200, description="Avatar deleted", @OA\JsonContent(ref="#/components/schemas/ApiSuccess"))
     * )
     *
     * @OA\Post(
     *     path="/api/forgot-password",
     *     tags={"Auth"},
     *     summary="Send a 6-character password reset code to an email address",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(required={"email"}, @OA\Property(property="email", type="string", format="email"))
     *     ),
     *     @OA\Response(response=200, description="Reset code sent", @OA\JsonContent(ref="#/components/schemas/ApiSuccess")),
     *     @OA\Response(response=429, description="Too many requests – wait 60 s", @OA\JsonContent(ref="#/components/schemas/ApiError")),
     *     @OA\Response(response=404, description="Email not found", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     *
     * @OA\Post(
     *     path="/api/reset-password",
     *     tags={"Auth"},
     *     summary="Reset password using the 6-character code",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token","email","password","password_confirmation"},
     *             @OA\Property(property="token", type="string", minLength=6, maxLength=6, example="AB1C2D", description="6-character reset code from email"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", minLength=8),
     *             @OA\Property(property="password_confirmation", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Password reset successful", @OA\JsonContent(ref="#/components/schemas/ApiSuccess")),
     *     @OA\Response(response=400, description="Invalid or expired code", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    /**
     * Send password reset code
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        try {
            $email = $request->email;
            $user = User::where('email', $email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No user found with this email address.',
                ], 404);
            }

            // Check throttling (60 seconds)
            $existingToken = DB::table('password_reset_tokens')
                ->where('email', $email)
                ->first();

            if ($existingToken) {
                $createdAt = \Carbon\Carbon::parse($existingToken->created_at);
                if ($createdAt->diffInSeconds(now()) < 60) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please wait before retrying. You can request a password reset once per minute.',
                    ], 429);
                }
            }

            // Generate 6-character code
            $code = $this->generateResetCode();

            // Hash the code for storage (using bcrypt for security)
            $hashedCode = Hash::make($code);

            // Store or update the reset token
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'token' => $hashedCode,
                    'created_at' => now(),
                ]
            );

            // Send notification with the plain code
            $user->notify(new \App\Notifications\ResetPasswordNotification($code));

            return response()->json([
                'success' => true,
                'message' => 'Password reset code has been sent to your email address.',
            ]);
        } catch (\Exception $e) {
            \Log::error('Password reset code sending failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending the password reset code. Please try again later.',
            ], 500);
        }
    }

    /**
     * Reset password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string|size:6',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $email = $request->email;
            $code = strtoupper($request->token); // Convert to uppercase for consistency
            $password = $request->password;

            // Find the user
            $user = User::where('email', $email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No user found with this email address.',
                ], 404);
            }

            // Get the reset token from database
            $resetToken = DB::table('password_reset_tokens')
                ->where('email', $email)
                ->first();

            if (!$resetToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired reset code. Please request a new password reset code.',
                ], 400);
            }

            // Check if token is expired (60 minutes)
            $createdAt = \Carbon\Carbon::parse($resetToken->created_at);
            if ($createdAt->diffInMinutes(now()) > 60) {
                // Delete expired token
                DB::table('password_reset_tokens')->where('email', $email)->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Reset code has expired. Please request a new password reset code.',
                ], 400);
            }

            // Verify the code
            if (!Hash::check($code, $resetToken->token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid reset code. Please check and try again.',
                ], 400);
            }

            // Reset the password
            $user->password = Hash::make($password);
            $user->save();

            // Delete the used token
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Password has been reset successfully. You can now login with your new password.',
            ]);
        } catch (\Exception $e) {
            \Log::error('Password reset failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while resetting your password. Please try again later.',
            ], 500);
        }
    }

    /**
     * Change password (for authenticated users)
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 400);
        }

        // Check if new password is same as current password
        if (Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'New password must be different from your current password',
            ], 400);
        }

        try {
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully',
            ]);
        } catch (\Exception $e) {
            \Log::error('Password change failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while changing your password. Please try again later.',
            ], 500);
        }
    }

    /**
     * Delete account
     */
    public function deleteAccount(Request $request)
    {
        $request->validate([
            'password' => 'required',
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password is incorrect',
            ], 400);
        }

        // Delete user (cascade will handle related records)
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Account deleted successfully',
        ]);
    }

    /**
     * Get user certificates
     */
    public function certificates(Request $request)
    {
        $user = $request->user();
        
        $cacheKey = "user_{$user->id}_certificates";
        
        $certificates = Cache::remember($cacheKey, 600, function () use ($user) {
            return $user->certificates()
                ->with('course:id,title,image,slug')
                ->orderBy('created_at', 'desc') // Use created_at instead of issued_date
                ->get();
        });
        
        return response()->json([
            'success' => true,
            'data' => $certificates,
            'message' => 'Certificates retrieved successfully'
        ]);
    }

    /**
     * Upload profile picture
     */
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
        ]);

        $user = $request->user();

        // Delete old avatar if exists
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Store new avatar
        $path = $request->file('avatar')->store('avatars', 'public');
        
        // Update user avatar
        $user->update([
            'avatar' => $path,
        ]);

        // Clear cache
        Cache::forget("user_{$user->id}_profile_stats");

        return response()->json([
            'success' => true,
            'message' => 'Profile picture uploaded successfully',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => asset('storage/' . $path),
                ],
            ],
        ]);
    }

    /**
     * Delete profile picture
     */
    public function deleteAvatar(Request $request)
    {
        $user = $request->user();

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->update([
            'avatar' => null,
        ]);

        // Clear cache
        Cache::forget("user_{$user->id}_profile_stats");

        return response()->json([
            'success' => true,
            'message' => 'Profile picture deleted successfully',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => null,
                ],
            ],
        ]);
    }
}



