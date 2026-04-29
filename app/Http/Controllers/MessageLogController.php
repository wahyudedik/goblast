<?php

namespace App\Http\Controllers;

use App\Jobs\SendMessageJob;
use App\Models\MessageLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

class MessageLogController extends Controller
{
    /**
     * Display a listing of message logs.
     */
    public function index(Request $request)
    {
        $tenant = Auth::user()->tenant;

        $query = $tenant->messageLogs()
            ->with(['device', 'broadcast', 'reminder', 'template']);

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by device
        if ($request->filled('device_id')) {
            $query->where('device_id', $request->device_id);
        }

        // Filter by recipient
        if ($request->filled('recipient')) {
            $query->where('recipient', 'like', '%'.$request->recipient.'%');
        }

        // Filter by source
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        $messageLogs = $query->latest()->paginate(20);

        // Get devices for filter dropdown
        $devices = $tenant->devices()
            ->orderBy('name')
            ->get(['id', 'name']);

        // Get status counts for badges
        $statusCounts = $tenant->messageLogs()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return view('message-logs.index', [
            'messageLogs' => $messageLogs,
            'devices' => $devices,
            'statusCounts' => $statusCounts,
            'filters' => $request->only(['date_from', 'date_to', 'status', 'device_id', 'recipient', 'source']),
        ]);
    }

    /**
     * Display the specified message log.
     */
    public function show(MessageLog $messageLog)
    {
        Gate::authorize('view', $messageLog);

        $messageLog->load(['device', 'broadcast', 'reminder', 'template']);

        // Get retry history (other attempts for the same recipient in the same broadcast/reminder)
        $retryHistory = collect();

        if ($messageLog->broadcast_id) {
            $retryHistory = MessageLog::where('broadcast_id', $messageLog->broadcast_id)
                ->where('recipient', $messageLog->recipient)
                ->where('id', '!=', $messageLog->id)
                ->orderBy('created_at', 'desc')
                ->get();
        } elseif ($messageLog->reminder_id) {
            $retryHistory = MessageLog::where('reminder_id', $messageLog->reminder_id)
                ->where('recipient', $messageLog->recipient)
                ->where('id', '!=', $messageLog->id)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('message-logs.show', [
            'messageLog' => $messageLog,
            'retryHistory' => $retryHistory,
        ]);
    }

    /**
     * Retry a failed message.
     */
    public function retry(MessageLog $messageLog)
    {
        Gate::authorize('update', $messageLog);

        if ($messageLog->status !== 'failed') {
            return back()->with('error', 'Only failed messages can be retried.');
        }

        // Reset the message log
        $messageLog->update([
            'status' => 'pending',
            'error_message' => null,
            'attempts' => 0,
            'failed_at' => null,
        ]);

        // Re-dispatch the job
        SendMessageJob::dispatch($messageLog)
            ->delay(now()->addSeconds(rand(5, 10)));

        return redirect()
            ->route('message-logs.show', $messageLog)
            ->with('success', 'Message queued for retry.');
    }

    /**
     * Remove the specified message log.
     */
    public function destroy(MessageLog $messageLog)
    {
        Gate::authorize('delete', $messageLog);

        $messageLog->delete();

        return redirect()
            ->route('message-logs.index')
            ->with('success', 'Message log deleted successfully.');
    }

    /**
     * Export message logs to CSV.
     */
    public function export(Request $request)
    {
        $tenant = Auth::user()->tenant;

        $query = $tenant->messageLogs()
            ->with(['device', 'broadcast', 'reminder', 'template']);

        // Apply same filters as index
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('device_id')) {
            $query->where('device_id', $request->device_id);
        }

        if ($request->filled('recipient')) {
            $query->where('recipient', 'like', '%'.$request->recipient.'%');
        }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        $messageLogs = $query->latest()->get();

        // Generate CSV
        $filename = 'message-logs-'.now()->format('Y-m-d-His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($messageLogs) {
            $file = fopen('php://output', 'w');

            // CSV header
            fputcsv($file, [
                'ID',
                'Recipient',
                'Message',
                'Status',
                'Source',
                'Device',
                'Broadcast',
                'Reminder',
                'Template',
                'Attempts',
                'Error Message',
                'Sent At',
                'Failed At',
                'Created At',
            ]);

            // CSV rows
            foreach ($messageLogs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->recipient,
                    $log->message,
                    $log->status,
                    $log->source,
                    $log->device?->name ?? '-',
                    $log->broadcast?->name ?? '-',
                    $log->reminder?->name ?? '-',
                    $log->template?->name ?? '-',
                    $log->attempts,
                    $log->error_message ?? '-',
                    $log->sent_at?->format('Y-m-d H:i:s') ?? '-',
                    $log->failed_at?->format('Y-m-d H:i:s') ?? '-',
                    $log->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
