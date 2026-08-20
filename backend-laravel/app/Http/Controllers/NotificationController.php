<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function unreadCount(Request $request)
    {
        try {
            $userId = Auth::user()->id;

            $count = Notification::where('user_id', $userId)
                ->where('is_read', false)
                ->count();

            return response()->json(['count' => $count]);
        } catch (\Exception $error) {
            \Log::error('Unread Count Error: ' . $error->getMessage());
            return response()->json(['detail' => 'خطأ في جلب عدد الإشعارات'], 500);
        }
    }


    public function markAllAsRead(Request $request)
    {
        try {
            $userId = Auth::user()->id;

            Notification::where('user_id', $userId)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            return response()->json(['message' => 'تم تحديد جميع الإشعارات كمقروءة']);
        } catch (\Exception $error) {
            \Log::error('Mark All Read Error: ' . $error->getMessage());
            return response()->json(['detail' => 'خطأ في تحديث الإشعارات'], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $userId = Auth::user()->id;

            $notifications = Notification::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            $response = $notifications->map(function ($n) {
                return [
                    'id' => $n->id,
                    'title' => $n->title,
                    'message' => $n->message,
                    'type' => $n->type,
                    'is_read' => $n->is_read,
                    'created_at' => $n->created_at ? $n->created_at->toIso8601String() : null,
                ];
            });

            return response()->json($response);
        } catch (\Exception $error) {
            \Log::error('Get Notifications Error: ' . $error->getMessage());
            return response()->json(['detail' => 'خطأ في جلب الإشعارات'], 500);
        }
    }

    public function read(Request $request, $id)
    {
        try {
            $userId = Auth::user()->id;

            $notification = Notification::where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (!$notification) {
                return response()->json(['detail' => 'الإشعار غير موجود'], 404);
            }

            $notification->is_read = true;
            $notification->save();

            return response()->json(['message' => 'تم وضع علامة مقروء على الإشعار']);
        } catch (\Exception $error) {
            \Log::error('Mark Read Error: ' . $error->getMessage());
            return response()->json(['detail' => 'خطأ في تحديث الإشعار'], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $userId = Auth::user()->id;

            $result = Notification::where('id', $id)
                ->where('user_id', $userId)
                ->delete();

            if ($result === 0) {
                return response()->json(['detail' => 'الإشعار غير موجود'], 404);
            }

            return response()->json(['message' => 'تم حذف الإشعار']);
        } catch (\Exception $error) {
            \Log::error('Delete Notification Error: ' . $error->getMessage());
            return response()->json(['detail' => 'خطأ في حذف الإشعار'], 500);
        }
    }
}
