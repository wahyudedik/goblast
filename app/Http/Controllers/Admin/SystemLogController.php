<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemLog;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SystemLogController extends Controller
{
    /**
     * Display a listing of system logs.
     */
    public function index(Request $request): View
    {
        $query = SystemLog::query()->with(['tenant', 'user']);

        // Filter by tenant
        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->input('tenant_id'));
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        // Filter by severity
        if ($request->filled('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // Filter by keyword
        if ($request->filled('keyword')) {
            $query->where('message', 'like', '%'.$request->input('keyword').'%');
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        $logTypes = SystemLog::query()->distinct()->pluck('type')->sort()->values();
        $tenants = Tenant::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.logs.index', [
            'logs' => $logs,
            'logTypes' => $logTypes,
            'tenants' => $tenants,
            'filters' => $request->only(['tenant_id', 'type', 'severity', 'date_from', 'date_to', 'keyword']),
        ]);
    }

    /**
     * Display the specified system log.
     */
    public function show(SystemLog $systemLog): View
    {
        $systemLog->load(['tenant', 'user']);

        // Get related logs (same type and tenant, within 1 hour)
        $relatedLogs = SystemLog::query()
            ->where('id', '!=', $systemLog->id)
            ->where('type', $systemLog->type)
            ->when($systemLog->tenant_id, fn ($q) => $q->where('tenant_id', $systemLog->tenant_id))
            ->whereBetween('created_at', [
                $systemLog->created_at->subHour(),
                $systemLog->created_at->addHour(),
            ])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.logs.show', [
            'log' => $systemLog,
            'relatedLogs' => $relatedLogs,
        ]);
    }

    /**
     * Export system logs to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = SystemLog::query()->with(['tenant', 'user']);

        // Apply same filters as index
        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->input('tenant_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('keyword')) {
            $query->where('message', 'like', '%'.$request->input('keyword').'%');
        }

        $logs = $query->orderBy('created_at', 'desc')->get();

        $filename = 'system-logs-'.now()->format('Y-m-d-His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');

            // CSV header
            fputcsv($file, [
                'ID',
                'Timestamp',
                'Tenant',
                'User',
                'Type',
                'Severity',
                'Message',
                'Context',
            ]);

            // CSV rows
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->tenant?->name ?? 'Sistem (global)',
                    $log->user?->name ?? '-',
                    $log->type,
                    $log->severity,
                    $log->message,
                    $log->context ? json_encode($log->context, JSON_UNESCAPED_UNICODE) : '-',
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
