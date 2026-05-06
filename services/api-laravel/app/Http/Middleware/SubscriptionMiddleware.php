<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->user()?->tenant;

        if (!$tenant || !$tenant->plan) {
            return $next($request);
        }

        $plan = $tenant->plan;

        // Example check: Device limit
        if ($request->is('api/v1/devices') && $request->isMethod('post')) {
            if ($tenant->devices()->count() >= $plan->device_limit) {
                return ApiResponse::error('Device limit reached for your plan.', 'PLAN_DEVICE_LIMIT_REACHED', 422);
            }
        }

        return $next($request);
    }
}
