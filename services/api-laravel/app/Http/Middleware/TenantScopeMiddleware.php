<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantScopeMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::error('Unauthenticated', 'UNAUTHENTICATED', 401);
        }

        if ($user->role === 'super_admin') {
            if ($request->filled('tenant_id')) {
                $request->attributes->set('tenant_id', (int) $request->input('tenant_id'));
            }

            return $next($request);
        }

        if (! $user->tenant_id) {
            return ApiResponse::error('User is not assigned to a tenant', 'TENANT_SCOPE_MISSING', 403);
        }

        $request->attributes->set('tenant_id', $user->tenant_id);

        return $next($request);
    }
}
