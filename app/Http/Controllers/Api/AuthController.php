<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'student',
            'phone' => $request->phone,
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
                    'role' => $user->role,
                    'avatar' => $user->avatar,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 201);
    }

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
                    'role' => $user->role,
                    'avatar' => $user->avatar,
                    'bio' => $user->bio,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

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

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'avatar' => 'nullable|string|max:500',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $updateData = $request->only(['name', 'email', 'phone', 'bio', 'avatar']);

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
                    'bio' => $user->bio,
                    'avatar' => $user->avatar,
                    'role' => $user->role,
                ],
            ],
        ]);
    }

    /**
     * Send password reset link
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'success' => true,
                    'message' => 'Password reset link has been sent to your email address.',
                ]);
            }

            // Handle throttling
            if ($status === Password::RESET_THROTTLED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please wait before retrying. You can request a password reset once per minute.',
                ], 429);
            }

            return response()->json([
                'success' => false,
                'message' => 'Unable to send password reset link. Please try again later.',
            ], 400);
        } catch (\Exception $e) {
            \Log::error('Password reset link sending failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending the password reset link. Please try again later.',
            ], 500);
        }
    }

    /**
     * Reset password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $user->password = Hash::make($password);
                    $user->save();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return response()->json([
                    'success' => true,
                    'message' => 'Password has been reset successfully. You can now login with your new password.',
                ]);
            }

            // Handle specific error cases
            if ($status === Password::INVALID_TOKEN) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid reset token. Please request a new password reset link.',
                ], 400);
            }

            if ($status === Password::INVALID_USER) {
                return response()->json([
                    'success' => false,
                    'message' => 'No user found with this email address.',
                ], 404);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired reset token. Please request a new password reset link.',
            ], 400);
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
                ->orderBy('issued_at', 'desc')
                ->get();
        });
        
        return response()->json([
            'success' => true,
            'data' => $certificates,
            'message' => 'Certificates retrieved successfully'
        ]);
    }
}



