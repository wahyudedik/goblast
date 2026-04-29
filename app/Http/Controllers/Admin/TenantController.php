<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTenantRequest;
use App\Http\Requests\Admin\UpdateTenantRequest;
use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantController extends Controller
{
    /**
     * Display a listing of tenants.
     */
    public function index(Request $request): View
    {
        $query = Tenant::query()
            ->withCount(['devices', 'messageLogs'])
            ->with(['subscriptions' => function ($q) {
                $q->where('status', 'active')->with('plan')->latest();
            }]);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by plan
        if ($request->filled('plan')) {
            $query->whereHas('subscriptions', function ($q) use ($request) {
                $q->where('status', 'active')
                    ->where('plan_id', $request->input('plan'));
            });
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $tenants = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.tenants.index', [
            'tenants' => $tenants,
            'plans' => $plans,
            'filters' => $request->only(['status', 'plan', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Show the form for creating a new tenant.
     */
    public function create(): View
    {
        return view('admin.tenants.create');
    }

    /**
     * Store a newly created tenant.
     */
    public function store(StoreTenantRequest $request): RedirectResponse
    {
        $tenant = Tenant::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
        ]);

        return redirect()
            ->route('admin.tenants.show', $tenant)
            ->with('success', 'Tenant berhasil dibuat dengan masa trial 14 hari.');
    }

    /**
     * Display the specified tenant.
     */
    public function show(Tenant $tenant): View
    {
        $tenant->load([
            'subscriptions' => function ($q) {
                $q->with('plan')->latest();
            },
            'users',
        ]);

        $tenant->loadCount([
            'devices',
            'devices as connected_devices_count' => function ($q) {
                $q->where('status', 'connected');
            },
            'messageLogs as total_sent_count' => function ($q) {
                $q->where('status', 'sent');
            },
            'messageLogs as total_failed_count' => function ($q) {
                $q->where('status', 'failed');
            },
        ]);

        $activeSubscription = $tenant->subscriptions
            ->where('status', 'active')
            ->first();

        return view('admin.tenants.show', [
            'tenant' => $tenant,
            'activeSubscription' => $activeSubscription,
        ]);
    }

    /**
     * Show the form for editing the specified tenant.
     */
    public function edit(Tenant $tenant): View
    {
        return view('admin.tenants.edit', [
            'tenant' => $tenant,
        ]);
    }

    /**
     * Update the specified tenant.
     */
    public function update(UpdateTenantRequest $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->safe()->only(['name', 'email', 'phone']);

        $tenant->update($data);

        return redirect()
            ->route('admin.tenants.show', $tenant)
            ->with('success', 'Data tenant berhasil diperbarui.');
    }

    /**
     * Suspend the specified tenant.
     */
    public function suspend(Request $request, Tenant $tenant): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        if ($tenant->status === 'suspended') {
            return redirect()
                ->back()
                ->with('error', 'Tenant sudah dalam status suspended.');
        }

        $tenant->update([
            'status' => 'suspended',
            'suspended_at' => now(),
            'suspended_reason' => $request->input('reason'),
        ]);

        return redirect()
            ->route('admin.tenants.show', $tenant)
            ->with('success', 'Tenant berhasil di-suspend.');
    }

    /**
     * Activate the specified tenant.
     */
    public function activate(Tenant $tenant): RedirectResponse
    {
        if ($tenant->status === 'active') {
            return redirect()
                ->back()
                ->with('error', 'Tenant sudah dalam status aktif.');
        }

        $tenant->update([
            'status' => 'active',
            'suspended_at' => null,
            'suspended_reason' => null,
        ]);

        return redirect()
            ->route('admin.tenants.show', $tenant)
            ->with('success', 'Tenant berhasil diaktifkan kembali.');
    }

    /**
     * Extend the trial period for the specified tenant.
     */
    public function extendTrial(Request $request, Tenant $tenant): RedirectResponse
    {
        $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:90'],
        ]);

        if ($tenant->status !== 'trial') {
            return redirect()
                ->back()
                ->with('error', 'Hanya tenant dengan status trial yang dapat diperpanjang.');
        }

        $currentEnd = $tenant->trial_ends_at ?? now();
        $tenant->update([
            'trial_ends_at' => $currentEnd->addDays($request->input('days')),
        ]);

        return redirect()
            ->route('admin.tenants.show', $tenant)
            ->with('success', "Masa trial berhasil diperpanjang {$request->input('days')} hari.");
    }

    /**
     * Remove the specified tenant.
     */
    public function destroy(Tenant $tenant): RedirectResponse
    {
        // Check for active devices or pending jobs
        $activeDevices = $tenant->devices()->whereIn('status', ['connected', 'pending'])->count();
        $pendingJobs = $tenant->messageLogs()->where('status', 'pending')->count();

        if ($activeDevices > 0 || $pendingJobs > 0) {
            return redirect()
                ->back()
                ->with('error', "Tidak dapat menghapus tenant. Masih ada {$activeDevices} device aktif dan {$pendingJobs} job pending.");
        }

        $tenant->delete();

        return redirect()
            ->route('admin.tenants.index')
            ->with('success', 'Tenant berhasil dihapus.');
    }
}
