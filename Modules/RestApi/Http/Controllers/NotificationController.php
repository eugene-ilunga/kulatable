<?php

namespace Modules\RestApi\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\RestApi\Entities\DeviceToken;
use Modules\RestApi\Notifications\AppPushNotification;

class NotificationController extends Controller
{
    public function registerToken(Request $request)
    {
        try {
            $data = $request->validate([
                'token' => 'required|string',
                'platform' => 'nullable|string|max:20',
                'device_id' => 'nullable|string|max:191',
            ]);

            $user = Auth::user();

            if (! $user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }

            DeviceToken::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'token' => $data['token'],
                ],
                [
                    'restaurant_id' => $user->restaurant_id,
                    'branch_id' => $user->branch_id,
                    'platform' => $data['platform'] ?? null,
                    'device_id' => $data['device_id'] ?? null,
                ]
            );

            return response()->json(['success' => true, 'message' => 'Token registered successfully']);
        } catch (\Throwable $e) {
            Log::error('NotificationController registerToken failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to register token'], 500);
        }
    }

    public function list(Request $request)
    {
        try {
            $user = $request->user();

            if (! $user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }

            $notifications = $user->notifications()
                ->orderByDesc('created_at')
                ->paginate(20);

            return response()->json($notifications);
        } catch (\Throwable $e) {
            Log::error('NotificationController list failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to fetch notifications'], 500);
        }
    }

    public function markRead(Request $request, string $id)
    {
        try {
            $user = $request->user();

            if (! $user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }

            $notification = $user->notifications()->find($id);
            if (! $notification) {
                return response()->json(['success' => false, 'message' => 'Notification not found'], 404);
            }
            $notification->markAsRead();

            return response()->json(['success' => true, 'message' => 'Notification marked as read']);
        } catch (\Throwable $e) {
            Log::error('NotificationController markRead failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to mark notification as read'], 500);
        }
    }

    public function sendTest(Request $request)
    {
        try {
            $user = $request->user();

            if (! $user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }

            $data = $request->validate([
                'title' => 'required|string',
                'body' => 'required|string',
                'data' => 'array',
            ]);

            $user->notify(new AppPushNotification(
                $data['title'],
                $data['body'],
                $data['data'] ?? []
            ));

            return response()->json(['success' => true, 'message' => 'Test notification sent']);
        } catch (\Throwable $e) {
            Log::error('NotificationController sendTest failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to send test notification'], 500);
        }
    }
}

