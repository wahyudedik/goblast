@extends('layouts.app')

@section('page-title', 'Kelola Invoice')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="sm:flex sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Kelola Invoice</h1>
                <p class="mt-2 text-sm text-gray-700">Daftar semua catatan pembayaran tenant.</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('admin.invoices.create') }}"
                    class="inline-flex items-center px-6 py-2.5 bg-green-600 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-150 shadow-sm">
                    <svg class="-ms-0.5 me-1.5 size-5" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                    </svg>
                    Catat Pembayaran
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-4 sm:p-6">
                <form method="GET" action="{{ route('admin.invoices.index') }}"
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <div>
                        <label for="tenant" class="block text-sm font-semibold text-gray-900 mb-2">Tenant</label>
                        <select id="tenant" name="tenant"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors">
                            <option value="">Semua Tenant</option>
                            @foreach ($tenants as $tenant)
                                <option value="{{ $tenant->id }}"
                                    {{ ($filters['tenant'] ?? '') == $tenant->id ? 'selected' : '' }}>
                                    {{ $tenant->name }}
                                </option>
                            @endforeach
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
                            <a href="{{ route('admin.invoices.index') }}"
                                class="inline-flex items-center rounded-md bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Invoices Table -->
        @if ($invoices->isEmpty())
            <div class="rounded-lg border border-dashed border-gray-300 bg-white p-12 text-center">
                <svg class="mx-auto size-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">Tidak ada invoice</h3>
                <p class="mt-1 text-sm text-gray-500">Belum ada catatan pembayaran.</p>
                <div class="mt-6">
                    <a href="{{ route('admin.invoices.create') }}"
                        class="inline-flex items-center px-6 py-2.5 bg-green-600 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-150 shadow-sm">
                        <svg class="-ms-0.5 me-1.5 size-5" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                        </svg>
                        Catat Pembayaran
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
                                    class="py-3.5 pe-3 ps-4 text-left text-sm font-semibold text-gray-900 sm:ps-6">Tenant
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Paket
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Nominal
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Tanggal
                                    Bayar</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Dicatat
                                    Oleh</th>
                                <th scope="col" class="relative py-3.5 pe-4 ps-3 sm:pe-6">
                                    <span class="sr-only">Aksi</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($invoices as $invoice)
                                <tr>
                                    <td class="whitespace-nowrap py-4 pe-3 ps-4 text-sm font-medium text-gray-900 sm:ps-6">
                                        <a href="{{ route('admin.tenants.show', $invoice->tenant) }}"
                                            class="hover:text-green-600">
                                            {{ $invoice->tenant->name }}
                                        </a>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $invoice->plan->name }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 font-medium">
                                        Rp {{ number_format($invoice->amount, 0, ',', '.') }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $invoice->paid_at->format('d M Y') }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $invoice->recordedBy?->name ?? '-' }}
                                    </td>
                                    <td
                                        class="relative whitespace-nowrap py-4 pe-4 ps-3 text-right text-sm font-medium sm:pe-6">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.invoices.show', $invoice) }}"
                                                class="text-green-600 hover:text-green-700">Lihat</a>
                                            <a href="{{ route('admin.invoices.edit', $invoice) }}"
                                                class="text-indigo-600 hover:text-indigo-700">Edit</a>
                                            <form action="{{ route('admin.invoices.destroy', $invoice) }}" method="POST"
                                                class="inline"
                                                data-confirm="Apakah Anda yakin ingin menghapus invoice ini? Aksi ini tidak dapat dibatalkan."
                                                data-confirm-title="Hapus Invoice" data-confirm-button="Ya, Hapus"
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
                @if ($invoices->hasPages())
                    <div class="border-t border-gray-200 px-4 py-3 sm:px-6">
                        {{ $invoices->links() }}
                    </div>
                @endif
            </div>
        @endif
    </div>
@endsection
