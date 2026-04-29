@extends('layouts.app')

@section('page-title', 'Detail Log')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="sm:flex sm:items-center sm:justify-between">
            <div>
                <nav class="flex" aria-label="Breadcrumb">
                    <ol role="list" class="flex items-center space-x-2">
                        <li>
                            <a href="{{ route('admin.logs.index') }}" class="text-sm text-gray-500 hover:text-gray-700">System
                                Logs</a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="size-5 shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span class="ms-2 text-sm font-medium text-gray-900">Log #{{ $log->id }}</span>
                            </div>
                        </li>
                    </ol>
                </nav>
                <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ $log->type }}</h1>
            </div>
        </div>

        <!-- Log Details -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Log Info -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 lg:col-span-2">
                <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
                    <h3 class="text-base font-semibold text-gray-900">Informasi Log</h3>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Type</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $log->type }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Severity</dt>
                            <dd class="mt-1">
                                @include('admin.logs._severity-badge', ['severity' => $log->severity])
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Tenant</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if ($log->tenant)
                                    <a href="{{ route('admin.tenants.show', $log->tenant) }}"
                                        class="text-green-600 hover:text-green-700">
                                        {{ $log->tenant->name }}
                                    </a>
                                @else
                                    <span class="text-gray-400">Sistem (global)</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">User</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $log->user?->name ?? '-' }}
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Message</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $log->message }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Timestamp</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $log->created_at->format('d M Y H:i:s') }}
                                <span class="text-gray-500">({{ $log->created_at->diffForHumans() }})</span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Severity Card -->
            <div class="space-y-6">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
                        <h3 class="text-base font-semibold text-gray-900">Severity</h3>
                    </div>
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center gap-3">
                            @if ($log->severity === 'critical')
                                <div class="flex size-10 items-center justify-center rounded-full bg-red-100">
                                    <svg class="size-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-red-700">Critical</p>
                                    <p class="text-xs text-gray-500">Memerlukan tindakan segera</p>
                                </div>
                            @elseif ($log->severity === 'error')
                                <div class="flex size-10 items-center justify-center rounded-full bg-orange-100">
                                    <svg class="size-6 text-orange-600" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-orange-700">Error</p>
                                    <p class="text-xs text-gray-500">Terjadi kesalahan</p>
                                </div>
                            @elseif ($log->severity === 'warning')
                                <div class="flex size-10 items-center justify-center rounded-full bg-yellow-100">
                                    <svg class="size-6 text-yellow-600" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-yellow-700">Warning</p>
                                    <p class="text-xs text-gray-500">Perlu perhatian</p>
                                </div>
                            @else
                                <div class="flex size-10 items-center justify-center rounded-full bg-blue-100">
                                    <svg class="size-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-blue-700">Info</p>
                                    <p class="text-xs text-gray-500">Informasi umum</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Context Information -->
        @if ($log->context)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
                    <h3 class="text-base font-semibold text-gray-900">Context Information</h3>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <div class="rounded-md bg-gray-50 p-4">
                        <pre class="whitespace-pre-wrap break-words text-sm text-gray-700">{{ json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </div>
            </div>
        @endif

        <!-- Related Logs -->
        @if ($relatedLogs->isNotEmpty())
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
                    <h3 class="text-base font-semibold text-gray-900">Related Logs</h3>
                    <p class="mt-2 text-sm text-gray-600">Log dengan tipe yang sama dalam rentang waktu ±1 jam.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="py-3.5 pe-3 ps-4 text-left text-sm font-semibold text-gray-900 sm:ps-6">
                                    Timestamp</th>
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
                            @foreach ($relatedLogs as $relatedLog)
                                <tr>
                                    <td class="whitespace-nowrap py-4 pe-3 ps-4 text-sm text-gray-500 sm:ps-6">
                                        {{ $relatedLog->created_at->format('d M Y H:i:s') }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        @include('admin.logs._severity-badge', [
                                            'severity' => $relatedLog->severity,
                                        ])
                                    </td>
                                    <td class="max-w-xs truncate px-3 py-4 text-sm text-gray-500">
                                        {{ $relatedLog->message }}
                                    </td>
                                    <td
                                        class="relative whitespace-nowrap py-4 pe-4 ps-3 text-right text-sm font-medium sm:pe-6">
                                        <a href="{{ route('admin.logs.show', $relatedLog) }}"
                                            class="text-green-600 hover:text-green-700">Lihat</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection
