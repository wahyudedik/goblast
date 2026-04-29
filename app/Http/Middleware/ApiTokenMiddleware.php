<?php

namespace App\Http\Middleware;

use App\Services\Contracts\ApiTokenServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenMiddleware
{
    public function __construct(
        private ApiTokenServiceInterface $apiTokenService
    ) {}

    /**
     * Handle an incoming request.
     *
     * Extract token from Authorization header, validate it, and inject tenant context.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Extract token from Authorization header (Bearer token)
        $authHeader = $request->header('Authorization');

        if (! $authHeader || ! str_starts_with($authHeader, 'Bearer ')) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Token tidak valid atau tidak ditemukan',
            ], 401);
        }

        // Extract the token value
        $token = substr($authHeader, 7); // Remove "Bearer " prefix

        // Validate the token
        $apiToken = $this->apiTokenService->validate($token);

        if (! $apiToken) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Token tidak valid atau tidak ditemukan',
            ], 401);
        }

        // Inject tenant context into the request
        $request->merge(['tenant' => $apiToken->tenant]);
        $request->attributes->set('tenant', $apiToken->tenant);
        $request->attributes->set('api_token', $apiToken);

        // Track token usage
        $this->apiTokenService->trackUsage($apiToken);

        return $next($request);
    }
}
