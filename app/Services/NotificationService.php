<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * Create a notification for a user
     */
    public static function create(
        User $user,
        string $type,
        string $title,
        string $message,
        ?string $actionType = null,
        ?int $actionId = null,
        ?array $data = null
    ): Notification {
        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'action_type' => $actionType,
            'action_id' => $actionId,
            'data' => $data,
        ]);
    }

    /**
     * Create notifications for multiple users
     */
    public static function createForUsers(
        $users, // Can be array of User objects or Collection
        string $type,
        string $title,
        string $message,
        ?string $actionType = null,
        ?int $actionId = null,
        ?array $data = null
    ): void {
        $notifications = [];
        foreach ($users as $user) {
            // Handle both User objects and arrays
            $userId = is_object($user) ? $user->id : (is_array($user) ? $user['id'] : $user);
            
            if (!$userId) {
                continue;
            }
            
            $notifications[] = [
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'action_type' => $actionType,
                'action_id' => $actionId,
                'data' => $data,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        if (!empty($notifications)) {
            Notification::insert($notifications);
        }
    }

    /**
     * Create notification for all users with a specific role
     */
    public static function createForRole(
        string $role,
        string $type,
        string $title,
        string $message,
        ?string $actionType = null,
        ?int $actionId = null,
        ?array $data = null
    ): void {
        $users = User::where('role', $role)
            ->where('is_active', true)
            ->get();
        
        self::createForUsers($users, $type, $title, $message, $actionType, $actionId, $data);
    }

    /**
     * Create notification for all students enrolled in a course
     */
    public static function createForCourseEnrollments(
        int $courseId,
        string $type,
        string $title,
        string $message,
        ?string $actionType = null,
        ?int $actionId = null,
        ?array $data = null
    ): void {
        $enrollments = \App\Models\Enrollment::where('course_id', $courseId)
            ->where('status', 'active')
            ->with('user')
            ->get();
        
        $users = $enrollments->pluck('user')->filter();
        self::createForUsers($users, $type, $title, $message, $actionType, $actionId, $data);
    }
}
