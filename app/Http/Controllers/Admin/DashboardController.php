<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Device;
use App\Models\GatewayInstance;
use App\Models\Invoice;
use App\Models\MessageLog;
use App\Models\Tenant;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the superadmin monitoring dashboard.
     */
    public function __invoke(): View
    {
        $today = now()->startOfDay();
        $monthStart = now()->startOfMonth();
        $thirtyDaysAgo = now()->subDays(29)->startOfDay();

        // Real-time statistics
        $stats = [
            'messages_today' => MessageLog::where('status', 'sent')
                ->where('sent_at', '>=', $today)
                ->count(),
            'active_tenants' => Tenant::where('status', 'active')->count(),
            'connected_devices' => Device::where('status', 'connected')->count(),
            'revenue_this_month' => Invoice::where('paid_at', '>=', $monthStart)->sum('amount'),
        ];

        // Message sent trend (30 days)
        $messageTrend = MessageLog::where('status', 'sent')
            ->where('sent_at', '>=', $thirtyDaysAgo)
            ->select(DB::raw('DATE(sent_at) as date'), DB::raw('COUNT(*) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        // Revenue trend (30 days)
        $revenueTrend = Invoice::where('paid_at', '>=', $thirtyDaysAgo)
            ->select(DB::raw('DATE(paid_at) as date'), DB::raw('SUM(amount) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        // Fill in missing dates with zero values
        $period = CarbonPeriod::create($thirtyDaysAgo, now());
        $messageTrendFilled = [];
        $revenueTrendFilled = [];

        foreach ($period as $date) {
            $key = $date->format('Y-m-d');
            $messageTrendFilled[$key] = (int) ($messageTrend[$key] ?? 0);
            $revenueTrendFilled[$key] = (float) ($revenueTrend[$key] ?? 0);
        }

        // Top 10 tenants by message usage (this month)
        $topTenants = Tenant::select('tenants.id', 'tenants.name')
            ->join('message_logs', 'tenants.id', '=', 'message_logs.tenant_id')
            ->where('message_logs.status', 'sent')
            ->where('message_logs.sent_at', '>=', $monthStart)
            ->groupBy('tenants.id', 'tenants.name')
            ->orderByDesc(DB::raw('COUNT(message_logs.id)'))
            ->limit(10)
            ->selectRaw('COUNT(message_logs.id) as message_count')
            ->get();

        // Active alerts
        $activeAlerts = Alert::where('status', 'active')
            ->with('tenant')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Gateway instances status
        $gateways = GatewayInstance::orderBy('name')->get();

        return view('admin.dashboard', [
            'stats' => $stats,
            'messageTrend' => $messageTrendFilled,
            'revenueTrend' => $revenueTrendFilled,
            'topTenants' => $topTenants,
            'activeAlerts' => $activeAlerts,
            'gateways' => $gateways,
        ]);
    }
}
