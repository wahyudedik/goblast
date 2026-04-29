@extends('layouts.app')

@section('page-title', 'Reminders')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="sm:flex sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Reminders</h1>
                <p class="mt-2 text-sm text-gray-700">Kelola pengingat otomatis untuk mengirim pesan terjadwal.</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('reminders.create') }}"
                    class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700">
                    <svg class="-ms-0.5 me-1.5 size-5" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                    </svg>
                    Buat Reminder
                </a>
            </div>
        </div>

        <!-- Filter -->
        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <form method="GET" action="{{ route('reminders.index') }}" class="flex items-center gap-4">
                    <label for="type" class="text-sm font-medium text-gray-900">Filter berdasarkan tipe:</label>
                    <select name="type" id="type"
                        class="px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-colors text-sm"
                        onchange="this.form.submit()">
                        <option value="">Semua Tipe</option>
                        <option value="spp_due" {{ $selectedType === 'spp_due' ? 'selected' : '' }}>⏰ SPP Jatuh Tempo
                        </option>
                        <option value="invoice_unpaid" {{ $selectedType === 'invoice_unpaid' ? 'selected' : '' }}>📄 Invoice
                            Belum Bayar</option>
                        <option value="booking_tomorrow" {{ $selectedType === 'booking_tomorrow' ? 'selected' : '' }}>📅
                            Booking Besok</option>
                    </select>
                    @if ($selectedType)
                        <a href="{{ route('reminders.index') }}" class="text-sm text-green-600 hover:text-green-700">Hapus
                            filter</a>
                    @endif
                </form>
            </div>
        </div>

        <!-- Reminders List -->
        @if ($reminders->isEmpty())
            <div class="rounded-lg border border-dashed border-gray-300 bg-white p-12 text-center">
                <svg class="mx-auto size-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">Tidak ada reminder</h3>
                <p class="mt-1 text-sm text-gray-500">Mulai dengan membuat reminder baru untuk mengirim pesan otomatis.</p>
                <div class="mt-6">
                    <a href="{{ route('reminders.create') }}"
                        class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700">
                        <svg class="-ms-0.5 me-1.5 size-5" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                        </svg>
                        Buat Reminder
                    </a>
                </div>
            </div>
        @else
            <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="py-3.5 pe-3 ps-4 text-left text-sm font-semibold text-gray-900 sm:ps-6">Nama</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Tipe</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Device</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Template
                            </th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Terakhir
                                Jalan</th>
                            <th scope="col" class="relative py-3.5 pe-4 ps-3 sm:pe-6"><span class="sr-only">Aksi</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach ($reminders as $reminder)
                            <tr>
                                <td class="whitespace-nowrap py-4 pe-3 ps-4 text-sm font-medium text-gray-900 sm:ps-6">
                                    <a href="{{ route('reminders.show', $reminder) }}"
                                        class="hover:text-green-600">{{ $reminder->name }}</a>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    @if ($reminder->type === 'spp_due')
                                        <span
                                            class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">⏰
                                            SPP Jatuh Tempo</span>
                                    @elseif ($reminder->type === 'invoice_unpaid')
                                        <span
                                            class="inline-flex items-center rounded-md bg-orange-50 px-2 py-1 text-xs font-medium text-orange-700 ring-1 ring-inset ring-orange-700/10">📄
                                            Invoice Belum Bayar</span>
                                    @else
                                        <span
                                            class="inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-700/10">📅
                                            Booking Besok</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $reminder->device->name }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    {{ $reminder->template?->name ?? 'Pesan Manual' }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm">
                                    @if ($reminder->is_active)
                                        <span
                                            class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                            <svg class="-ms-0.5 me-1.5 size-2 fill-green-500" viewBox="0 0 6 6">
                                                <circle cx="3" cy="3" r="3" />
                                            </svg>
                                            Aktif
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                            <svg class="-ms-0.5 me-1.5 size-2 fill-gray-400" viewBox="0 0 6 6">
                                                <circle cx="3" cy="3" r="3" />
                                            </svg>
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    {{ $reminder->last_run_at ? $reminder->last_run_at->diffForHumans() : 'Belum pernah' }}
                                </td>
                                <td
                                    class="relative whitespace-nowrap py-4 pe-4 ps-3 text-right text-sm font-medium sm:pe-6">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('reminders.show', $reminder) }}"
                                            class="text-green-600 hover:text-green-700">Lihat</a>
                                        <form action="{{ route('reminders.toggle', $reminder) }}" method="POST"
                                            class="inline">
                                            @csrf
                                            <button type="submit"
                                                class="{{ $reminder->is_active ? 'text-yellow-600 hover:text-yellow-900' : 'text-green-600 hover:text-green-700' }}">
                                                {{ $reminder->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                        <form action="{{ route('reminders.destroy', $reminder) }}" method="POST"
                                            class="inline" data-confirm="Apakah Anda yakin ingin menghapus reminder ini?"
                                            data-confirm-title="Hapus Reminder" data-confirm-button="Ya, Hapus"
                                            data-confirm-type="danger">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
