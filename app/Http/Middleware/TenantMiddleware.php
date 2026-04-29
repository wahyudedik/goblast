<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Validates that the authenticated user belongs to a tenant,
     * injects tenant context into the request, and prevents
     * cross-tenant data access.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Superadmin users should not access tenant routes
        if ($user && $user->role === 'superadmin') {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['notifications' => [], 'unread_count' => 0]);
            }

            return redirect()->route('admin.dashboard');
        }

        // Ensure user has a tenant
        if (! $user || ! $user->tenant_id) {
            abort(403, 'User must belong to a tenant to access this resource.');
        }

        // Load tenant relationship if not already loaded
        if (! $user->relationLoaded('tenant')) {
            $user->load('tenant');
        }

        // Inject tenant into request for easy access in controllers
        $request->merge(['tenant' => $user->tenant]);

        return $next($request);
    }
}
