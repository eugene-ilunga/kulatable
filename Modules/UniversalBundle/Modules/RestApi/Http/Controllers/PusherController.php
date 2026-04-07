<?php

namespace Modules\RestApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PusherSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class PusherController extends Controller
{
    /**
     * Get Pusher settings
     *
     * @note This endpoint is accessible to ALL authenticated users (superadmin, admin, staff).
     *       Pusher settings are system-wide configuration, not per-restaurant.
     *
     * @response 200 {
     *   "id": 1,
     *   "beamer_status": true,
     *   "instance_id": "instance_123",
     *   "beam_secret": "secret_123",
     *   "pusher_broadcast": true,
     *   "pusher_app_id": "app_id_123",
     *   "pusher_key": "key_123",
     *   "pusher_secret": "secret_123",
     *   "pusher_cluster": "mt1",
     *   "is_enabled_pusher_broadcast": true
     * }
     */
    public function getPusherSettings()
    {
        // Use query builder to fetch raw data (avoid Eloquent model issues)
        $settings = DB::table('pusher_settings')
            ->where('id', 1)
            ->first();

        if (!$settings) {
            return response()->json([
                'message' => 'Pusher settings not found',
                'data' => null
            ], 404);
        }

        // Compute is_enabled_pusher_broadcast manually
        $isEnabled = (bool)$settings->pusher_broadcast &&
                    !empty($settings->pusher_app_id) &&
                    !empty($settings->pusher_key) &&
                    !empty($settings->pusher_secret) &&
                    !empty($settings->pusher_cluster);

        // Return only Pusher Channels (broadcast) configuration
        return response()->json([
            'data' => [
                'id' => (int)$settings->id,
                'pusher_broadcast' => (bool)$settings->pusher_broadcast,
                'pusher_app_id' => (string)($settings->pusher_app_id ?? ''),
                'pusher_key' => (string)($settings->pusher_key ?? ''),
                'pusher_secret' => (string)($settings->pusher_secret ?? ''),
                'pusher_cluster' => (string)($settings->pusher_cluster ?? ''),
                'is_enabled_pusher_broadcast' => $isEnabled,
            ]
        ]);
    }

    /**
     * Get Pusher Broadcast settings (for real-time updates)
     *
     * @response 200 {
     *   "pusher_broadcast": true,
     *   "pusher_app_id": "app_id_123",
     *   "pusher_key": "key_123",
     *   "pusher_cluster": "mt1",
     *   "is_enabled": true
     * }
     */
    public function getPusherBroadcastSettings()
    {
        $settings = DB::table('pusher_settings')
            ->where('id', 1)
            ->first();

        if (!$settings) {
            return response()->json([
                'message' => 'Pusher Channels settings not configured',
                'data' => null
            ], 404);
        }

        $isEnabled = (bool)$settings->pusher_broadcast &&
                    !empty($settings->pusher_app_id) &&
                    !empty($settings->pusher_key) &&
                    !empty($settings->pusher_secret) &&
                    !empty($settings->pusher_cluster);

        return response()->json([
            'data' => [
                'pusher_broadcast' => (bool)$settings->pusher_broadcast,
                'pusher_app_id' => (string)($settings->pusher_app_id ?? ''),
                'pusher_key' => (string)($settings->pusher_key ?? ''),
                'pusher_cluster' => (string)($settings->pusher_cluster ?? ''),
                'is_enabled' => $isEnabled
            ]
        ]);
    }

