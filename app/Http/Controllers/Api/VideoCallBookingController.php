<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VideoCallBooking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class VideoCallBookingController extends Controller
{
    /**
     * Get available time slots for a tutor
     */
    public function availableSlots(Request $request, $tutorId)
    {
        $request->validate([
            'date' => 'required|date',
            'duration' => 'required|in:5,10,15',
        ]);

        $date = Carbon::parse($request->date);
        $duration = (int) $request->duration;
        $bufferMinutes = VideoCallBooking::BUFFER_MINUTES;

        // Get existing bookings for the tutor on this date
        $existingBookings = VideoCallBooking::where('tutor_id', $tutorId)
            ->whereDate('scheduled_at', $date)
            ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
            ->orderBy('scheduled_at')
            ->get();

        // Generate available slots (e.g., 9 AM to 6 PM)
        $startTime = $date->copy()->setTime(9, 0);
        $endTime = $date->copy()->setTime(18, 0);
        $availableSlots = [];

        $currentTime = $startTime->copy();

        while ($currentTime->copy()->addMinutes($duration)->lte($endTime)) {
            $slotStart = $currentTime->copy();
            $slotEnd = $slotStart->copy()->addMinutes($duration);
            $nextSlotStart = $slotEnd->copy()->addMinutes($bufferMinutes);

            // Check if this slot conflicts with existing bookings
            $hasConflict = false;
            foreach ($existingBookings as $booking) {
                $bookingStart = Carbon::parse($booking->scheduled_at);
                $bookingEnd = $bookingStart->copy()->addMinutes($booking->getTotalDurationMinutes() + $bufferMinutes);

                // Check if slots overlap (considering buffer)
                if ($slotStart->lt($bookingEnd) && $nextSlotStart->gt($bookingStart)) {
                    $hasConflict = true;
                    break;
                }
            }

            // Don't show past slots
            if (!$hasConflict && $slotStart->isFuture()) {
                $availableSlots[] = [
                    'start_time' => $slotStart->toIso8601String(),
                    'end_time' => $slotEnd->toIso8601String(),
                    'duration_minutes' => $duration,
                ];
            }

            // Move to next slot (current slot + duration + buffer)
            $currentTime->addMinutes($duration + $bufferMinutes);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date->toDateString(),
                'duration' => $duration,
                'available_slots' => $availableSlots,
            ],
            'message' => 'Available slots retrieved successfully'
        ]);
    }

    /**
     * Create a new booking
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tutor_id' => 'required|exists:users,id',
            'course_id' => 'nullable|exists:courses,id',
            'type' => 'nullable|in:video_call,video_request',
            'scheduled_at' => 'required|date|after:now',
            'duration_minutes' => 'required|in:5,10,15',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $tutor = User::findOrFail($request->tutor_id);

        // Verify tutor is actually a tutor
        if ($tutor->role !== 'tutor') {
            return response()->json([
                'success' => false,
                'message' => 'Selected user is not a tutor'
            ], 400);
        }

        $scheduledAt = Carbon::parse($request->scheduled_at);
        $duration = (int) $request->duration_minutes;
        $bufferMinutes = VideoCallBooking::BUFFER_MINUTES;

        // Check for conflicts with existing bookings
        // A conflict occurs if:
        // 1. New slot starts before existing booking ends (with buffer)
        // 2. New slot ends after existing booking starts (with buffer)
        $slotEnd = $scheduledAt->copy()->addMinutes($duration);
        $slotStartWithBuffer = $scheduledAt->copy()->subMinutes($bufferMinutes);
        $slotEndWithBuffer = $slotEnd->copy()->addMinutes($bufferMinutes);

        $conflictingBookings = VideoCallBooking::where('tutor_id', $tutor->id)
            ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
            ->where(function ($query) use ($scheduledAt, $slotEnd, $slotStartWithBuffer, $slotEndWithBuffer, $bufferMinutes) {
                $query->where(function ($q) use ($scheduledAt, $slotEnd, $slotStartWithBuffer, $slotEndWithBuffer, $bufferMinutes) {
                    // Check if new slot overlaps with existing booking
                    $q->where(function ($subQ) use ($scheduledAt, $slotEnd, $bufferMinutes) {
                        // Existing booking starts before new slot ends (with buffer)
                        $subQ->where('scheduled_at', '<', $slotEnd->copy()->addMinutes($bufferMinutes))
                            // And existing booking ends after new slot starts (with buffer)
                            ->whereRaw('DATE_ADD(scheduled_at, INTERVAL (duration_minutes + extension_minutes + ?) MINUTE) > ?', [
                                $bufferMinutes,
                                $scheduledAt->copy()->subMinutes($bufferMinutes)
                            ]);
                    });
                });
            })
            ->exists();

        if ($conflictingBookings) {
            return response()->json([
                'success' => false,
                'message' => 'This time slot is not available. Please choose another time.'
            ], 400);
        }

        // Create booking
        $booking = VideoCallBooking::create([
            'student_id' => $user->id,
            'tutor_id' => $tutor->id,
            'course_id' => $request->course_id,
            'type' => $request->type ?? 'video_call',
            'status' => 'pending',
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => $duration,
            'extension_minutes' => 0,
            'is_extended' => false,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking created successfully',
            'data' => $booking->load(['student:id,name,email,avatar', 'tutor:id,name,email,avatar', 'course:id,title'])
        ], 201);
    }

    /**
     * Extend booking duration
     */
    public function extend(Request $request, VideoCallBooking $booking)
    {
        $request->validate([
            'extension_minutes' => 'required|in:5,10',
        ]);

        $user = $request->user();

        // Verify user owns this booking
        if ($booking->student_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Check if already extended
        if ($booking->is_extended) {
            return response()->json([
                'success' => false,
                'message' => 'This booking has already been extended. You can only extend once.'
            ], 400);
        }

        // Check if booking can be extended
        if (!$booking->canExtend()) {
            return response()->json([
                'success' => false,
                'message' => 'This booking cannot be extended at this time.'
            ], 400);
        }

        $extensionMinutes = (int) $request->extension_minutes;
        $bufferMinutes = VideoCallBooking::BUFFER_MINUTES;
        $newEndTime = $booking->getEndTime()->copy()->addMinutes($extensionMinutes);

        // Check for conflicts with existing bookings after extension
        $newEndTimeWithBuffer = $newEndTime->copy()->addMinutes($bufferMinutes);
        $bookingStartWithBuffer = $booking->scheduled_at->copy()->subMinutes($bufferMinutes);

        $conflictingBookings = VideoCallBooking::where('tutor_id', $booking->tutor_id)
            ->where('id', '!=', $booking->id)
            ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
            ->where(function ($query) use ($booking, $newEndTime, $bookingStartWithBuffer, $newEndTimeWithBuffer, $bufferMinutes) {
                $query->where(function ($q) use ($booking, $newEndTime, $bookingStartWithBuffer, $newEndTimeWithBuffer, $bufferMinutes) {
                    // Check if extended booking overlaps with existing booking
                    $q->where('scheduled_at', '<', $newEndTimeWithBuffer)
                        ->whereRaw('DATE_ADD(scheduled_at, INTERVAL (duration_minutes + extension_minutes + ?) MINUTE) > ?', [
                            $bufferMinutes,
                            $bookingStartWithBuffer
                        ]);
                });
            })
            ->exists();

        if ($conflictingBookings) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot extend booking. It would conflict with another booking.'
            ], 400);
        }

        // Update booking
        $booking->update([
            'extension_minutes' => $extensionMinutes,
            'is_extended' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking extended successfully',
            'data' => $booking->fresh()->load(['student:id,name,email,avatar', 'tutor:id,name,email,avatar', 'course:id,title'])
        ]);
    }

    /**
     * Get user's bookings
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $role = $user->role;

        $query = VideoCallBooking::with(['student:id,name,email,avatar', 'tutor:id,name,email,avatar', 'course:id,title']);

        if ($role === 'student') {
            $query->where('student_id', $user->id);
        } elseif ($role === 'tutor') {
            $query->where('tutor_id', $user->id);
        } else {
            // Admin or other roles can see all
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date
        if ($request->has('date')) {
            $query->whereDate('scheduled_at', $request->date);
        }

        // Sort by scheduled time
        $bookings = $query->orderBy('scheduled_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $bookings->items(),
            'pagination' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
            'message' => 'Bookings retrieved successfully'
        ]);
    }

    /**
     * Get a specific booking
     */
    public function show(Request $request, VideoCallBooking $booking)
    {
        $user = $request->user();

        // Verify user has access
        if ($booking->student_id !== $user->id && $booking->tutor_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $booking->load(['student:id,name,email,avatar', 'tutor:id,name,email,avatar', 'course:id,title']),
            'message' => 'Booking retrieved successfully'
        ]);
    }

    /**
     * Update booking status
     */
    public function updateStatus(Request $request, VideoCallBooking $booking)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,in_progress,completed,cancelled',
        ]);

        $user = $request->user();
        $newStatus = $request->status;

        // Verify user has permission
        if ($booking->student_id !== $user->id && $booking->tutor_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Update status and timestamps
        $updateData = ['status' => $newStatus];

        if ($newStatus === 'in_progress' && !$booking->started_at) {
            $updateData['started_at'] = now();
        }

        if ($newStatus === 'completed' && !$booking->ended_at) {
            $updateData['ended_at'] = now();
        }

        $booking->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Booking status updated successfully',
            'data' => $booking->fresh()->load(['student:id,name,email,avatar', 'tutor:id,name,email,avatar', 'course:id,title'])
        ]);
    }

    /**
     * Cancel a booking
     */
    public function cancel(Request $request, VideoCallBooking $booking)
    {
        $user = $request->user();

        // Verify user has permission
        if ($booking->student_id !== $user->id && $booking->tutor_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Can only cancel if not already completed or cancelled
        if (in_array($booking->status, ['completed', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel a booking that is already ' . $booking->status
            ], 400);
        }

        $booking->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled successfully',
            'data' => $booking->fresh()->load(['student:id,name,email,avatar', 'tutor:id,name,email,avatar', 'course:id,title'])
        ]);
    }
}
