@extends('layouts.app')

@section('page-title', 'System Logs')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="sm:flex sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">System Logs</h1>
                <p class="mt-2 text-sm text-gray-700">Daftar semua log sistem yang tercatat.</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('admin.logs.export', request()->query()) }}"
                    class="inline-flex items-center px-4 py-2.5 border-2 border-gray-300 rounded-lg text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-all">
                    <svg class="-ms-0.5 me-1.5 size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Export CSV
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-4 sm:p-6">
                <form method="GET" action="{{ route('admin.logs.index') }}"
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-7">
                    <div>
                        <label for="tenant_id" class="block text-sm font-semibold text-gray-900 mb-2">Tenant</label>
                        <select id="tenant_id" name="tenant_id"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors">
                            <option value="">Semua Tenant</option>
                            @foreach ($tenants as $tenant)
                                <option value="{{ $tenant->id }}"
                                    {{ ($filters['tenant_id'] ?? '') == $tenant->id ? 'selected' : '' }}>
                                    {{ $tenant->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="type" class="block text-sm font-semibold text-gray-900 mb-2">Type</label>
                        <select id="type" name="type"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors">
                            <option value="">Semua Type</option>
                            @foreach ($logTypes as $logType)
                                <option value="{{ $logType }}"
                                    {{ ($filters['type'] ?? '') === $logType ? 'selected' : '' }}>
                                    {{ $logType }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="severity" class="block text-sm font-semibold text-gray-900 mb-2">Severity</label>
                        <select id="severity" name="severity"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors">
                            <option value="">Semua Severity</option>
                            <option value="info" {{ ($filters['severity'] ?? '') === 'info' ? 'selected' : '' }}>Info
                            </option>
                            <option value="warning" {{ ($filters['severity'] ?? '') === 'warning' ? 'selected' : '' }}>
                                Warning</option>
                            <option value="error" {{ ($filters['severity'] ?? '') === 'error' ? 'selected' : '' }}>Error
                            </option>
                            <option value="critical" {{ ($filters['severity'] ?? '') === 'critical' ? 'selected' : '' }}>
                                Critical</option>
                        </select>
                    </div>

                    <div>
                        <label for="date_from" class="block text-sm font-semibold text-gray-900 mb-2">Dari Tanggal</label>
                        <input type="date" id="date_from" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors">
                    </div>

                    <div>
                        <label for="date_to" class="block text-sm font-semibold text-gray-900 mb-2">Sampai Tanggal</label>
                        <input type="date" id="date_to" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors">
                    </div>

                    <div>
                        <label for="keyword" class="block text-sm font-semibold text-gray-900 mb-2">Keyword</label>
                        <input type="text" id="keyword" name="keyword" value="{{ $filters['keyword'] ?? '' }}"
                            placeholder="Cari pesan..."
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors">
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit"
                            class="inline-flex items-center px-6 py-2.5 bg-green-600 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-150 shadow-sm">
                            <svg class="-ms-0.5 me-1.5 size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                            </svg>
                            Filter
                        </button>
                        @if (collect($filters)->filter()->isNotEmpty())
                            <a href="{{ route('admin.logs.index') }}"
                                class="inline-flex items-center rounded-md bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Logs Table -->
        @if ($logs->isEmpty())
            <div class="rounded-lg border border-dashed border-gray-300 bg-white p-12 text-center">
                <svg class="mx-auto size-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">Tidak ada log</h3>
                <p class="mt-1 text-sm text-gray-500">Belum ada system log yang tercatat.</p>
            </div>
        @else
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="py-3.5 pe-3 ps-4 text-left text-sm font-semibold text-gray-900 sm:ps-6">
                                    Timestamp</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                    Tenant</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                    Type</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                    Severity</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                    Message</th>
                                <th scope="col" class="relative py-3.5 pe-4 ps-3 sm:pe-6">
                                    <span class="sr-only">Aksi</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($logs as $log)
                                <tr>
                                    <td class="whitespace-nowrap py-4 pe-3 ps-4 text-sm text-gray-500 sm:ps-6">
                                        {{ $log->created_at->format('d M Y H:i:s') }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        @if ($log->tenant)
                                            <a href="{{ route('admin.tenants.show', $log->tenant) }}"
                                                class="text-green-600 hover:text-green-700">
                                                {{ $log->tenant->name }}
                                            </a>
                                        @else
                                            <span class="text-gray-400">Sistem (global)</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900">
                                        {{ $log->type }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        @include('admin.logs._severity-badge', [
                                            'severity' => $log->severity,
                                        ])
                                    </td>
                                    <td class="max-w-xs truncate px-3 py-4 text-sm text-gray-500">
                                        {{ $log->message }}
                                    </td>
                                    <td
                                        class="relative whitespace-nowrap py-4 pe-4 ps-3 text-right text-sm font-medium sm:pe-6">
                                        <a href="{{ route('admin.logs.show', $log) }}"
                                            class="text-green-600 hover:text-green-700">Lihat</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if ($logs->hasPages())
                    <div class="border-t border-gray-200 px-4 py-3 sm:px-6">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>
        @endif
    </div>
@endsection
