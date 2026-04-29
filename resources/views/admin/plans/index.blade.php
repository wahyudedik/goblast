@extends('layouts.app')

@section('page-title', 'Kelola Paket')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="sm:flex sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Kelola Paket</h1>
                <p class="mt-2 text-sm text-gray-700">Daftar semua paket langganan yang tersedia di platform.</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('admin.plans.create') }}"
                    class="inline-flex items-center px-6 py-2.5 bg-green-600 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-150 shadow-sm">
                    <svg class="-ms-0.5 me-1.5 size-5" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                    </svg>
                    Tambah Paket
                </a>
            </div>
        </div>

        <!-- Plans Table -->
        @if ($plans->isEmpty())
            <div class="rounded-lg border border-dashed border-gray-300 bg-white p-12 text-center">
                <svg class="mx-auto size-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">Tidak ada paket</h3>
                <p class="mt-1 text-sm text-gray-500">Belum ada paket langganan yang dibuat.</p>
                <div class="mt-6">
                    <a href="{{ route('admin.plans.create') }}"
                        class="inline-flex items-center px-6 py-2.5 bg-green-600 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-150 shadow-sm">
                        <svg class="-ms-0.5 me-1.5 size-5" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                        </svg>
                        Tambah Paket
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
                                    class="py-3.5 pe-3 ps-4 text-left text-sm font-semibold text-gray-900 sm:ps-6">Nama
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Harga
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Kuota
                                    Pesan</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Max
                                    Device</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Fitur
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                    Subscriber</th>
                                <th scope="col" class="relative py-3.5 pe-4 ps-3 sm:pe-6">
                                    <span class="sr-only">Aksi</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($plans as $plan)
                                <tr>
                                    <td class="whitespace-nowrap py-4 pe-3 ps-4 text-sm font-medium text-gray-900 sm:ps-6">
                                        {{ $plan->name }}
                                        <span class="block text-xs text-gray-500">{{ $plan->slug }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        Rp {{ number_format($plan->price, 0, ',', '.') }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        @if (is_null($plan->message_quota))
                                            <span class="text-green-600 font-medium">Unlimited</span>
                                        @else
                                            {{ number_format($plan->message_quota) }}
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $plan->max_devices }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        <div class="flex flex-wrap gap-1">
                                            @if ($plan->has_reminder)
                                                <span
                                                    class="inline-flex items-center rounded-full bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">Reminder</span>
                                            @endif
                                            @if ($plan->has_api)
                                                <span
                                                    class="inline-flex items-center rounded-full bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-700/10">API</span>
                                            @endif
                                            @if ($plan->has_multi_device)
                                                <span
                                                    class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10">Multi
                                                    Device</span>
                                            @endif
                                            @if (!$plan->has_reminder && !$plan->has_api && !$plan->has_multi_device)
                                                <span class="text-gray-400 text-xs">-</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        @if ($plan->is_active)
                                            <span
                                                class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Aktif</span>
                                        @else
                                            <span
                                                class="inline-flex items-center rounded-full bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ number_format($plan->active_subscriptions_count) }}
                                    </td>
                                    <td
                                        class="relative whitespace-nowrap py-4 pe-4 ps-3 text-right text-sm font-medium sm:pe-6">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.plans.edit', $plan) }}"
                                                class="text-indigo-600 hover:text-indigo-700">Edit</a>

                                            <form action="{{ route('admin.plans.toggle-active', $plan) }}" method="POST"
                                                class="inline">
                                                @csrf
                                                <button type="submit"
                                                    class="{{ $plan->is_active ? 'text-yellow-600 hover:text-yellow-700' : 'text-green-600 hover:text-green-700' }}">
                                                    {{ $plan->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </button>
                                            </form>

                                            <form action="{{ route('admin.plans.destroy', $plan) }}" method="POST"
                                                class="inline"
                                                data-confirm="Apakah Anda yakin ingin menghapus paket ini? Aksi ini tidak dapat dibatalkan."
                                                data-confirm-title="Hapus Paket" data-confirm-button="Ya, Hapus"
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
            </div>
        @endif
    </div>
@endsection
