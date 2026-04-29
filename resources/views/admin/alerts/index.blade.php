@extends('layouts.app')

@section('page-title', 'Kelola Alert')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="sm:flex sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Kelola Alert</h1>
                <p class="mt-2 text-sm text-gray-700">Daftar semua alert sistem yang tercatat.</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-4 sm:p-6">
                <form method="GET" action="{{ route('admin.alerts.index') }}"
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6">
                    <div>
                        <label for="status" class="block text-sm font-semibold text-gray-900 mb-2">Status</label>
                        <select id="status" name="status"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors">
                            <option value="">Semua Status</option>
                            <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active
                            </option>
                            <option value="resolved" {{ ($filters['status'] ?? '') === 'resolved' ? 'selected' : '' }}>
                                Resolved</option>
                        </select>
                    </div>

                    <div>
                        <label for="severity" class="block text-sm font-semibold text-gray-900 mb-2">Severity</label>
                        <select id="severity" name="severity"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors">
                            <option value="">Semua Severity</option>
                            <option value="warning" {{ ($filters['severity'] ?? '') === 'warning' ? 'selected' : '' }}>
                                Warning</option>
                            <option value="error" {{ ($filters['severity'] ?? '') === 'error' ? 'selected' : '' }}>Error
                            </option>
                            <option value="critical" {{ ($filters['severity'] ?? '') === 'critical' ? 'selected' : '' }}>
                                Critical</option>
                        </select>
                    </div>

                    <div>
                        <label for="type" class="block text-sm font-semibold text-gray-900 mb-2">Type</label>
                        <select id="type" name="type"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors">
                            <option value="">Semua Type</option>
                            @foreach ($alertTypes as $alertType)
                                <option value="{{ $alertType }}"
                                    {{ ($filters['type'] ?? '') === $alertType ? 'selected' : '' }}>
                                    {{ $alertType }}
                                </option>
                            @endforeach
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
                            <a href="{{ route('admin.alerts.index') }}"
                                class="inline-flex items-center rounded-md bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Alerts Table -->
        @if ($alerts->isEmpty())
            <div class="rounded-lg border border-dashed border-gray-300 bg-white p-12 text-center">
                <svg class="mx-auto size-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">Tidak ada alert</h3>
                <p class="mt-1 text-sm text-gray-500">Belum ada alert sistem yang tercatat.</p>
            </div>
        @else
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="py-3.5 pe-3 ps-4 text-left text-sm font-semibold text-gray-900 sm:ps-6">Type
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                    Severity</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                    Message</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                    Dibuat</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                    Status</th>
                                <th scope="col" class="relative py-3.5 pe-4 ps-3 sm:pe-6">
                                    <span class="sr-only">Aksi</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($alerts as $alert)
                                <tr>
                                    <td class="whitespace-nowrap py-4 pe-3 ps-4 text-sm font-medium text-gray-900 sm:ps-6">
                                        <a href="{{ route('admin.alerts.show', $alert) }}" class="hover:text-green-600">
                                            {{ $alert->type }}
                                        </a>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        @include('admin.alerts._severity-badge', [
                                            'severity' => $alert->severity,
                                        ])
                                    </td>
                                    <td class="max-w-xs truncate px-3 py-4 text-sm text-gray-500">
                                        {{ $alert->message }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $alert->created_at->format('d M Y H:i') }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        @include('admin.alerts._status-badge', [
                                            'status' => $alert->status,
                                        ])
                                    </td>
                                    <td
                                        class="relative whitespace-nowrap py-4 pe-4 ps-3 text-right text-sm font-medium sm:pe-6">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.alerts.show', $alert) }}"
                                                class="text-green-600 hover:text-green-700">Lihat</a>

                                            @if ($alert->status === 'active')
                                                <form action="{{ route('admin.alerts.resolve', $alert) }}" method="POST"
                                                    class="inline"
                                                    data-confirm="Apakah Anda yakin ingin me-resolve alert ini?"
                                                    data-confirm-title="Resolve Alert" data-confirm-button="Ya, Resolve"
                                                    data-confirm-type="warning">
                                                    @csrf
                                                    <button type="submit"
                                                        class="text-yellow-600 hover:text-yellow-700">Resolve</button>
                                                </form>
                                            @endif

                                            <form action="{{ route('admin.alerts.destroy', $alert) }}" method="POST"
                                                class="inline"
                                                data-confirm="Apakah Anda yakin ingin menghapus alert ini? Aksi ini tidak dapat dibatalkan."
                                                data-confirm-title="Hapus Alert" data-confirm-button="Ya, Hapus"
                                                data-confirm-type="danger">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="text-red-600 hover:text-red-700">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if ($alerts->hasPages())
                    <div class="border-t border-gray-200 px-4 py-3 sm:px-6">
                        {{ $alerts->links() }}
                    </div>
                @endif
            </div>
        @endif
    </div>
@endsection
