<?php

namespace App\Http\Controllers;

use App\Models\KeywordRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class KeywordRuleController extends Controller
{
    public function index(): View
    {
        $tenant = Auth::user()->tenant;

        $rules = $tenant->keywordRules()
            ->with('device')
            ->orderBy('priority')
            ->orderBy('keyword')
            ->get();

        return view('auto-reply.index', ['rules' => $rules]);
    }

    public function create(): View
    {
        $tenant = Auth::user()->tenant;
        $devices = $tenant->devices()->where('status', 'connected')->get();

        return view('auto-reply.create', ['devices' => $devices]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = Auth::user()->tenant;

        $validated = $request->validate([
            'keyword' => ['required', 'string', 'max:255'],
            'reply' => ['required', 'string', 'max:4096'],
            'device_id' => ['required', 'exists:devices,id'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $tenant->devices()->where('id', $validated['device_id'])->firstOrFail();

        // Check duplicate keyword per device
        $exists = $tenant->keywordRules()
            ->where('device_id', $validated['device_id'])
            ->where('keyword', $validated['keyword'])
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'Keyword ini sudah ada untuk device yang dipilih.');
        }

        $tenant->keywordRules()->create([
            'keyword' => $validated['keyword'],
            'reply' => $validated['reply'],
            'device_id' => $validated['device_id'],
            'priority' => $validated['priority'] ?? 0,
            'is_active' => true,
        ]);

        return redirect()->route('auto-reply.index')->with('success', 'Auto reply berhasil dibuat.');
    }

    public function edit(KeywordRule $keywordRule): View
    {
        Gate::authorize('update', $keywordRule);

        $tenant = Auth::user()->tenant;
        $devices = $tenant->devices()->where('status', 'connected')->get();

        return view('auto-reply.edit', ['rule' => $keywordRule, 'devices' => $devices]);
    }

    public function update(Request $request, KeywordRule $keywordRule): RedirectResponse
    {
        Gate::authorize('update', $keywordRule);

        $tenant = Auth::user()->tenant;

        $validated = $request->validate([
            'keyword' => ['required', 'string', 'max:255'],
            'reply' => ['required', 'string', 'max:4096'],
            'device_id' => ['required', 'exists:devices,id'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        // Check duplicate (exclude current)
        $exists = $tenant->keywordRules()
            ->where('device_id', $validated['device_id'])
            ->where('keyword', $validated['keyword'])
            ->where('id', '!=', $keywordRule->id)
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'Keyword ini sudah ada untuk device yang dipilih.');
        }

        $keywordRule->update([
            'keyword' => $validated['keyword'],
            'reply' => $validated['reply'],
            'device_id' => $validated['device_id'],
            'priority' => $validated['priority'] ?? 0,
        ]);

        return redirect()->route('auto-reply.index')->with('success', 'Auto reply berhasil diperbarui.');
    }

    public function toggle(KeywordRule $keywordRule): RedirectResponse
    {
        Gate::authorize('update', $keywordRule);

        $keywordRule->update(['is_active' => ! $keywordRule->is_active]);

        $status = $keywordRule->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->back()->with('success', "Auto reply berhasil {$status}.");
    }

    public function destroy(KeywordRule $keywordRule): RedirectResponse
    {
        Gate::authorize('delete', $keywordRule);

        $keywordRule->delete();

        return redirect()->route('auto-reply.index')->with('success', 'Auto reply berhasil dihapus.');
    }
}
