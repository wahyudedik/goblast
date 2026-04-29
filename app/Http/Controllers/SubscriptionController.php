<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = $request->user()->tenant;

        // Get current active subscription with plan
        $currentSubscription = $tenant->subscriptions()
            ->with('plan')
            ->where('status', 'active')
            ->latest()
            ->first();

        // Get all active plans for comparison
        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        // Calculate remaining quota
        $remainingQuota = null;
        $quotaPercentage = 0;

        if ($currentSubscription) {
            if ($currentSubscription->message_quota_limit === null) {
                // Unlimited quota
                $remainingQuota = 'Unlimited';
                $quotaPercentage = 0;
            } else {
                $remainingQuota = max(0, $currentSubscription->message_quota_limit - $currentSubscription->message_quota_used);
                $quotaPercentage = $currentSubscription->message_quota_limit > 0
                    ? ($currentSubscription->message_quota_used / $currentSubscription->message_quota_limit) * 100
                    : 0;
            }
        }

        // Check if in trial period
        $isInTrial = $tenant->status === 'trial' && $tenant->trial_ends_at && $tenant->trial_ends_at->isFuture();
        $trialDaysRemaining = $isInTrial ? (int) now()->diffInDays($tenant->trial_ends_at, false) : 0;

        return view('subscriptions.index', [
            'tenant' => $tenant,
            'currentSubscription' => $currentSubscription,
            'plans' => $plans,
            'remainingQuota' => $remainingQuota,
            'quotaPercentage' => $quotaPercentage,
            'isInTrial' => $isInTrial,
            'trialDaysRemaining' => $trialDaysRemaining,
        ]);
    }

    public function plans(): View
    {
        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        return view('subscriptions.plans', [
            'plans' => $plans,
        ]);
    }
}
