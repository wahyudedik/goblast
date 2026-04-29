@extends('layouts.app')

@section('page-title', 'Admin Dashboard')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Monitoring Dashboard</h1>
            <p class="mt-2 text-sm text-gray-700">Ringkasan statistik platform secara real-time.</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Messages Sent Today -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 px-4 py-5 sm:p-6">
                <div class="flex items-center">
                    <div class="shrink-0">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-100">
                            <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="truncate text-sm font-medium text-gray-500">Pesan Terkirim Hari Ini</dt>
                            <dd class="text-2xl font-bold text-gray-900">{{ number_format($stats['messages_today']) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Active Tenants -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 px-4 py-5 sm:p-6">
                <div class="flex items-center">
                    <div class="shrink-0">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100">
                            <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="truncate text-sm font-medium text-gray-500">Tenant Aktif</dt>
                            <dd class="text-2xl font-bold text-gray-900">{{ number_format($stats['active_tenants']) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Connected Devices -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 px-4 py-5 sm:p-6">
                <div class="flex items-center">
                    <div class="shrink-0">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-100">
                            <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="truncate text-sm font-medium text-gray-500">Device Terhubung</dt>
                            <dd class="text-2xl font-bold text-gray-900">{{ number_format($stats['connected_devices']) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Revenue This Month -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 px-4 py-5 sm:p-6">
                <div class="flex items-center">
                    <div class="shrink-0">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-yellow-100">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="truncate text-sm font-medium text-gray-500">Revenue Bulan Ini</dt>
                            <dd class="text-2xl font-bold text-gray-900">Rp
                                {{ number_format($stats['revenue_this_month'], 0, ',', '.') }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Message Sent Trend (30 days) -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-4 sm:p-6">
                    <h3 class="text-base font-semibold text-gray-900">Tren Pesan Terkirim (30 Hari)</h3>
                    <div class="mt-4">
                        <canvas id="messageTrendChart" height="250"></canvas>
                    </div>
                </div>
            </div>

            <!-- Revenue Trend (30 days) -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-4 sm:p-6">
                    <h3 class="text-base font-semibold text-gray-900">Tren Revenue (30 Hari)</h3>
                    <div class="mt-4">
                        <canvas id="revenueTrendChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Tenants -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-4 sm:p-6">
                <h3 class="text-base font-semibold text-gray-900">Top 10 Tenant (Penggunaan Pesan Bulan Ini)</h3>
                <div class="mt-4">
                    @if ($topTenants->isEmpty())
                        <p class="text-sm text-gray-500">Belum ada data penggunaan pesan bulan ini.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-300">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col"
                                            class="py-3.5 pe-3 ps-4 text-left text-sm font-semibold text-gray-900 sm:ps-6">#
                                        </th>
                                        <th scope="col"
                                            class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Tenant</th>
                                        <th scope="col"
                                            class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">Pesan
                                            Terkirim</th>
                                        <th scope="col"
                                            class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Proporsi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @php
                                        $maxMessages = $topTenants->max('message_count') ?: 1;
                                    @endphp
                                    @foreach ($topTenants as $index => $tenant)
                                        <tr>
                                            <td class="whitespace-nowrap py-4 pe-3 ps-4 text-sm text-gray-500 sm:ps-6">
                                                {{ $index + 1 }}</td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900">
                                                <a href="{{ route('admin.tenants.show', $tenant->id) }}"
                                                    class="hover:text-green-600">
                                                    {{ $tenant->name }}
                                                </a>
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 text-right">
                                                {{ number_format($tenant->message_count) }}</td>
                                            <td class="px-3 py-4 text-sm text-gray-500">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-full max-w-xs rounded-full bg-gray-200">
                                                        <div class="h-2 rounded-full bg-green-500"
                                                            style="width: {{ ($tenant->message_count / $maxMessages) * 100 }}%">
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Alerts & Gateway Status Row -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Active Alerts -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-4 sm:p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900">Alert Aktif</h3>
                        @if ($activeAlerts->isNotEmpty())
                            <span
                                class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                                {{ $activeAlerts->count() }}
                            </span>
                        @endif
                    </div>
                    <div class="mt-4">
                        @if ($activeAlerts->isEmpty())
                            <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center">
                                <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-500">Tidak ada alert aktif. Semua sistem berjalan normal.
                                </p>
                            </div>
                        @else
                            <ul role="list" class="space-y-3">
                                @foreach ($activeAlerts as $alert)
                                    <li
                                        class="flex items-start gap-3 rounded-lg border p-3
                                        {{ $alert->severity === 'critical' ? 'border-red-200 bg-red-50' : ($alert->severity === 'error' ? 'border-orange-200 bg-orange-50' : 'border-yellow-200 bg-yellow-50') }}">
                                        @if ($alert->severity === 'critical')
                                            <svg class="h-5 w-5 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                            </svg>
                                        @elseif ($alert->severity === 'error')
                                            <svg class="h-5 w-5 shrink-0 text-orange-500" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                            </svg>
                                        @else
                                            <svg class="h-5 w-5 shrink-0 text-yellow-500" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                            </svg>
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                                    {{ $alert->severity === 'critical' ? 'bg-red-100 text-red-800' : ($alert->severity === 'error' ? 'bg-orange-100 text-orange-800' : 'bg-yellow-100 text-yellow-800') }}">
                                                    {{ ucfirst($alert->severity) }}
                                                </span>
                                                <span class="text-xs text-gray-500">{{ $alert->type }}</span>
                                            </div>
                                            <p class="mt-1 text-sm text-gray-700">{{ $alert->message }}</p>
                                            <div class="mt-1 flex items-center gap-2 text-xs text-gray-500">
                                                <span>{{ $alert->created_at->diffForHumans() }}</span>
                                                @if ($alert->tenant)
                                                    <span>&middot;</span>
                                                    <span>{{ $alert->tenant->name }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Gateway Status -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-4 sm:p-6">
                    <h3 class="text-base font-semibold text-gray-900">Status Gateway</h3>
                    <div class="mt-4">
                        @if ($gateways->isEmpty())
                            <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center">
                                <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.737 5.1a3.375 3.375 0 012.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 01.9 2.7m0 0a3 3 0 01-3 3m0 3h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008zm-3 6h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008z" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-500">Belum ada gateway instance yang dikonfigurasi.</p>
                            </div>
                        @else
                            <ul role="list" class="space-y-3">
                                @foreach ($gateways as $gateway)
                                    <li class="flex items-center justify-between rounded-lg border border-gray-200 p-4">
                                        <div class="flex items-center gap-3">
                                            <span class="relative flex h-3 w-3">
                                                @if ($gateway->status === 'active')
                                                    <span
                                                        class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-400 opacity-75"></span>
                                                    <span
                                                        class="relative inline-flex h-3 w-3 rounded-full bg-green-500"></span>
                                                @elseif ($gateway->status === 'error')
                                                    <span
                                                        class="relative inline-flex h-3 w-3 rounded-full bg-red-500"></span>
                                                @else
                                                    <span
                                                        class="relative inline-flex h-3 w-3 rounded-full bg-gray-400"></span>
                                                @endif
                                            </span>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ $gateway->name }}</p>
                                                <p class="text-xs text-gray-500">{{ $gateway->base_url }}</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <span
                                                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                                {{ $gateway->status === 'active' ? 'bg-green-100 text-green-800' : ($gateway->status === 'error' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">
                                                {{ ucfirst($gateway->status) }}
                                            </span>
                                            @if ($gateway->last_checked_at)
                                                <p class="mt-2 text-sm text-gray-600">Cek:
                                                    {{ $gateway->last_checked_at->diffForHumans() }}</p>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const messageTrendData = @json($messageTrend);
            const revenueTrendData = @json($revenueTrend);

            const labels = Object.keys(messageTrendData).map(function(date) {
                const d = new Date(date + 'T00:00:00');
                return d.toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'short'
                });
            });

            const chartDefaults = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            maxRotation: 45,
                            font: {
                                size: 11
                            }
                        },
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            font: {
                                size: 11
                            }
                        },
                    },
                },
            };

            // Message Trend Chart
            new Chart(document.getElementById('messageTrendChart'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Pesan Terkirim',
                        data: Object.values(messageTrendData),
                        backgroundColor: 'rgba(22, 163, 74, 0.7)',
                        borderColor: 'rgb(22, 163, 74)',
                        borderWidth: 1,
                        borderRadius: 4,
                    }],
                },
                options: chartDefaults,
            });

            // Revenue Trend Chart
            new Chart(document.getElementById('revenueTrendChart'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Revenue (Rp)',
                        data: Object.values(revenueTrendData),
                        borderColor: 'rgb(234, 179, 8)',
                        backgroundColor: 'rgba(234, 179, 8, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3,
                        pointRadius: 2,
                        pointHoverRadius: 5,
                    }],
                },
                options: {
                    ...chartDefaults,
                    scales: {
                        ...chartDefaults.scales,
                        y: {
                            ...chartDefaults.scales.y,
                            ticks: {
                                ...chartDefaults.scales.y.ticks,
                                callback: function(value) {
                                    return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                                },
                            },
                        },
                    },
                },
            });
        });
    </script>
@endpush
