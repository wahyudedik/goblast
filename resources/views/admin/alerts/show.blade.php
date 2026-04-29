@extends('layouts.app')

@section('page-title', 'Detail Alert')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="sm:flex sm:items-center sm:justify-between">
            <div>
                <nav class="flex" aria-label="Breadcrumb">
                    <ol role="list" class="flex items-center space-x-2">
                        <li>
                            <a href="{{ route('admin.alerts.index') }}"
                                class="text-sm text-gray-500 hover:text-gray-700">Kelola Alert</a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="size-5 shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span class="ms-2 text-sm font-medium text-gray-900">{{ $alert->type }}</span>
                            </div>
                        </li>
                    </ol>
                </nav>
                <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ $alert->type }}</h1>
            </div>
            <div class="mt-4 flex flex-wrap gap-2 sm:mt-0">
                @if ($alert->status === 'active')
                    <form action="{{ route('admin.alerts.resolve', $alert) }}" method="POST"
                        data-confirm="Apakah Anda yakin ingin me-resolve alert ini?" data-confirm-title="Resolve Alert"
                        data-confirm-button="Ya, Resolve" data-confirm-type="warning">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center rounded-md bg-yellow-50 px-3 py-2 text-sm font-semibold text-yellow-700 shadow-sm ring-1 ring-inset ring-yellow-600/20 hover:bg-yellow-100">
                            <svg class="-ms-0.5 me-1.5 size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Resolve
                        </button>
                    </form>
                @endif

                <form action="{{ route('admin.alerts.destroy', $alert) }}" method="POST"
                    data-confirm="Apakah Anda yakin ingin menghapus alert ini? Aksi ini tidak dapat dibatalkan."
                    data-confirm-title="Hapus Alert" data-confirm-button="Ya, Hapus" data-confirm-type="danger">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="inline-flex items-center rounded-md bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 shadow-sm ring-1 ring-inset ring-red-600/10 hover:bg-red-100">
                        <svg class="-ms-0.5 me-1.5 size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                        Hapus
                    </button>
                </form>
            </div>
        </div>

        <!-- Alert Details -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Alert Info -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 lg:col-span-2">
                <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
                    <h3 class="text-base font-semibold text-gray-900">Informasi Alert</h3>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Type</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $alert->type }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Severity</dt>
                            <dd class="mt-1">
                                @include('admin.alerts._severity-badge', ['severity' => $alert->severity])
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="mt-1">
                                @include('admin.alerts._status-badge', ['status' => $alert->status])
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Tenant</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if ($alert->tenant)
                                    <a href="{{ route('admin.tenants.show', $alert->tenant) }}"
                                        class="text-green-600 hover:text-green-700">
                                        {{ $alert->tenant->name }}
                                    </a>
                                @else
                                    <span class="text-gray-400">Sistem (global)</span>
                                @endif
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Message</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $alert->message }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Dibuat</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $alert->created_at->format('d M Y H:i:s') }}
                                <span class="text-gray-500">({{ $alert->created_at->diffForHumans() }})</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Diperbarui</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $alert->updated_at->format('d M Y H:i:s') }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Status Card -->
            <div class="space-y-6">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
                        <h3 class="text-base font-semibold text-gray-900">Status</h3>
                    </div>
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center gap-3">
                            @if ($alert->status === 'active')
                                <div class="flex size-10 items-center justify-center rounded-full bg-red-100">
                                    <svg class="size-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-red-700">Active</p>
                                    <p class="text-xs text-gray-500">Alert belum di-resolve</p>
                                </div>
                            @else
                                <div class="flex size-10 items-center justify-center rounded-full bg-green-100">
                                    <svg class="size-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-green-700">Resolved</p>
                                    <p class="text-xs text-gray-500">Alert sudah ditangani</p>
                                </div>
                            @endif
                        </div>

                        @if ($alert->status === 'resolved')
                            <div class="mt-4 border-t border-gray-200 pt-4">
                                <dl class="space-y-3">
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500">Resolved oleh</dt>
                                        <dd class="mt-0.5 text-sm text-gray-900">
                                            {{ $alert->resolvedBy?->name ?? '-' }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500">Resolved pada</dt>
                                        <dd class="mt-0.5 text-sm text-gray-900">
                                            {{ $alert->resolved_at?->format('d M Y H:i:s') ?? '-' }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Context Information -->
        @if ($alert->context)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
                    <h3 class="text-base font-semibold text-gray-900">Context Information</h3>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <div class="rounded-md bg-gray-50 p-4">
                        <pre class="whitespace-pre-wrap break-words text-sm text-gray-700">{{ json_encode($alert->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
