<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInvoiceRequest;
use App\Http\Requests\Admin\UpdateInvoiceRequest;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\Contracts\BillingServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    /**
     * Display a listing of invoices.
     */
    public function index(Request $request): View
    {
        $query = Invoice::query()
            ->with(['tenant', 'plan', 'recordedBy']);

        // Filter by tenant
        if ($request->filled('tenant')) {
            $query->where('tenant_id', $request->input('tenant'));
        }

        // Filter by plan
        if ($request->filled('plan')) {
            $query->where('plan_id', $request->input('plan'));
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('paid_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('paid_at', '<=', $request->input('date_to'));
        }

        $invoices = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        $tenants = Tenant::orderBy('name')->get(['id', 'name']);
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get(['id', 'name']);

        return view('admin.invoices.index', [
            'invoices' => $invoices,
            'tenants' => $tenants,
            'plans' => $plans,
            'filters' => $request->only(['tenant', 'plan', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Show the form for creating a new invoice.
     */
    public function create(): View
    {
        $tenants = Tenant::orderBy('name')->get(['id', 'name']);
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.invoices.create', [
            'tenants' => $tenants,
            'plans' => $plans,
        ]);
    }

    /**
     * Store a newly created invoice and activate/extend subscription.
     */
    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $plan = Plan::findOrFail($validated['plan_id']);
        $tenant = Tenant::findOrFail($validated['tenant_id']);

        $billingService = app(BillingServiceInterface::class);

        $paymentData = [
            'amount' => $validated['amount'],
            'duration_days' => $validated['duration_days'],
            'paid_at' => $validated['paid_at'],
            'notes' => $validated['notes'] ?? null,
        ];

        // Check if tenant has an active subscription with the same plan
        $activeSubscription = $tenant->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->latest('ends_at')
            ->first();

        if ($activeSubscription && $activeSubscription->plan_id === $plan->id) {
            // Same plan: extend the existing subscription
            $invoice = $billingService->extendSubscription($tenant, $plan, $paymentData, $request->user());
        } else {
            // Different plan or no active subscription: activate new subscription (expires old one)
            $subscription = $billingService->activateSubscription($tenant, $plan, $paymentData, $request->user());
            $invoice = $subscription->invoices()->latest()->first();
        }

        return redirect()
            ->route('admin.invoices.show', $invoice)
            ->with('success', 'Invoice berhasil dicatat dan langganan telah diaktifkan.');
    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice): View
    {
        $invoice->load(['tenant', 'plan', 'subscription.plan', 'recordedBy']);

        return view('admin.invoices.show', [
            'invoice' => $invoice,
        ]);
    }

    /**
     * Show the form for editing the specified invoice.
     */
    public function edit(Invoice $invoice): View
    {
        $invoice->load(['tenant', 'plan']);

        return view('admin.invoices.edit', [
            'invoice' => $invoice,
        ]);
    }

    /**
     * Update the specified invoice.
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $invoice->update($request->validated());

        return redirect()
            ->route('admin.invoices.show', $invoice)
            ->with('success', 'Invoice berhasil diperbarui.');
    }

    /**
     * Remove the specified invoice.
     */
    public function destroy(Invoice $invoice): RedirectResponse
    {
        $invoice->delete();

        return redirect()
            ->route('admin.invoices.index')
            ->with('success', 'Invoice berhasil dihapus.');
    }
}
