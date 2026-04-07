<?php

namespace Modules\RestApi\Http\Middleware;

use App\Models\DeliveryExecutive;
use Closure;
use Illuminate\Http\Request;

class VerifyPartnerUniqueCode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Expect partner code in X-PARTNER-CODE header (API clients should send this)
        $uniqueCode = $request->header('X-PARTNER-CODE') ?? $request->header('x-partner-code');

        if (!$uniqueCode) {
            return response()->json([
                'success' => false,
                'message' => 'Partner unique code is required in header (X-PARTNER-CODE)',
            ], 401);
        }

        $partner = DeliveryExecutive::where('unique_code', $uniqueCode)->first();

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid partner unique code',
            ], 401);
        }

        if ($partner->status === 'inactive') {
            return response()->json([
                'success' => false,
                'message' => 'Partner account is inactive',
            ], 403);
        }

        // Attach partner to request for use in controllers
        $request->merge(['partner' => $partner]);
        $request->setUserResolver(function () use ($partner) {
            return $partner;
        });

        return $next($request);
    }
}
