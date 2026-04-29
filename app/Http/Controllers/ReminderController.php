<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReminderController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = Auth::user()->tenant;

        $query = $tenant->reminders()->with(['device', 'template']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $reminders = $query->latest()->get();

        return view('reminders.index', [
            'reminders' => $reminders,
            'selectedType' => $request->type,
        ]);
    }

    public function create(): View
    {
        $tenant = Auth::user()->tenant;

        $devices = $tenant->devices()->where('status', 'connected')->get();
        $templates = $tenant->templates()->orderBy('name')->get();

        return view('reminders.create', [
            'devices' => $devices,
            'templates' => $templates,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = Auth::user()->tenant;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['spp_due', 'invoice_unpaid', 'booking_tomorrow'])],
            'frequency' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'yearly'])],
            'send_time' => ['required', 'date_format:H:i'],
            'send_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'device_id' => ['required', 'exists:devices,id'],
            'template_id' => ['nullable', 'exists:templates,id'],
            'message' => ['required_without:template_id', 'nullable', 'string', 'max:4096'],
            'recipients_text' => ['required', 'string'],
        ]);

        $tenant->devices()->where('id', $validated['device_id'])->firstOrFail();

        if ($validated['template_id']) {
            $tenant->templates()->where('id', $validated['template_id'])->firstOrFail();
        }

        // Parse recipients
        $recipients = array_filter(
            array_map('trim', explode("\n", $validated['recipients_text']))
        );

        $reminder = $tenant->reminders()->create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'frequency' => $validated['frequency'],
            'send_time' => $validated['send_time'],
            'send_day' => $validated['send_day'] ?? null,
            'device_id' => $validated['device_id'],
            'template_id' => $validated['template_id'] ?? null,
            'message' => $validated['message'] ?? null,
            'recipients' => $recipients,
            'is_active' => true,
        ]);

        return redirect()
            ->route('reminders.show', $reminder)
            ->with('success', 'Reminder berhasil dibuat.');
    }

    public function show(Reminder $reminder): View
    {
        Gate::authorize('view', $reminder);

        $reminder->load(['device', 'template']);
        $reminder->loadCount('reminderLogs');

        $recentLogs = $reminder->reminderLogs()
            ->latest('sent_at')
            ->limit(20)
            ->get();

        return view('reminders.show', [
            'reminder' => $reminder,
            'recentLogs' => $recentLogs,
        ]);
    }

    public function edit(Reminder $reminder): View
    {
        Gate::authorize('update', $reminder);

        $tenant = Auth::user()->tenant;
        $devices = $tenant->devices()->where('status', 'connected')->get();
        $templates = $tenant->templates()->orderBy('name')->get();

        return view('reminders.edit', [
            'reminder' => $reminder,
            'devices' => $devices,
            'templates' => $templates,
        ]);
    }

    public function update(Request $request, Reminder $reminder): RedirectResponse
    {
        Gate::authorize('update', $reminder);

        $tenant = Auth::user()->tenant;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['spp_due', 'invoice_unpaid', 'booking_tomorrow'])],
            'frequency' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'yearly'])],
            'send_time' => ['required', 'date_format:H:i'],
            'send_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'device_id' => ['required', 'exists:devices,id'],
            'template_id' => ['nullable', 'exists:templates,id'],
            'message' => ['required_without:template_id', 'nullable', 'string', 'max:4096'],
            'recipients_text' => ['required', 'string'],
        ]);

        $tenant->devices()->where('id', $validated['device_id'])->firstOrFail();

        $recipients = array_filter(
            array_map('trim', explode("\n", $validated['recipients_text']))
        );

        $reminder->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'frequency' => $validated['frequency'],
            'send_time' => $validated['send_time'],
            'send_day' => $validated['send_day'] ?? null,
            'device_id' => $validated['device_id'],
            'template_id' => $validated['template_id'] ?? null,
            'message' => $validated['message'] ?? null,
            'recipients' => $recipients,
        ]);

        return redirect()
            ->route('reminders.show', $reminder)
            ->with('success', 'Reminder berhasil diperbarui.');
    }

    public function toggle(Reminder $reminder): RedirectResponse
    {
        Gate::authorize('update', $reminder);

        $reminder->update(['is_active' => ! $reminder->is_active]);

        $status = $reminder->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->back()->with('success', "Reminder berhasil {$status}.");
    }

    public function destroy(Reminder $reminder): RedirectResponse
    {
        Gate::authorize('delete', $reminder);

        $reminder->delete();

        return redirect()
            ->route('reminders.index')
            ->with('success', 'Reminder berhasil dihapus.');
    }
}
