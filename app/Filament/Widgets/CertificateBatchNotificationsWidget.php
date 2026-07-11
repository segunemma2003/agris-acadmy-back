<?php

namespace App\Filament\Widgets;

use App\Models\Notification as AppNotification;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

/**
 * Surfaces "certificate batch complete" notices to the admin who triggered a
 * bulk certificate generation, so they know when a queued batch has finished.
 */
class CertificateBatchNotificationsWidget extends Widget
{
    protected static string $view = 'filament.widgets.certificate-batch-notifications';

    protected int|string|array $columnSpan = 'full';

    // High sort value so this always renders below the stats widgets (which
    // top out around 3) at the bottom of the dashboard, regardless of what
    // else gets added there later.
    protected static ?int $sort = 100;

    /**
     * Only show the widget when the current admin actually has batch notices.
     */
    public static function canView(): bool
    {
        $user = Filament::auth()->user();

        return $user
            && AppNotification::where('user_id', $user->id)
                ->where('type', 'certificate_batch')
                ->exists();
    }

    public function getNotifications()
    {
        $user = Filament::auth()->user();

        if (!$user) {
            return collect();
        }

        $notifications = AppNotification::where('user_id', $user->id)
            ->where('type', 'certificate_batch')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Mark the unread ones as read now that they've been surfaced, so they
        // stop showing as "new" on the next dashboard visit.
        AppNotification::where('user_id', $user->id)
            ->where('type', 'certificate_batch')
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return $notifications;
    }
}
