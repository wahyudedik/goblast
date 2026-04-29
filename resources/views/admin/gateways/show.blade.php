@extends('layouts.app')

@section('page-title', 'Detail Gateway')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="sm:flex sm:items-center sm:justify-between">
            <div>
                <nav class="flex" aria-label="Breadcrumb">
                    <ol role="list" class="flex items-center space-x-2">
                        <li>
                            <a href="{{ route('admin.gateways.index') }}"
                                class="text-sm text-gray-500 hover:text-gray-700">Kelola Gateway</a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="size-5 shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span class="ms-2 text-sm font-medium text-gray-900">{{ $gateway->name }}</span>
                            </div>
                        </li>
                    </ol>
                </nav>
                <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ $gateway->name }}</h1>
            </div>
            <div class="mt-4 flex flex-wrap gap-2 sm:mt-0">
                <form action="{{ route('admin.gateways.restart', $gateway) }}" method="POST"
                    data-confirm="Apakah Anda yakin ingin me-restart gateway ini?" data-confirm-title="Restart Gateway"
                    data-confirm-button="Ya, Restart" data-confirm-type="warning">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center rounded-md bg-yellow-50 px-3 py-2 text-sm font-semibold text-yellow-700 shadow-sm ring-1 ring-inset ring-yellow-600/20 hover:bg-yellow-100">
                        <svg class="-ms-0.5 me-1.5 size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182M2.985 19.644l3.181-3.182" />
                        </svg>
                        Restart
                    </button>
                </form>

                <form action="{{ route('admin.gateways.destroy', $gateway) }}" method="POST"
                    data-confirm="Apakah Anda yakin ingin menghapus gateway ini? Aksi ini tidak dapat dibatalkan."
                    data-confirm-title="Hapus Gateway" data-confirm-button="Ya, Hapus" data-confirm-type="danger">
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

        <!-- Gateway Details -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Gateway Info -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 lg:col-span-2">
                <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
                    <h3 class="text-base font-semibold text-gray-900">Informasi Gateway</h3>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Nama</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $gateway->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="mt-1">
                                @include('admin.gateways._status-badge', ['status' => $gateway->status])
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Base URL</dt>
                            <dd class="mt-1 text-sm text-gray-900 break-all">{{ $gateway->base_url }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Terakhir Dicek</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $gateway->last_checked_at?->format('d M Y H:i:s') ?? '-' }}
                                @if ($gateway->last_checked_at)
                                    <span class="text-gray-500">({{ $gateway->last_checked_at->diffForHumans() }})</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Dibuat</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $gateway->created_at->format('d M Y H:i') }}</dd>
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
                            @if ($gateway->status === 'active')
                                <div class="flex size-10 items-center justify-center rounded-full bg-green-100">
                                    <svg class="size-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-green-700">Aktif</p>
                                    <p class="text-xs text-gray-500">Gateway berjalan normal</p>
                                </div>
                            @elseif ($gateway->status === 'inactive')
                                <div class="flex size-10 items-center justify-center rounded-full bg-gray-100">
                                    <svg class="size-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-600">Tidak Aktif</p>
                                    <p class="text-xs text-gray-500">Gateway sedang tidak aktif</p>
                                </div>
                            @else
                                <div class="flex size-10 items-center justify-center rounded-full bg-red-100">
                                    <svg class="size-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-red-700">Error</p>
                                    <p class="text-xs text-gray-500">Gateway mengalami masalah</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Last Error -->
        @if ($gateway->last_error)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
                    <h3 class="text-base font-semibold text-red-700">Error Terakhir</h3>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <div class="rounded-md bg-red-50 p-4">
                        <div class="flex">
                            <svg class="size-5 shrink-0 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                                    clip-rule="evenodd" />
                            </svg>
                            <div class="ms-3">
                                <p class="text-sm text-red-700">{{ $gateway->last_error }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Health Check History -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
                <h3 class="text-base font-semibold text-gray-900">Riwayat Health Check</h3>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status Saat Ini</dt>
                        <dd class="mt-1">
                            @include('admin.gateways._status-badge', ['status' => $gateway->status])
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Terakhir Dicek</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $gateway->last_checked_at?->format('d M Y H:i:s') ?? 'Belum pernah dicek' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Terakhir Error</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $gateway->last_error ? 'Ya - lihat detail di atas' : 'Tidak ada error' }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
@endsection
