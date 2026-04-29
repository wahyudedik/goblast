@extends('layouts.app')

@section('page-title', $reminder->name)

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <a href="{{ route('reminders.index') }}"
                class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-900 mb-2">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                    </path>
                </svg>
                Kembali ke Reminders
            </a>
            <div class="mt-2 sm:flex sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $reminder->name }}</h1>
                    <div class="mt-2 flex items-center gap-2 flex-wrap">
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
                        @if ($reminder->is_active)
                            <span
                                class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Aktif</span>
                        @else
                            <span
                                class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">Nonaktif</span>
                        @endif
                        <span
                            class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">
                            @php
                                $freqLabels = [
                                    'daily' => 'Setiap Hari',
                                    'weekly' => 'Setiap Minggu',
                                    'monthly' => 'Setiap Bulan',
                                    'yearly' => 'Setiap Tahun',
                                ];
                            @endphp
                            🔄 {{ $freqLabels[$reminder->frequency] ?? $reminder->frequency }} — {{ $reminder->send_time }}
                            WIB
                        </span>
                    </div>
                </div>
                <div class="mt-4 flex gap-2 sm:mt-0">
                    <form action="{{ route('reminders.toggle', $reminder) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center rounded-md {{ $reminder->is_active ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700' }} px-3 py-2 text-sm font-semibold text-white shadow-sm">
                            {{ $reminder->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                        </button>
                    </form>
                    <a href="{{ route('reminders.edit', $reminder) }}"
                        class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        Edit
                    </a>
                    <form action="{{ route('reminders.destroy', $reminder) }}" method="POST" class="inline"
                        data-confirm="Apakah Anda yakin ingin menghapus reminder ini?" data-confirm-title="Hapus Reminder"
                        data-confirm-button="Ya, Hapus" data-confirm-type="danger">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">Hapus</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Info Cards -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Reminder Info -->
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-base font-semibold text-gray-900">Informasi Reminder</h3>
                    <dl class="mt-4 space-y-3">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Device</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $reminder->device->name }}</dd>
                        </div>
                        @if ($reminder->template)
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Template</dt>
                                <dd class="text-sm font-medium text-gray-900">
                                    <a href="{{ route('templates.show', $reminder->template) }}"
                                        class="text-green-600 hover:text-green-700">{{ $reminder->template->name }}</a>
                                </dd>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Penerima</dt>
                            <dd class="text-sm font-medium text-gray-900">
                                {{ is_array($reminder->recipients) ? count($reminder->recipients) : 0 }} nomor</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Terakhir Dijalankan</dt>
                            <dd class="text-sm font-medium text-gray-900">
                                {{ $reminder->last_run_at ? $reminder->last_run_at->format('d M Y H:i') : 'Belum pernah' }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Total Pesan Terkirim</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $reminder->reminder_logs_count }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Schedule Info -->
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-base font-semibold text-gray-900">Jadwal</h3>
                    <div class="mt-4 rounded-lg bg-green-50 p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-lg font-bold text-green-900">
                                    @php
                                        $dayNames = [
                                            1 => 'Senin',
                                            2 => 'Selasa',
                                            3 => 'Rabu',
                                            4 => 'Kamis',
                                            5 => 'Jumat',
                                            6 => 'Sabtu',
                                            7 => 'Minggu',
                                        ];
                                        $monthNames = [
                                            1 => 'Januari',
                                            2 => 'Februari',
                                            3 => 'Maret',
                                            4 => 'April',
                                            5 => 'Mei',
                                            6 => 'Juni',
                                            7 => 'Juli',
                                            8 => 'Agustus',
                                            9 => 'September',
                                            10 => 'Oktober',
                                            11 => 'November',
                                            12 => 'Desember',
                                        ];
                                    @endphp
                                    @if ($reminder->frequency === 'daily')
                                        Setiap Hari
                                    @elseif ($reminder->frequency === 'weekly')
                                        Setiap {{ $dayNames[$reminder->send_day] ?? '' }}
                                    @elseif ($reminder->frequency === 'monthly')
                                        Setiap Tanggal {{ $reminder->send_day }}
                                    @elseif ($reminder->frequency === 'yearly')
                                        Setiap Bulan {{ $monthNames[$reminder->send_day] ?? '' }}
                                    @endif
                                </p>
                                <p class="text-sm text-green-700">Pukul {{ $reminder->send_time }} WIB</p>
                            </div>
                        </div>
                    </div>

                    @if ($reminder->message && !$reminder->template)
                        <div class="mt-4">
                            <h4 class="text-sm font-medium text-gray-500 mb-2">Pesan</h4>
                            <div class="rounded-lg bg-gray-50 p-3">
                                <p class="whitespace-pre-wrap text-sm text-gray-900">{{ $reminder->message }}</p>
                            </div>
                        </div>
                    @endif

                    @if (is_array($reminder->recipients) && count($reminder->recipients) > 0)
                        <div class="mt-4">
                            <h4 class="text-sm font-medium text-gray-500 mb-2">Penerima
                                ({{ count($reminder->recipients) }})</h4>
                            <div class="rounded-lg bg-gray-50 p-3 max-h-32 overflow-y-auto">
                                <p class="font-mono text-xs text-gray-700">{{ implode(', ', $reminder->recipients) }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Logs -->
        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-base font-semibold text-gray-900">Log Pengiriman Terbaru</h3>
                @if ($recentLogs->isEmpty())
                    <p class="mt-4 text-sm text-gray-500">Belum ada log pengiriman. Reminder akan mulai mengirim sesuai
                        jadwal.</p>
                @else
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead>
                                <tr>
                                    <th scope="col"
                                        class="py-3.5 pe-3 ps-0 text-left text-sm font-semibold text-gray-900">Penerima</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        Kondisi</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        Dikirim Pada</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($recentLogs as $log)
                                    <tr>
                                        <td class="whitespace-nowrap py-4 pe-3 ps-0 text-sm font-medium text-gray-900">
                                            {{ $log->recipient }}</td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            {{ $log->condition_key }}</td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            {{ $log->sent_at?->format('d M Y H:i') ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
