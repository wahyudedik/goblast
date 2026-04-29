<?php

namespace App\Http\Controllers;

use App\Jobs\SendMessageJob;
use App\Models\Broadcast;
use App\Models\Device;
use App\Models\Template;
use App\Services\Contracts\BroadcastServiceInterface;
use App\Services\Contracts\QuotaServiceInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class BroadcastController extends Controller
{
    public function __construct(
        private readonly BroadcastServiceInterface $broadcastService,
        private readonly QuotaServiceInterface $quotaService,
    ) {}

    public function index(Request $request)
    {
        $tenant = Auth::user()->tenant;

        $query = $tenant->broadcasts()
            ->with(['device', 'template'])
            ->latest();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $broadcasts = $query->paginate(15);

        $statusCounts = $tenant->broadcasts()
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return view('broadcasts.index', [
            'broadcasts' => $broadcasts,
            'statusCounts' => $statusCounts,
            'currentStatus' => $request->status,
        ]);
    }

    public function create()
    {
        $tenant = Auth::user()->tenant;

        $devices = $tenant->devices()
            ->where('status', 'connected')
            ->get();

        $templates = $tenant->templates()
            ->orderBy('name')
            ->get();

        $remainingQuota = $this->quotaService->getRemainingQuota($tenant);
        $isUnlimited = $this->quotaService->isUnlimited($tenant);

        $contactGroups = $tenant->contacts()
            ->whereNotNull('group')
            ->distinct()
            ->pluck('group');

        return view('broadcasts.create', [
            'devices' => $devices,
            'templates' => $templates,
            'remainingQuota' => $remainingQuota,
            'isUnlimited' => $isUnlimited,
            'contactGroups' => $contactGroups,
        ]);
    }

    public function store(Request $request)
    {
        $tenant = Auth::user()->tenant;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'device_id' => ['required', 'exists:devices,id'],
            'template_id' => ['nullable', 'exists:templates,id'],
            'message' => ['required_without:template_id', 'nullable', 'string', 'max:4096'],
            'scheduled_at' => ['nullable', 'date'],
            'source_type' => ['required', Rule::in(['csv', 'database', 'contacts'])],
            'csv_file' => ['required_if:source_type,csv', 'file', 'mimes:csv,txt', 'max:5120'],
            'recipients' => ['required_if:source_type,database', 'array'],
            'recipients.*' => ['string', 'regex:/^62[0-9]{9,13}$/'],
            'contact_recipients' => ['required_if:source_type,contacts', 'nullable', 'string'],
        ]);

        // Verify device belongs to tenant
        $device = Device::where('id', $validated['device_id'])
            ->where('tenant_id', $tenant->id)
            ->where('status', 'connected')
            ->firstOrFail();

        // Verify template belongs to tenant if provided
        $template = null;
        if (isset($validated['template_id'])) {
            $template = Template::where('id', $validated['template_id'])
                ->where('tenant_id', $tenant->id)
                ->firstOrFail();
        }

        try {
            if ($validated['source_type'] === 'csv') {
                $broadcast = $this->broadcastService->createFromCsv(
                    $tenant,
                    $request->file('csv_file'),
                    $device,
                    $template
                );
            } elseif ($validated['source_type'] === 'contacts') {
                // Parse contact recipients (comma-separated phone numbers from hidden input)
                $contactRecipients = array_filter(
                    array_map('trim', explode(',', $validated['contact_recipients'] ?? ''))
                );
                $broadcast = $this->broadcastService->createFromRecipients(
                    $tenant,
                    $contactRecipients,
                    $device,
                    $template
                );
            } else {
                $broadcast = $this->broadcastService->createFromRecipients(
                    $tenant,
                    $validated['recipients'],
                    $device,
                    $template
                );
            }

            $updateData = ['name' => $validated['name']];

            // Store manual message if no template
            if (! $template && ! empty($validated['message'])) {
                $updateData['message'] = $validated['message'];
            }

            // Store schedule — input is in user's local timezone (Asia/Jakarta)
            if (! empty($validated['scheduled_at'])) {
                $scheduledAt = Carbon::parse($validated['scheduled_at'], config('app.timezone'));
                $updateData['scheduled_at'] = $scheduledAt;
            }

            $broadcast->update($updateData);

            return redirect()
                ->route('broadcasts.show', $broadcast)
                ->with('success', 'Broadcast berhasil dibuat.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal membuat broadcast: '.$e->getMessage());
        }
    }

    public function show(Broadcast $broadcast)
    {
        Gate::authorize('view', $broadcast);

        $broadcast->load(['device', 'template']);

        $progress = $this->broadcastService->getProgress($broadcast);

        // Get message logs for this broadcast
        $messageLogs = $broadcast->messageLogs()
            ->with('device')
            ->latest()
            ->paginate(20);

        return view('broadcasts.show', [
            'broadcast' => $broadcast,
            'progress' => $progress,
            'messageLogs' => $messageLogs,
        ]);
    }

    public function dispatch(Broadcast $broadcast)
    {
        Gate::authorize('update', $broadcast);

        if (! in_array($broadcast->status, ['draft', 'failed'])) {
            return back()->with('error', 'Only draft or failed broadcasts can be dispatched.');
        }

        try {
            $this->broadcastService->dispatch($broadcast);

            return redirect()
                ->route('broadcasts.show', $broadcast)
                ->with('success', 'Broadcast dispatched successfully. Messages are being sent.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to dispatch broadcast: '.$e->getMessage());
        }
    }

    public function cancel(Broadcast $broadcast)
    {
        Gate::authorize('update', $broadcast);

        if (! in_array($broadcast->status, ['queued', 'running'])) {
            return back()->with('error', 'Only queued or running broadcasts can be cancelled.');
        }

        try {
            $this->broadcastService->cancel($broadcast);

            return redirect()
                ->route('broadcasts.show', $broadcast)
                ->with('success', 'Broadcast cancelled successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to cancel broadcast: '.$e->getMessage());
        }
    }

    public function retryFailed(Broadcast $broadcast)
    {
        Gate::authorize('update', $broadcast);

        try {
            // Get all failed message logs
            $failedLogs = $broadcast->messageLogs()
                ->where('status', 'failed')
                ->get();

            if ($failedLogs->isEmpty()) {
                return back()->with('info', 'No failed messages to retry.');
            }

            // Reset failed messages to pending and re-dispatch
            foreach ($failedLogs as $log) {
                $log->update([
                    'status' => 'pending',
                    'error_message' => null,
                    'attempts' => 0,
                ]);

                // Re-dispatch the job
                SendMessageJob::dispatch($log)
                    ->delay(now()->addSeconds(rand(5, 10)));
            }

            $broadcast->increment('pending_count', $failedLogs->count());
            $broadcast->decrement('failed_count', $failedLogs->count());

            return redirect()
                ->route('broadcasts.show', $broadcast)
                ->with('success', "Retrying {$failedLogs->count()} failed messages.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to retry messages: '.$e->getMessage());
        }
    }

    public function progress(Broadcast $broadcast)
    {
        Gate::authorize('view', $broadcast);

        $progress = $this->broadcastService->getProgress($broadcast);

        return response()->json([
            'status' => $broadcast->status,
            'total' => $progress->total,
            'sent' => $progress->sent,
            'failed' => $progress->failed,
            'pending' => $progress->pending,
            'percentage' => $progress->percentage,
        ]);
    }
}
