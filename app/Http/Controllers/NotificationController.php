<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function get_unreadNotifications()
    {
        return Auth::user()->unreadNotifications;
    }

    public function get_allNotifications()
    {
        return Auth::user()->notifications;
    }

    public function mark_as_read(Request $request)
    {
        $request->validate([
            'notification_id' => 'required'
        ]);

        Auth::user()->unreadNotifications
            ->where('id', $request->notification_id)
            ->markAsRead();

        return response()->json([
            'message' => 'Notifications marked as read',
        ], 200);
    }

    public function clear_notification()
    {
        $user = Auth::user();

        $user->notifications()->delete();

        return response()->json([
            'message' => 'Notifications cleared successfuly!',
        ], 200);
    }
}