    /**
     * Get Pusher Beams settings (for push notifications)
     *
     * @response 200 {
     *   "beamer_status": true,
     *   "instance_id": "instance_123",
     *   "beam_secret": "secret_123",
     *   "is_enabled": true
     * }
     */
    public function getPusherBeamsSettings()
    {
        try {
            $settings = PusherSetting::where('id', 1)->first();

            if (!$settings) {
                return response()->json([
                    'message' => 'Pusher Beams settings not configured',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'data' => [
                    'beamer_status' => (bool) $settings->beamer_status,
                    'instance_id' => $settings->instance_id,
                    'beam_secret' => $settings->beam_secret,
                    'is_enabled' => $settings->beamer_status && $settings->instance_id && $settings->beam_secret
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving Pusher Beams settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if Pusher is configured and enabled
     *
     * @note This is a quick health check accessible to all authenticated users.
     *
     * @response 200 {
     *   "pusher_broadcast_enabled": true,
     *   "pusher_beams_enabled": true,
     *   "any_pusher_enabled": true
     * }
     */
    public function checkPusherStatus()
    {
        $settings = DB::table('pusher_settings')
            ->where('id', 1)
            ->first();

        if (!$settings) {
            return response()->json([
                'pusher_channels_enabled' => false
            ]);
        }

        $broadcastEnabled = (bool)$settings->pusher_broadcast &&
                           !empty($settings->pusher_app_id) &&
                           !empty($settings->pusher_key) &&
                           !empty($settings->pusher_secret) &&
                           !empty($settings->pusher_cluster);

        return response()->json([
            'pusher_channels_enabled' => $broadcastEnabled
        ]);
    }

    /**
     * Get Pusher channel authorization
     *
     * @bodyParam channel_name string required The channel to authorize. Example: "orders.1"
     * @bodyParam socket_id string required The socket ID for the connection. Example: "socket_123"
     *
     * @response 200 {
     *   "auth": "auth_signature",
     *   "shared_secret": "optional_shared_secret"
     * }
     */
    public function authorizeChannel(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 401);
            }

            $channelName = $request->input('channel_name');
            $socketId = $request->input('socket_id');

            if (!$channelName || !$socketId) {
                return response()->json([
                    'message' => 'channel_name and socket_id are required'
                ], 400);
            }

            $settings = PusherSetting::first();

            if (!$settings || !$settings->is_enabled_pusher_broadcast) {
                return response()->json([
                    'message' => 'Pusher broadcast is not enabled'
                ], 503);
            }

            // Using Laravel Broadcasting helpers
            if (!function_exists('app')) {
                return response()->json([
                    'message' => 'Unable to authorize channel'
                ], 500);
            }

            try {
                $pusher = app('pusher');

                // Extract channel info
                $channelParts = explode('.', $channelName);

                // Verify user can access this channel
                if (count($channelParts) >= 2) {
                    $resourceType = $channelParts[0];
                    $resourceId = $channelParts[1];

                    // Basic authorization - extend based on your needs
                    // For example: verify user can access order/resource
                }

                $auth = $pusher->socket_auth($socketId, $channelName);

                return response()->json([
                    'auth' => $auth
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Failed to authorize channel',
                    'error' => $e->getMessage()
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error authorizing channel',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Pusher presence channel members
     *
     * @urlParam channel string required The presence channel name. Example: "orders.presence.1"
     *
     * @response 200 {
     *   "members": [
     *     {
     *       "id": "user_1",
     *       "info": {
     *         "name": "John Doe"
     *       }
     *     }
     *   ],
     *   "count": 1
     * }
     */
    public function getPresenceChannelMembers($channel)
    {
        try {
            $settings = PusherSetting::first();

            if (!$settings || !$settings->is_enabled_pusher_broadcast) {
                return response()->json([
                    'message' => 'Pusher broadcast is not enabled'
                ], 503);
            }

            try {
                $pusher = app('pusher');
                $response = $pusher->get("/channels/{$channel}/users");

                return response()->json([
                    'members' => $response['users'] ?? [],
                    'count' => count($response['users'] ?? [])
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Error retrieving presence channel members',
                    'error' => $e->getMessage()
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error getting presence channel members',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Diagnostic endpoint for troubleshooting Pusher settings
     * Returns detailed information about the table and configuration
     */
    public function diagnostics()
    {
        $diagnostics = [
            'timestamp' => now()->toIso8601String(),
            'authenticated' => Auth::check(),
            'user_id' => Auth::id(),
        ];

        // Check if table exists
        try {
            $tableExists = Schema::hasTable('pusher_settings');
            $diagnostics['table_exists'] = $tableExists;
        } catch (\Exception $e) {
            $diagnostics['table_exists'] = false;
            $diagnostics['table_check_error'] = $e->getMessage();
        }

        // Try to count records
        try {
            $count = DB::table('pusher_settings')->count();
            $diagnostics['record_count'] = $count;
        } catch (\Exception $e) {
            $diagnostics['record_count'] = null;
            $diagnostics['count_error'] = $e->getMessage();
        }

        // Try to fetch record ID 1
        try {
            $settings = DB::table('pusher_settings')
                ->where('id', 1)
                ->first();

            if ($settings) {
                $diagnostics['id_1_exists'] = true;
                $diagnostics['id_1_fields'] = array_keys((array)$settings);
                $diagnostics['id_1_data'] = [
                    'id' => $settings->id ?? 'missing',
                    'pusher_broadcast' => $settings->pusher_broadcast ?? 'missing',
                    'pusher_app_id' => !empty($settings->pusher_app_id) ? '***' : 'empty',
                    'pusher_key' => !empty($settings->pusher_key) ? '***' : 'empty',
                    'pusher_secret' => !empty($settings->pusher_secret) ? '***' : 'empty',
                    'pusher_cluster' => $settings->pusher_cluster ?? 'missing',
                ];
            } else {
                $diagnostics['id_1_exists'] = false;
                $diagnostics['id_1_message'] = 'No record with ID 1 found';
            }
        } catch (\Exception $e) {
            $diagnostics['id_1_exists'] = false;
            $diagnostics['id_1_error'] = $e->getMessage();
        }

        return response()->json([
            'data' => $diagnostics
        ]);
    }
}
