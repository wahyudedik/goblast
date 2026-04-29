<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Checks if the authenticated user has one of the required roles.
     * Returns 403 for unauthorized access.
     *
     * @param  Closure(Request): (Response)  $next
     * @param  string  ...$roles  One or more roles (superadmin, admin, member)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Authentication required.');
        }

        // Check if user has one of the required roles
        if (! in_array($user->role, $roles, true)) {
            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
