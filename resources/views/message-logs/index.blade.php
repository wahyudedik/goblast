@extends('layouts.app')

@section('page-title', 'Log Pesan')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="sm:flex sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Log Pesan</h1>
                <p class="mt-2 text-sm text-gray-700">
                    Lihat dan kelola semua log pengiriman pesan.
                </p>
            </div>
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('message-logs.export', $filters) }}"
                    class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    <svg class="-ms-0.5 me-1.5 size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Ekspor ke CSV
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-900/5">
            <form method="GET" action="{{ route('message-logs.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Date From -->
                    <div>
                        <label for="date_from" class="block text-sm font-medium text-gray-700">
                            Dari Tanggal
                        </label>
                        <input type="date" name="date_from" id="date_from" value="{{ $filters['date_from'] ?? '' }}"
                            class="mt-1 block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors text-sm">
                    </div>

                    <!-- Date To -->
                    <div>
                        <label for="date_to" class="block text-sm font-medium text-gray-700">
                            Sampai Tanggal
                        </label>
                        <input type="date" name="date_to" id="date_to" value="{{ $filters['date_to'] ?? '' }}"
                            class="mt-1 block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors text-sm">
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">
                            Status
                        </label>
                        <select name="status" id="status"
                            class="mt-1 block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors text-sm">
                            <option value="">Semua Status</option>
                            <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>
                                Tertunda
                            </option>
                            <option value="sent" {{ ($filters['status'] ?? '') === 'sent' ? 'selected' : '' }}>Terkirim
                            </option>
                            <option value="failed" {{ ($filters['status'] ?? '') === 'failed' ? 'selected' : '' }}>Gagal
                            </option>
                            <option value="cancelled" {{ ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>
                                Dibatalkan
                            </option>
                            <option value="retrying" {{ ($filters['status'] ?? '') === 'retrying' ? 'selected' : '' }}>
                                Mencoba Ulang
                            </option>
                        </select>
                    </div>

                    <!-- Device -->
                    <div>
                        <label for="device_id" class="block text-sm font-medium text-gray-700">
                            Device
                        </label>
                        <select name="device_id" id="device_id"
                            class="mt-1 block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors text-sm">
                            <option value="">Semua Device</option>
                            @foreach ($devices as $device)
                                <option value="{{ $device->id }}"
                                    {{ ($filters['device_id'] ?? '') == $device->id ? 'selected' : '' }}>
                                    {{ $device->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Recipient -->
                    <div>
                        <label for="recipient" class="block text-sm font-medium text-gray-700">
                            Penerima
                        </label>
                        <input type="text" name="recipient" id="recipient" value="{{ $filters['recipient'] ?? '' }}"
                            placeholder="Cari berdasarkan nomor telepon"
                            class="mt-1 block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors text-sm">
                    </div>

                    <!-- Source -->
                    <div>
                        <label for="source" class="block text-sm font-medium text-gray-700">
                            Sumber
                        </label>
                        <select name="source" id="source"
                            class="mt-1 block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors text-sm">
                            <option value="">Semua Sumber</option>
                            <option value="broadcast" {{ ($filters['source'] ?? '') === 'broadcast' ? 'selected' : '' }}>
                                Broadcast
                            </option>
                            <option value="trigger" {{ ($filters['source'] ?? '') === 'trigger' ? 'selected' : '' }}>
                                Trigger
                            </option>
                            <option value="reminder" {{ ($filters['source'] ?? '') === 'reminder' ? 'selected' : '' }}>
                                Reminder
                            </option>
                            <option value="api" {{ ($filters['source'] ?? '') === 'api' ? 'selected' : '' }}>API
                            </option>
                            <option value="auto_reply" {{ ($filters['source'] ?? '') === 'auto_reply' ? 'selected' : '' }}>
                                Auto Reply
                            </option>
                        </select>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit"
                        class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600">
                        Terapkan Filter
                    </button>
                    <a href="{{ route('message-logs.index') }}"
                        class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        Hapus Filter
                    </a>
                </div>
            </form>
        </div>

        <!-- Status Badges -->
        <div class="flex flex-wrap gap-2">
            @foreach (['pending', 'sent', 'failed', 'cancelled', 'retrying'] as $status)
                @if (isset($statusCounts[$status]))
                    <div
                        class="inline-flex items-center rounded-md px-3 py-2 text-sm font-medium {{ ($filters['status'] ?? '') === $status ? 'bg-green-100 text-green-700' : 'bg-white text-gray-700 ring-1 ring-inset ring-gray-300' }}">
                        {{ ucfirst($status) }}
                        <span
                            class="ms-2 rounded-full {{ ($filters['status'] ?? '') === $status ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700' }} px-2 py-0.5 text-xs">
                            {{ $statusCounts[$status] }}
                        </span>
                    </div>
                @endif
            @endforeach
        </div>

        <!-- Message Logs Table -->
        @if ($messageLogs->isEmpty())
            <div class="rounded-lg border border-dashed border-gray-300 bg-white p-12 text-center">
                <svg class="mx-auto size-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">Tidak ada log pesan</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Tidak ada pesan yang sesuai dengan filter Anda.
                </p>
            </div>
        @else
            <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="py-3.5 pe-3 ps-4 text-left text-sm font-semibold text-gray-900 sm:ps-6">
                                Penerima
                            </th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                Pesan
                            </th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                Status
                            </th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                Device
                            </th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                Dikirim Pada
                            </th>
                            <th scope="col" class="relative py-3.5 pe-4 ps-3 sm:pe-6">
                                <span class="sr-only">Aksi</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach ($messageLogs as $log)
                            <tr>
                                <td class="whitespace-nowrap py-4 pe-3 ps-4 text-sm font-medium text-gray-900 sm:ps-6">
                                    {{ $log->recipient }}
                                </td>
                                <td class="max-w-xs truncate px-3 py-4 text-sm text-gray-500">
                                    {{ Str::limit($log->message, 50) }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm">
                                    @if ($log->status === 'sent')
                                        <span
                                            class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                            Terkirim
                                        </span>
                                    @elseif ($log->status === 'pending')
                                        <span
                                            class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-700 ring-1 ring-inset ring-yellow-600/20">
                                            Tertunda
                                        </span>
                                    @elseif ($log->status === 'retrying')
                                        <span
                                            class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-600/20">
                                            Mencoba Ulang
                                        </span>
                                    @elseif ($log->status === 'cancelled')
                                        <span
                                            class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                            Dibatalkan
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">
                                            Gagal
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    {{ $log->device?->name ?? '-' }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    @if ($log->sent_at)
                                        <time datetime="{{ $log->sent_at->toIso8601String() }}"
                                            title="{{ $log->sent_at->format('Y-m-d H:i:s') }}">
                                            {{ $log->sent_at->diffForHumans() }}
                                        </time>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td
                                    class="relative whitespace-nowrap py-4 pe-4 ps-3 text-right text-sm font-medium sm:pe-6">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('message-logs.show', $log) }}"
                                            class="text-green-600 hover:text-green-700">
                                            Lihat
                                        </a>

                                        @if ($log->status === 'failed')
                                            <form action="{{ route('message-logs.retry', $log) }}" method="POST"
                                                class="inline">
                                                @csrf
                                                <button type="submit" class="text-blue-600 hover:text-blue-900">
                                                    Coba Ulang
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if ($messageLogs->hasPages())
                <div class="mt-6">
                    {{ $messageLogs->appends($filters)->links() }}
                </div>
            @endif
        @endif
    </div>
@endsection
