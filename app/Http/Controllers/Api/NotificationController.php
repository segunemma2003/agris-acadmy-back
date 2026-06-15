<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Annotations as OA;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/notifications",
     *     tags={"Notifications"},
     *     summary="Get paginated notifications for the authenticated user",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="unread_only", in="query", required=false, @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="type", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=20)),
     *     @OA\Response(response=200, description="Paginated notifications")
     * )
     *
     * Get user's notifications
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = $user->notifications()->orderBy('created_at', 'desc');

        // Filter by read status
        if ($request->has('unread_only') && $request->unread_only) {
            $query->where('is_read', false);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $perPage = $request->get('per_page', 20);
        $notifications = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'from' => $notifications->firstItem(),
                'to' => $notifications->lastItem(),
            ],
            'message' => 'Notifications retrieved successfully'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/notifications/unread-count",
     *     tags={"Notifications"},
     *     summary="Get the number of unread notifications",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=200, description="Unread count", @OA\JsonContent(@OA\Property(property="success", type="boolean"), @OA\Property(property="data", type="object", @OA\Property(property="unread_count", type="integer"))))
     * )
     *
     * Get unread notifications count
     */
    public function unreadCount(Request $request)
    {
        $user = $request->user();
        
        $count = $user->notifications()->where('is_read', false)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $count
            ],
            'message' => 'Unread count retrieved successfully'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/notifications/{notification}",
     *     tags={"Notifications"},
     *     summary="Get a notification (auto-marks as read)",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="notification", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Notification"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     *
     * @OA\Put(
     *     path="/api/notifications/{notification}/read",
     *     tags={"Notifications"},
     *     summary="Mark a notification as read",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="notification", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Notification marked as read"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     *
     * @OA\Put(
     *     path="/api/notifications/read-all",
     *     tags={"Notifications"},
     *     summary="Mark ALL notifications as read",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=200, description="All notifications marked as read")
     * )
     *
     * @OA\Delete(
     *     path="/api/notifications/{notification}",
     *     tags={"Notifications"},
     *     summary="Delete a notification",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="notification", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Notification deleted"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     *
     * @OA\Delete(
     *     path="/api/notifications/read/all",
     *     tags={"Notifications"},
     *     summary="Delete all read notifications",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=200, description="Read notifications deleted")
     * )
     *
     * Get a specific notification
     */
    public function show(Request $request, Notification $notification)
    {
        $user = $request->user();

        if ($notification->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Mark as read when viewing
        if (!$notification->is_read) {
            $notification->markAsRead();
        }

        return response()->json([
            'success' => true,
            'data' => $notification,
            'message' => 'Notification retrieved successfully'
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, Notification $notification)
    {
        $user = $request->user();

        if ($notification->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'data' => $notification,
            'message' => 'Notification marked as read'
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();

        $updated = $user->notifications()
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'updated_count' => $updated
            ],
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * Delete a notification
     */
    public function destroy(Request $request, Notification $notification)
    {
        $user = $request->user();

        if ($notification->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully'
        ]);
    }

    /**
     * Delete all read notifications
     */
    public function deleteAllRead(Request $request)
    {
        $user = $request->user();

        $deleted = $user->notifications()
            ->where('is_read', true)
            ->delete();

        return response()->json([
            'success' => true,
            'data' => [
                'deleted_count' => $deleted
            ],
            'message' => 'All read notifications deleted successfully'
        ]);
    }
}
