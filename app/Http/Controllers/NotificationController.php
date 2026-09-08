<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $notifications = $user->notifications()->latest()->get();

        return response()->json(['notifications' => $notifications]);
    }

    public function unread(Request $request)
    {
        $user = $request->user();
        $notifications = $user->unreadNotifications()->latest()->get();

        return response()->json(['unread_notifications' => $notifications]);
    }

    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        return response()->json(['message' => 'تم تعليم كل الإشعارات كمقروءة']);
    }

    public function clear(Request $request)
    {
        $user = $request->user();
        $user->notifications()->delete();

        return response()->json(['message' => 'تم حذف كل الإشعارات']);
    }

    public function deleteById(Request $request, $id)
    {
        $user = $request->user();
        $notification = $user->notifications()->find($id);

        if (!$notification) {
            return response()->json(['message' => 'الإشعار غير موجود'], 404);
        }

        $notification->delete();
        return response()->json(['message' => 'تم حذف الإشعار']);
    }

    public function notifyUser(Request $request)
    {
        $request->validate([
            'type'   => 'required|in:beneficiary,donor,volunteer',
            'user_id' => 'required|integer',
            'title'   => 'required|string',
            'body'    => 'required|string',
        ]);

        $models = [
            'beneficiary' => \App\Models\Beneficiary::class,
            'donor'       => \App\Models\Donor::class,
            'volunteer'   => \App\Models\Volunteer::class,
        ];

        $modelClass = $models[$request->type];
        $user = $modelClass::find($request->user_id);

        if (!$user) {
            return response()->json(['error' => 'User selected is invalid'], 404);
        }

        $user->notify(new \App\Notifications\FcmNotificationMessage(
            $request->title,
            $request->body
        ));

        return response()->json(['message' => 'Notification sent successfully']);
    }
}