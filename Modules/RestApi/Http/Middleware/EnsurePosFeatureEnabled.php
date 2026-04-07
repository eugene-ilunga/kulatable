<?php

namespace Modules\RestApi\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePosFeatureEnabled
{
    public function handle(Request $request, Closure $next)
    {
        // Allow superadmin (no restaurant) to bypass plan check
        if (user() && is_null(user()->restaurant_id)) {
            return $next($request);
        }

        $modules = restaurant_modules();
        $allowedKeys = ['POS', 'Pos', 'pos', 'Order', 'Orders', 'order', 'orders'];

        $hasAccess = collect($modules)->contains(function ($item) use ($allowedKeys) {
            return in_array($item, $allowedKeys, true);
        });

        if (! $hasAccess) {
            return response()->json([
                'message' => __('applicationintegration::messages.plan_not_allowed'),
            ], 403);
        }

        return $next($request);
    }
}

