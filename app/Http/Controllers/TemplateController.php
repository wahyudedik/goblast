<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTemplateRequest;
use App\Http\Requests\UpdateTemplateRequest;
use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $tenant = Auth::user()->tenant;

        $query = $tenant->templates();

        // Filter by type if provided
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $templates = $query->latest()->get();

        return view('templates.index', [
            'templates' => $templates,
            'selectedType' => $request->type,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('templates.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTemplateRequest $request): RedirectResponse
    {
        $tenant = Auth::user()->tenant;

        // Extract variables from content
        preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $request->content, $matches);
        $variables = array_unique($matches[1]);

        $template = $tenant->templates()->create([
            'name' => $request->name,
            'type' => $request->type,
            'content' => $request->content,
            'variables' => $variables,
        ]);

        return redirect()
            ->route('templates.show', $template)
            ->with('success', 'Template created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Template $template): View
    {
        // Load relationships with counts
        $template->load([
            'broadcasts' => fn ($query) => $query->latest()->limit(10),
            'reminders' => fn ($query) => $query->latest()->limit(10),
        ]);

        $template->loadCount(['broadcasts', 'reminders']);

        return view('templates.show', [
            'template' => $template,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Template $template): View
    {
        // Load usage counts
        $template->loadCount(['broadcasts', 'reminders']);

        return view('templates.edit', [
            'template' => $template,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTemplateRequest $request, Template $template): RedirectResponse
    {
        // Extract variables from content
        preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $request->content, $matches);
        $variables = array_unique($matches[1]);

        $template->update([
            'name' => $request->name,
            'type' => $request->type,
            'content' => $request->content,
            'variables' => $variables,
        ]);

        return redirect()
            ->route('templates.show', $template)
            ->with('success', 'Template updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Template $template): RedirectResponse
    {
        // Check if template is being used by active reminders
        $activeRemindersCount = $template->reminders()->where('is_active', true)->count();

        if ($activeRemindersCount > 0) {
            return redirect()
                ->back()
                ->with('error', "Cannot delete template. It is being used by {$activeRemindersCount} active reminder(s).");
        }

        $template->delete();

        return redirect()
            ->route('templates.index')
            ->with('success', 'Template deleted successfully.');
    }

    /**
     * Duplicate the specified template.
     */
    public function duplicate(Template $template): RedirectResponse
    {
        $tenant = Auth::user()->tenant;

        $newTemplate = $tenant->templates()->create([
            'name' => $template->name.' (Copy)',
            'type' => $template->type,
            'content' => $template->content,
            'variables' => $template->variables,
        ]);

        return redirect()
            ->route('templates.edit', $newTemplate)
            ->with('success', 'Template duplicated successfully. You can now edit it.');
    }
}
