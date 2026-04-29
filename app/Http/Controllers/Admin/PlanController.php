<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePlanRequest;
use App\Http\Requests\Admin\UpdatePlanRequest;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PlanController extends Controller
{
    /**
     * Display a listing of plans.
     */
    public function index(): View
    {
        $plans = Plan::query()
            ->withCount(['subscriptions as active_subscriptions_count' => function ($q) {
                $q->where('status', 'active');
            }])
            ->orderBy('sort_order')
            ->get();

        return view('admin.plans.index', [
            'plans' => $plans,
        ]);
    }

    /**
     * Show the form for creating a new plan.
     */
    public function create(): View
    {
        return view('admin.plans.create');
    }

    /**
     * Store a newly created plan.
     */
    public function store(StorePlanRequest $request): RedirectResponse
    {
        Plan::create($request->validated());

        return redirect()
            ->route('admin.plans.index')
            ->with('success', 'Paket berhasil dibuat.');
    }

    /**
     * Show the form for editing the specified plan.
     */
    public function edit(Plan $plan): View
    {
        $activeSubscriptionCount = $plan->subscriptions()->where('status', 'active')->count();

        return view('admin.plans.edit', [
            'plan' => $plan,
            'activeSubscriptionCount' => $activeSubscriptionCount,
        ]);
    }

    /**
     * Update the specified plan.
     */
    public function update(UpdatePlanRequest $request, Plan $plan): RedirectResponse
    {
        $plan->update($request->validated());

        return redirect()
            ->route('admin.plans.index')
            ->with('success', 'Paket berhasil diperbarui.');
    }

    /**
     * Toggle the active status of the specified plan.
     */
    public function toggleActive(Plan $plan): RedirectResponse
    {
        $plan->update(['is_active' => ! $plan->is_active]);

        $status = $plan->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()
            ->route('admin.plans.index')
            ->with('success', "Paket berhasil {$status}.");
    }

    /**
     * Remove the specified plan.
     */
    public function destroy(Plan $plan): RedirectResponse
    {
        $activeSubscriptions = $plan->subscriptions()->where('status', 'active')->count();

        if ($activeSubscriptions > 0) {
            return redirect()
                ->back()
                ->with('error', "Tidak dapat menghapus paket. Masih ada {$activeSubscriptions} langganan aktif yang menggunakan paket ini.");
        }

        $totalSubscriptions = $plan->subscriptions()->count();

        if ($totalSubscriptions > 0) {
            return redirect()
                ->back()
                ->with('error', "Tidak dapat menghapus paket. Masih ada {$totalSubscriptions} langganan yang terkait dengan paket ini.");
        }

        $plan->delete();

        return redirect()
            ->route('admin.plans.index')
            ->with('success', 'Paket berhasil dihapus.');
    }
}
