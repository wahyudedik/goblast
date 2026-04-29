<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms' => ['required', 'accepted'],
        ]);

        // Create tenant and user with trial subscription in a transaction
        DB::transaction(function () use ($request, &$user) {
            // Create tenant with trial status
            $trialDays = config('wa-automation.trial_duration_days', 14);
            $tenant = Tenant::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'status' => 'trial',
                'trial_ends_at' => now()->addDays($trialDays),
            ]);

            // Create user associated with tenant
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'admin',
                'is_active' => true,
            ]);

            // Get starter plan for trial
            $starterPlan = Plan::where('slug', 'starter')->first();

            if ($starterPlan) {
                // Create trial subscription
                Subscription::create([
                    'tenant_id' => $tenant->id,
                    'plan_id' => $starterPlan->id,
                    'status' => 'active',
                    'message_quota_used' => 0,
                    'message_quota_limit' => $starterPlan->message_quota,
                    'starts_at' => now(),
                    'ends_at' => now()->addDays($trialDays),
                ]);
            }
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}
