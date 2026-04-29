@extends('layouts.app')

@section('page-title', 'Kelola Tenant')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="sm:flex sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Kelola Tenant</h1>
                <p class="mt-2 text-sm text-gray-700">Daftar semua tenant yang terdaftar di platform.</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('admin.tenants.create') }}"
                    class="inline-flex items-center px-6 py-2.5 bg-green-600 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-150 shadow-sm">
                    <svg class="-ms-0.5 me-1.5 size-5" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                    </svg>
                    Tambah Tenant
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-4 sm:p-6">
                <form method="GET" action="{{ route('admin.tenants.index') }}"
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <div>
                        <label for="status" class="block text-sm font-semibold text-gray-900 mb-2">Status</label>
                        <select id="status" name="status"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors">
                            <option value="">Semua Status</option>
                            <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active
                            </option>
                            <option value="trial" {{ ($filters['status'] ?? '') === 'trial' ? 'selected' : '' }}>Trial
                            </option>
                            <option value="suspended" {{ ($filters['status'] ?? '') === 'suspended' ? 'selected' : '' }}>
                                Suspended</option>
                            <option value="expired" {{ ($filters['status'] ?? '') === 'expired' ? 'selected' : '' }}>Expired
                            </option>
                        </select>
                    </div>

                    <div>
                        <label for="plan" class="block text-sm font-semibold text-gray-900 mb-2">Paket</label>
                        <select id="plan" name="plan"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors">
                            <option value="">Semua Paket</option>
                            @foreach ($plans as $plan)
                                <option value="{{ $plan->id }}"
                                    {{ ($filters['plan'] ?? '') == $plan->id ? 'selected' : '' }}>
                                    {{ $plan->name }}
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
                            <a href="{{ route('admin.tenants.index') }}"
                                class="inline-flex items-center rounded-md bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Tenants Table -->
        @if ($tenants->isEmpty())
            <div class="rounded-lg border border-dashed border-gray-300 bg-white p-12 text-center">
                <svg class="mx-auto size-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">Tidak ada tenant</h3>
                <p class="mt-1 text-sm text-gray-500">Belum ada tenant yang terdaftar.</p>
                <div class="mt-6">
                    <a href="{{ route('admin.tenants.create') }}"
                        class="inline-flex items-center px-6 py-2.5 bg-green-600 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-150 shadow-sm">
                        <svg class="-ms-0.5 me-1.5 size-5" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                        </svg>
                        Tambah Tenant
                    </a>
                </div>
            </div>
        @else
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="py-3.5 pe-3 ps-4 text-left text-sm font-semibold text-gray-900 sm:ps-6">Nama</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Email
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Paket
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Pesan
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                    Terdaftar</th>
                                <th scope="col" class="relative py-3.5 pe-4 ps-3 sm:pe-6">
                                    <span class="sr-only">Aksi</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($tenants as $tenant)
                                @php
                                    $activeSubscription = $tenant->subscriptions->where('status', 'active')->first();
                                @endphp
                                <tr>
                                    <td class="whitespace-nowrap py-4 pe-3 ps-4 text-sm font-medium text-gray-900 sm:ps-6">
                                        <a href="{{ route('admin.tenants.show', $tenant) }}" class="hover:text-green-600">
                                            {{ $tenant->name }}
                                        </a>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $tenant->email }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $activeSubscription?->plan?->name ?? '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        @include('admin.tenants._status-badge', [
                                            'status' => $tenant->status,
                                        ])
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ number_format($tenant->message_logs_count) }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $tenant->created_at->format('d M Y') }}
                                    </td>
                                    <td
                                        class="relative whitespace-nowrap py-4 pe-4 ps-3 text-right text-sm font-medium sm:pe-6">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.tenants.show', $tenant) }}"
                                                class="text-green-600 hover:text-green-700">Lihat</a>
                                            <a href="{{ route('admin.tenants.edit', $tenant) }}"
                                                class="text-indigo-600 hover:text-indigo-700">Edit</a>

                                            @if ($tenant->status !== 'suspended')
                                                <button type="button" class="text-yellow-600 hover:text-yellow-700"
                                                    onclick="document.getElementById('suspend-modal-{{ $tenant->id }}').showModal()">
                                                    Suspend
                                                </button>
                                            @else
                                                <form action="{{ route('admin.tenants.activate', $tenant) }}"
                                                    method="POST" class="inline"
                                                    data-confirm="Apakah Anda yakin ingin mengaktifkan kembali tenant ini?"
                                                    data-confirm-title="Aktifkan Tenant"
                                                    data-confirm-button="Ya, Aktifkan" data-confirm-type="warning">
                                                    @csrf
                                                    <button type="submit"
                                                        class="text-green-600 hover:text-green-700">Aktifkan</button>
                                                </form>
                                            @endif

                                            <form action="{{ route('admin.tenants.destroy', $tenant) }}" method="POST"
                                                class="inline"
                                                data-confirm="Apakah Anda yakin ingin menghapus tenant ini? Aksi ini tidak dapat dibatalkan."
                                                data-confirm-title="Hapus Tenant" data-confirm-button="Ya, Hapus"
                                                data-confirm-type="danger">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="text-red-600 hover:text-red-700">Hapus</button>
                                            </form>
                                        </div>

                                        @if ($tenant->status !== 'suspended')
                                            <!-- Suspend Modal -->
                                            <dialog id="suspend-modal-{{ $tenant->id }}"
                                                class="rounded-lg p-0 shadow-xl backdrop:bg-gray-900/50 w-full max-w-md">
                                                <form action="{{ route('admin.tenants.suspend', $tenant) }}"
                                                    method="POST" class="p-6 text-left">
                                                    @csrf
                                                    <h3 class="text-lg font-semibold text-gray-900">Suspend Tenant</h3>
                                                    <p class="mt-2 text-sm text-gray-500">Masukkan alasan penangguhan untuk
                                                        tenant <strong>{{ $tenant->name }}</strong>.</p>
                                                    <div class="mt-4">
                                                        <label for="reason-{{ $tenant->id }}"
                                                            class="block text-sm font-semibold text-gray-900 mb-2">Alasan</label>
                                                        <textarea id="reason-{{ $tenant->id }}" name="reason" rows="3" required
                                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors"
                                                            placeholder="Masukkan alasan penangguhan..."></textarea>
                                                    </div>
                                                    <div class="mt-6 flex justify-end gap-3">
                                                        <button type="button"
                                                            class="px-4 py-2.5 border-2 border-gray-300 rounded-lg text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-all"
                                                            onclick="this.closest('dialog').close()">
                                                            Batal
                                                        </button>
                                                        <button type="submit"
                                                            class="rounded-md bg-yellow-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-yellow-700">
                                                            Suspend
                                                        </button>
                                                    </div>
                                                </form>
                                            </dialog>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if ($tenants->hasPages())
                    <div class="border-t border-gray-200 px-4 py-3 sm:px-6">
                        {{ $tenants->links() }}
                    </div>
                @endif
            </div>
        @endif
    </div>
@endsection
