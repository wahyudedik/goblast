<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FeatureMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Checks if the tenant's subscription plan includes the required feature.
     * Blocks access to features not available in the current plan.
     * Returns 403 with upgrade information for unauthorized access.
     *
     * @param  Closure(Request): (Response)  $next
     * @param  string  $feature  Feature name (reminder, api, multi_device)
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        // Superadmin has access to all features
        if ($user && $user->role === 'superadmin') {
            return $next($request);
        }

        // Support API token auth where tenant is set on request attributes
        $tenant = $request->attributes->get('tenant');

        if (! $tenant) {
            if (! $user || ! $user->tenant_id) {
                abort(403, 'User must belong to a tenant to access this resource.');
            }

            // Load tenant with active subscription and plan
            if (! $user->relationLoaded('tenant')) {
                $user->load('tenant');
            }

            $tenant = $user->tenant;
        }

        // Get active subscription
        $subscription = $tenant->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->with('plan')
            ->first();

        if (! $subscription) {
            abort(403, 'No active subscription found. Please subscribe to a plan to access this feature.');
        }

        $plan = $subscription->plan;

        // Map feature names to plan attributes
        $featureMap = [
            'reminder' => 'has_reminder',
            'api' => 'has_api',
            'multi_device' => 'has_multi_device',
        ];

        if (! isset($featureMap[$feature])) {
            abort(500, "Invalid feature name: {$feature}");
        }

        $planAttribute = $featureMap[$feature];

        // Check if plan has the required feature
        if (! $plan->{$planAttribute}) {
            $featureNames = [
                'reminder' => 'Reminder',
                'api' => 'API Access',
                'multi_device' => 'Multi-Device',
            ];

            $featureName = $featureNames[$feature] ?? $feature;

            abort(403, "The {$featureName} feature is not available in your current plan ({$plan->name}). Please upgrade to access this feature.");
        }

        return $next($request);
    }
}
