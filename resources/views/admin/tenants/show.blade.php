@extends('layouts.app')

@section('page-title', 'Detail Tenant')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="sm:flex sm:items-center sm:justify-between">
            <div>
                <nav class="flex" aria-label="Breadcrumb">
                    <ol role="list" class="flex items-center space-x-2">
                        <li>
                            <a href="{{ route('admin.tenants.index') }}"
                                class="text-sm text-gray-500 hover:text-gray-700">Kelola Tenant</a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="size-5 shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span class="ms-2 text-sm font-medium text-gray-900">{{ $tenant->name }}</span>
                            </div>
                        </li>
                    </ol>
                </nav>
                <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ $tenant->name }}</h1>
            </div>
            <div class="mt-4 flex flex-wrap gap-2 sm:mt-0">
                <a href="{{ route('admin.tenants.edit', $tenant) }}"
                    class="inline-flex items-center px-4 py-2.5 border-2 border-gray-300 rounded-lg text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-all">
                    <svg class="-ms-0.5 me-1.5 size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                    </svg>
                    Edit
                </a>

                @if ($tenant->status !== 'suspended')
                    <button type="button"
                        class="inline-flex items-center rounded-md bg-yellow-50 px-3 py-2 text-sm font-semibold text-yellow-700 shadow-sm ring-1 ring-inset ring-yellow-600/20 hover:bg-yellow-100"
                        onclick="document.getElementById('suspend-modal').showModal()">
                        Suspend
                    </button>
                @else
                    <form action="{{ route('admin.tenants.activate', $tenant) }}" method="POST"
                        data-confirm="Apakah Anda yakin ingin mengaktifkan kembali tenant ini?"
                        data-confirm-title="Aktifkan Tenant" data-confirm-button="Ya, Aktifkan" data-confirm-type="warning">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center rounded-md bg-green-50 px-3 py-2 text-sm font-semibold text-green-700 shadow-sm ring-1 ring-inset ring-green-600/20 hover:bg-green-100">
                            Aktifkan
                        </button>
                    </form>
                @endif

                @if ($tenant->status === 'trial')
                    <button type="button"
                        class="inline-flex items-center rounded-md bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700 shadow-sm ring-1 ring-inset ring-blue-600/20 hover:bg-blue-100"
                        onclick="document.getElementById('extend-trial-modal').showModal()">
                        Perpanjang Trial
                    </button>
                @endif

                <form action="{{ route('admin.tenants.destroy', $tenant) }}" method="POST"
                    data-confirm="Apakah Anda yakin ingin menghapus tenant ini? Aksi ini tidak dapat dibatalkan."
                    data-confirm-title="Hapus Tenant" data-confirm-button="Ya, Hapus" data-confirm-type="danger">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="inline-flex items-center rounded-md bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 shadow-sm ring-1 ring-inset ring-red-600/10 hover:bg-red-100">
                        Hapus
                    </button>
                </form>
            </div>
        </div>

        <!-- Tenant Info Cards -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Tenant Details -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 lg:col-span-2">
                <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
                    <h3 class="text-base font-semibold text-gray-900">Informasi Tenant</h3>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Nama</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $tenant->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Email</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $tenant->email }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Telepon</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $tenant->phone ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="mt-1">
                                @include('admin.tenants._status-badge', ['status' => $tenant->status])
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Terdaftar</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $tenant->created_at->format('d M Y H:i') }}</dd>
                        </div>
                        @if ($tenant->status === 'trial' && $tenant->trial_ends_at)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Trial Berakhir</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ $tenant->trial_ends_at->format('d M Y') }}
                                    <span class="text-gray-500">({{ $tenant->trial_ends_at->diffForHumans() }})</span>
                                </dd>
                            </div>
                        @endif
                        @if ($tenant->status === 'suspended')
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-gray-500">Alasan Suspend</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $tenant->suspended_reason ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Tanggal Suspend</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ $tenant->suspended_at?->format('d M Y H:i') ?? '-' }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Stats -->
            <div class="space-y-6">
                <!-- Subscription Info -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
                        <h3 class="text-base font-semibold text-gray-900">Langganan</h3>
                    </div>
                    <div class="px-4 py-5 sm:p-6">
                        @if ($activeSubscription)
                            <dl class="space-y-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Paket</dt>
                                    <dd class="mt-1 text-sm font-semibold text-gray-900">
                                        {{ $activeSubscription->plan->name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Periode</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ $activeSubscription->starts_at->format('d M Y') }} -
                                        {{ $activeSubscription->ends_at->format('d M Y') }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Kuota Terpakai</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        @if ($activeSubscription->message_quota_limit)
                                            {{ number_format($activeSubscription->message_quota_used) }} /
                                            {{ number_format($activeSubscription->message_quota_limit) }}
                                            @php
                                                $percentage =
                                                    $activeSubscription->message_quota_limit > 0
                                                        ? round(
                                                            ($activeSubscription->message_quota_used /
                                                                $activeSubscription->message_quota_limit) *
                                                                100,
                                                        )
                                                        : 0;
                                            @endphp
                                            <div class="mt-2 w-full rounded-full bg-gray-200 h-2">
                                                <div class="h-2 rounded-full {{ $percentage >= 90 ? 'bg-red-500' : ($percentage >= 70 ? 'bg-yellow-500' : 'bg-green-500') }}"
                                                    style="width: {{ min($percentage, 100) }}%"></div>
                                            </div>
                                        @else
                                            {{ number_format($activeSubscription->message_quota_used) }} / Unlimited
                                        @endif
                                    </dd>
                                </div>
                            </dl>
                        @else
                            <p class="text-sm text-gray-500">Tidak ada langganan aktif.</p>
                        @endif
                    </div>
                </div>

                <!-- Device & Message Stats -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
                        <h3 class="text-base font-semibold text-gray-900">Statistik</h3>
                    </div>
                    <div class="px-4 py-5 sm:p-6">
                        <dl class="space-y-4">
                            <div class="flex items-center justify-between">
                                <dt class="text-sm font-medium text-gray-500">Total Device</dt>
                                <dd class="text-sm font-semibold text-gray-900">{{ $tenant->devices_count }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-sm font-medium text-gray-500">Device Terhubung</dt>
                                <dd class="text-sm font-semibold text-green-600">{{ $tenant->connected_devices_count }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between border-t border-gray-100 pt-4">
                                <dt class="text-sm font-medium text-gray-500">Pesan Terkirim</dt>
                                <dd class="text-sm font-semibold text-green-600">
                                    {{ number_format($tenant->total_sent_count) }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-sm font-medium text-gray-500">Pesan Gagal</dt>
                                <dd class="text-sm font-semibold text-red-600">
                                    {{ number_format($tenant->total_failed_count) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
                <h3 class="text-base font-semibold text-gray-900">Pengguna ({{ $tenant->users->count() }})</h3>
            </div>
            @if ($tenant->users->isNotEmpty())
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="py-3.5 pe-3 ps-4 text-left text-sm font-semibold text-gray-900 sm:ps-6">Nama</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Email
                            </th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Role</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach ($tenant->users as $user)
                            <tr>
                                <td class="whitespace-nowrap py-4 pe-3 ps-4 text-sm font-medium text-gray-900 sm:ps-6">
                                    {{ $user->name }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $user->email }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm">
                                    <span
                                        class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                        {{ ucfirst($user->role) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm">
                                    @if ($user->is_active)
                                        <span
                                            class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Aktif</span>
                                    @else
                                        <span
                                            class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">Nonaktif</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="px-4 py-8 text-center text-sm text-gray-500">Belum ada pengguna.</div>
            @endif
        </div>

        <!-- Subscription History -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
                <h3 class="text-base font-semibold text-gray-900">Riwayat Langganan</h3>
            </div>
            @if ($tenant->subscriptions->isNotEmpty())
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="py-3.5 pe-3 ps-4 text-left text-sm font-semibold text-gray-900 sm:ps-6">Paket</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status
                            </th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Kuota
                            </th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Mulai
                            </th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Berakhir
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach ($tenant->subscriptions as $subscription)
                            <tr>
                                <td class="whitespace-nowrap py-4 pe-3 ps-4 text-sm font-medium text-gray-900 sm:ps-6">
                                    {{ $subscription->plan->name }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm">
                                    @if ($subscription->status === 'active')
                                        <span
                                            class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Active</span>
                                    @elseif ($subscription->status === 'expired')
                                        <span
                                            class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">Expired</span>
                                    @else
                                        <span
                                            class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">{{ ucfirst($subscription->status) }}</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    {{ number_format($subscription->message_quota_used) }} /
                                    {{ $subscription->message_quota_limit ? number_format($subscription->message_quota_limit) : 'Unlimited' }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    {{ $subscription->starts_at->format('d M Y') }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    {{ $subscription->ends_at->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="px-4 py-8 text-center text-sm text-gray-500">Belum ada riwayat langganan.</div>
            @endif
        </div>
    </div>

    @if ($tenant->status !== 'suspended')
        <!-- Suspend Modal -->
        <dialog id="suspend-modal" class="rounded-lg p-0 shadow-xl backdrop:bg-gray-900/50 w-full max-w-md">
            <form action="{{ route('admin.tenants.suspend', $tenant) }}" method="POST" class="p-6">
                @csrf
                <h3 class="text-lg font-semibold text-gray-900">Suspend Tenant</h3>
                <p class="mt-2 text-sm text-gray-500">Masukkan alasan penangguhan untuk tenant
                    <strong>{{ $tenant->name }}</strong>. Semua akses login dan pengiriman pesan akan diblokir.</p>
                <div class="mt-4">
                    <label for="suspend-reason" class="block text-sm font-semibold text-gray-900 mb-2">Alasan</label>
                    <textarea id="suspend-reason" name="reason" rows="3" required
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

    @if ($tenant->status === 'trial')
        <!-- Extend Trial Modal -->
        <dialog id="extend-trial-modal" class="rounded-lg p-0 shadow-xl backdrop:bg-gray-900/50 w-full max-w-md">
            <form action="{{ route('admin.tenants.extend-trial', $tenant) }}" method="POST" class="p-6">
                @csrf
                <h3 class="text-lg font-semibold text-gray-900">Perpanjang Masa Trial</h3>
                <p class="mt-2 text-sm text-gray-500">
                    Trial saat ini berakhir: <strong>{{ $tenant->trial_ends_at?->format('d M Y') ?? '-' }}</strong>
                </p>
                <div class="mt-4">
                    <label for="extend-days" class="block text-sm font-semibold text-gray-900 mb-2">Jumlah Hari</label>
                    <input type="number" id="extend-days" name="days" min="1" max="90" value="7"
                        required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors">
                    <p class="mt-2 text-sm text-gray-600">Maksimal 90 hari.</p>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button"
                        class="px-4 py-2.5 border-2 border-gray-300 rounded-lg text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-all"
                        onclick="this.closest('dialog').close()">
                        Batal
                    </button>
                    <button type="submit"
                        class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                        Perpanjang
                    </button>
                </div>
            </form>
        </dialog>
    @endif
@endsection
