<?php

namespace App\Http\Controllers;

use App\Models\ApiToken;
use App\Services\Contracts\ApiTokenServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ApiTokenController extends Controller
{
    public function __construct(
        private readonly ApiTokenServiceInterface $apiTokenService,
    ) {}

    /**
     * Display a listing of the API tokens.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ApiToken::class);

        $tokens = ApiToken::where('tenant_id', $request->user()->tenant_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('api-tokens.index', [
            'tokens' => $tokens,
        ]);
    }

    /**
     * Show the form for creating a new API token.
     */
    public function create(): View
    {
        Gate::authorize('create', ApiToken::class);

        return view('api-tokens.create');
    }

    /**
     * Store a newly created API token in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', ApiToken::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $result = $this->apiTokenService->generate(
            $request->user()->tenant,
            $validated['name']
        );

        return redirect()
            ->route('api-tokens.show', $result['apiToken'])
            ->with('token', $result['token'])
            ->with('success', 'API token created successfully. Make sure to copy it now - you won\'t be able to see it again!');
    }

    /**
     * Display the specified API token.
     */
    public function show(Request $request, ApiToken $apiToken): View
    {
        Gate::authorize('view', $apiToken);

        // Get usage statistics - count messages sent via API for this tenant
        // Note: We track by source='api' since api_token_id is not stored in message_logs
        $totalRequests = $apiToken->tenant->messageLogs()
            ->where('source', 'api')
            ->count();

        return view('api-tokens.show', [
            'apiToken' => $apiToken,
            'totalRequests' => $totalRequests,
            'plainToken' => session('token'), // Only available immediately after creation
        ]);
    }

    /**
     * Revoke the specified API token.
     */
    public function revoke(Request $request, ApiToken $apiToken): RedirectResponse
    {
        Gate::authorize('revoke', $apiToken);

        $this->apiTokenService->revoke($apiToken);

        return redirect()
            ->route('api-tokens.index')
            ->with('success', 'API token revoked successfully.');
    }

    /**
     * Remove the specified API token from storage.
     */
    public function destroy(Request $request, ApiToken $apiToken): RedirectResponse
    {
        Gate::authorize('delete', $apiToken);

        $apiToken->delete();

        return redirect()
            ->route('api-tokens.index')
            ->with('success', 'API token deleted successfully.');
    }
}
