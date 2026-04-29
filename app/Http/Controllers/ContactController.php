<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = Auth::user()->tenant;
        $query = $tenant->contacts();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('group', 'like', "%{$search}%");
            });
        }

        if ($request->filled('group')) {
            $query->where('group', $request->group);
        }

        $contacts = $query->orderBy('name')->orderBy('phone_number')->paginate(25);

        $groups = $tenant->contacts()
            ->whereNotNull('group')
            ->distinct()
            ->pluck('group');

        return view('contacts.index', [
            'contacts' => $contacts,
            'groups' => $groups,
            'filters' => $request->only(['search', 'group']),
        ]);
    }

    public function create(): View
    {
        $tenant = Auth::user()->tenant;
        $groups = $tenant->contacts()->whereNotNull('group')->distinct()->pluck('group');

        return view('contacts.create', ['groups' => $groups]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = Auth::user()->tenant;

        $validated = $request->validate([
            'phone_number' => ['required', 'string', 'regex:/^62[0-9]{9,13}$/'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'group' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Check duplicate
        $exists = $tenant->contacts()->where('phone_number', $validated['phone_number'])->exists();
        if ($exists) {
            return back()->withInput()->with('error', 'Nomor telepon sudah ada di kontak.');
        }

        $tenant->contacts()->create($validated);

        return redirect()->route('contacts.index')->with('success', 'Kontak berhasil ditambahkan.');
    }

    public function edit(Contact $contact): View
    {
        $tenant = Auth::user()->tenant;
        abort_unless($contact->tenant_id === $tenant->id, 403);

        $groups = $tenant->contacts()->whereNotNull('group')->distinct()->pluck('group');

        return view('contacts.edit', ['contact' => $contact, 'groups' => $groups]);
    }

    public function update(Request $request, Contact $contact): RedirectResponse
    {
        $tenant = Auth::user()->tenant;
        abort_unless($contact->tenant_id === $tenant->id, 403);

        $validated = $request->validate([
            'phone_number' => ['required', 'string', 'regex:/^62[0-9]{9,13}$/'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'group' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Check duplicate (exclude current)
        $exists = $tenant->contacts()
            ->where('phone_number', $validated['phone_number'])
            ->where('id', '!=', $contact->id)
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'Nomor telepon sudah ada di kontak lain.');
        }

        $contact->update($validated);

        return redirect()->route('contacts.index')->with('success', 'Kontak berhasil diperbarui.');
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $tenant = Auth::user()->tenant;
        abort_unless($contact->tenant_id === $tenant->id, 403);

        $contact->delete();

        return redirect()->route('contacts.index')->with('success', 'Kontak berhasil dihapus.');
    }

    /**
     * Import contacts from CSV.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'group' => ['nullable', 'string', 'max:100'],
        ]);

        $tenant = Auth::user()->tenant;
        $file = $request->file('csv_file');
        $group = $request->group;

        $handle = fopen($file->getRealPath(), 'r');
        $imported = 0;
        $skipped = 0;

        while (($line = fgetcsv($handle)) !== false) {
            $phone = trim($line[0] ?? '');
            $name = trim($line[1] ?? '');

            if (! preg_match('/^62[0-9]{9,13}$/', $phone)) {
                $skipped++;

                continue;
            }

            $tenant->contacts()->updateOrCreate(
                ['phone_number' => $phone],
                [
                    'name' => $name ?: null,
                    'group' => $group ?: null,
                ]
            );
            $imported++;
        }

        fclose($handle);

        return redirect()->route('contacts.index')
            ->with('success', "Berhasil import {$imported} kontak. {$skipped} dilewati.");
    }

    /**
     * API: Get contacts for select2/autocomplete (used by broadcast & reminder forms).
     */
    public function apiSearch(Request $request): JsonResponse
    {
        $tenant = Auth::user()->tenant;
        $query = $tenant->contacts();

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('group')) {
            $query->where('group', $request->group);
        }

        $contacts = $query->orderBy('name')->limit(50)->get(['id', 'phone_number', 'name', 'group']);

        return response()->json($contacts);
    }

    /**
     * API: Get phone numbers by group (for broadcast/reminder recipient selection).
     */
    public function apiByGroup(Request $request): JsonResponse
    {
        $tenant = Auth::user()->tenant;

        $contacts = $tenant->contacts()
            ->when($request->group, fn ($q) => $q->where('group', $request->group))
            ->pluck('phone_number');

        return response()->json(['phone_numbers' => $contacts]);
    }
}
