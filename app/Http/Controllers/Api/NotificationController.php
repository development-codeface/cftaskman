<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notifications;

class NotificationController extends Controller
{
    // ----------------------------------------------------
    // 16. GET NOTIFICATIONS OF A USER
    // ----------------------------------------------------
    public function getUserNotifications($user_id)
    {
        $notifications = Notifications::where('user_id', $user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'notifications' => $notifications
        ]);
    }

    // ----------------------------------------------------
    // 17. MARK NOTIFICATION AS READ
    // ----------------------------------------------------
    public function markAsRead(Request $request)
    {
        $validated = $request->validate([
            'notification_id' => 'required|exists:notifications,id'
        ]);

        Notifications::where('id', $validated['notification_id'])
            ->update(['is_read' => 1]);

        return response()->json([
            'status' => true,
            'message' => 'Notification marked as read'
        ]);
    }
}
