@extends('layouts.app')

@section('title', 'Device Details')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <a href="{{ route('devices.index') }}"
                class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700">
                <svg class="me-1 size-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z"
                        clip-rule="evenodd" />
                </svg>
                Back to Devices
            </a>
            <div class="mt-2 flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">{{ $device->name }}</h1>
                <div class="flex gap-2">
                    <a href="{{ route('devices.edit', $device) }}"
                        class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        <svg class="me-1.5 size-4" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" />
                        </svg>
                        Rename
                    </a>
                    @if (in_array($device->status, ['connected', 'pending']))
                        <form action="{{ route('devices.disconnect', $device) }}" method="POST" class="inline"
                            data-confirm="Apakah Anda yakin ingin memutuskan device ini?"
                            data-confirm-title="Putuskan Device" data-confirm-button="Ya, Putuskan"
                            data-confirm-type="warning">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center rounded-md bg-yellow-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-yellow-500">
                                <svg class="me-1.5 size-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                                        clip-rule="evenodd" />
                                </svg>
                                Disconnect
                            </button>
                        </form>
                    @endif
                    <form action="{{ route('devices.destroy', $device) }}" method="POST" class="inline"
                        data-confirm="Apakah Anda yakin ingin menghapus device ini? Aksi ini tidak dapat dibatalkan."
                        data-confirm-title="Hapus Device" data-confirm-button="Ya, Hapus" data-confirm-type="danger">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                            <svg class="me-1.5 size-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z"
                                    clip-rule="evenodd" />
                            </svg>
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Device Details -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Basic Information -->
            <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
                <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                    <h3 class="text-base font-semibold text-gray-900">Device Information</h3>
                </div>
                <div class="px-6 py-4">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Name</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $device->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Phone Number</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $device->phone_number ?? 'Not connected' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="mt-1">
                                @if ($device->status === 'connected')
                                    <span
                                        class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                        <svg class="-ms-0.5 me-1.5 size-2 fill-green-500" viewBox="0 0 6 6">
                                            <circle cx="3" cy="3" r="3" />
                                        </svg>
                                        Connected
                                    </span>
                                @elseif ($device->status === 'pending')
                                    <span
                                        class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-700 ring-1 ring-inset ring-yellow-600/20">
                                        <svg class="-ms-0.5 me-1.5 size-2 fill-yellow-500" viewBox="0 0 6 6">
                                            <circle cx="3" cy="3" r="3" />
                                        </svg>
                                        Pending
                                    </span>
                                @elseif ($device->status === 'disconnected')
                                    <span
                                        class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                        <svg class="-ms-0.5 me-1.5 size-2 fill-gray-400" viewBox="0 0 6 6">
                                            <circle cx="3" cy="3" r="3" />
                                        </svg>
                                        Disconnected
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">
                                        <svg class="-ms-0.5 me-1.5 size-2 fill-red-500" viewBox="0 0 6 6">
                                            <circle cx="3" cy="3" r="3" />
                                        </svg>
                                        Error
                                    </span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Last Seen</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if ($device->last_seen_at)
                                    <time datetime="{{ $device->last_seen_at->toIso8601String() }}"
                                        title="{{ $device->last_seen_at->format('Y-m-d H:i:s') }}">
                                        {{ $device->last_seen_at->diffForHumans() }}
                                    </time>
                                @else
                                    Never
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Created</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <time datetime="{{ $device->created_at->toIso8601String() }}"
                                    title="{{ $device->created_at->format('Y-m-d H:i:s') }}">
                                    {{ $device->created_at->format('M d, Y') }}
                                </time>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Usage Statistics -->
            <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
                <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                    <h3 class="text-base font-semibold text-gray-900">Usage Statistics</h3>
                </div>
                <div class="px-6 py-4">
                    <dl class="grid grid-cols-1 gap-4">
                        <div class="rounded-lg bg-gray-50 p-4">
                            <dt class="text-sm font-medium text-gray-500">Total Messages</dt>
                            <dd class="mt-1 text-3xl font-semibold text-gray-900">
                                {{ number_format($totalMessages) }}</dd>
                        </div>
                        <div class="rounded-lg bg-green-50 p-4">
                            <dt class="text-sm font-medium text-green-700">Sent Successfully</dt>
                            <dd class="mt-1 text-3xl font-semibold text-green-900">
                                {{ number_format($sentMessages) }}</dd>
                            @if ($totalMessages > 0)
                                <dd class="mt-1 text-xs text-green-600">
                                    {{ number_format(($sentMessages / $totalMessages) * 100, 1) }}% success rate
                                </dd>
                            @endif
                        </div>
                        <div class="rounded-lg bg-red-50 p-4">
                            <dt class="text-sm font-medium text-red-700">Failed</dt>
                            <dd class="mt-1 text-3xl font-semibold text-red-900">
                                {{ number_format($failedMessages) }}</dd>
                            @if ($totalMessages > 0)
                                <dd class="mt-1 text-xs text-red-600">
                                    {{ number_format(($failedMessages / $totalMessages) * 100, 1) }}% failure rate
                                </dd>
                            @endif
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection
