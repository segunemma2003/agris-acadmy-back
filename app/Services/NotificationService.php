<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Support\NotificationPreferences;

class NotificationService
{
    /**
     * Create a notification for a user (respects in-app preference for the type).
     */
    public static function create(
        User $user,
        string $type,
        string $title,
        string $message,
        ?string $actionType = null,
        ?int $actionId = null,
        ?array $data = null
    ): ?Notification {
        if (!NotificationPreferences::allowsInApp($user->notification_preferences, $type)) {
            return null;
        }

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
        $users,
        string $type,
        string $title,
        string $message,
        ?string $actionType = null,
        ?int $actionId = null,
        ?array $data = null
    ): void {
        $notifications = [];
        $now = now();

        foreach ($users as $user) {
            if (!$user instanceof User) {
                $userId = is_array($user) ? ($user['id'] ?? null) : $user;
                if (!$userId) {
                    continue;
                }
                $user = User::find($userId);
                if (!$user) {
                    continue;
                }
            }

            if (!NotificationPreferences::allowsInApp($user->notification_preferences, $type)) {
                continue;
            }

            $notifications[] = [
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'action_type' => $actionType,
                'action_id' => $actionId,
                'data' => $data ? json_encode($data) : null,
                'is_read' => false,
                'created_at' => $now,
                'updated_at' => $now,
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
