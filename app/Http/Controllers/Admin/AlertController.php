<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlertController extends Controller
{
    /**
     * Display a listing of alerts.
     */
    public function index(Request $request): View
    {
        $query = Alert::query()->with(['tenant', 'resolvedBy']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by severity
        if ($request->filled('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $alerts = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        $alertTypes = Alert::query()->distinct()->pluck('type')->sort()->values();

        return view('admin.alerts.index', [
            'alerts' => $alerts,
            'alertTypes' => $alertTypes,
            'filters' => $request->only(['status', 'severity', 'type', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Display the specified alert.
     */
    public function show(Alert $alert): View
    {
        $alert->load(['tenant', 'resolvedBy']);

        return view('admin.alerts.show', [
            'alert' => $alert,
        ]);
    }

    /**
     * Resolve the specified alert.
     */
    public function resolve(Alert $alert): RedirectResponse
    {
        if ($alert->status === 'resolved') {
            return redirect()
                ->back()
                ->with('error', 'Alert sudah dalam status resolved.');
        }

        $alert->update([
            'status' => 'resolved',
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Alert berhasil di-resolve.');
    }

    /**
     * Remove the specified alert.
     */
    public function destroy(Alert $alert): RedirectResponse
    {
        $alert->delete();

        return redirect()
            ->route('admin.alerts.index')
            ->with('success', 'Alert berhasil dihapus.');
    }
}
