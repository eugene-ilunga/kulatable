<?php

namespace Modules\MultiPOS\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\MultiPOS\Entities\PosMachine;

class ResolvePosMachine
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
        $cookieName = config('multipos.cookie.name', 'pos_token');
        $deviceId = $request->cookie($cookieName);

        $machine = null;

        if ($deviceId) {
            // Get current branch from session
            $branchId = session('branch')?->id ?? null;

            if ($branchId) {
                // Find machine by device_id and branch_id - allow pending, active, and declined status
                $machine = PosMachine::where('device_id', $deviceId)
                    ->where('branch_id', $branchId)
                    ->first();

                if ($machine && in_array($machine->status, ['pending', 'active'])) {
                    // Update last seen timestamp only for pending and active machines
                    $machine->updateQuietly(['last_seen_at' => now()]);
                }
            }
        }

        // Attach machine (or null) to request for use in controllers
        $request->attributes->set('posMachine', $machine);

        return $next($request);
    }
}

