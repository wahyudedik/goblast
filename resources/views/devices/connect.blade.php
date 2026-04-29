@extends('layouts.app')

@section('page-title', 'Hubungkan Device')

@section('content')
    <!-- Header -->
    <div class="mb-6">
        <a href="{{ route('devices.index') }}"
            class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-900 mb-2">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Kembali ke Devices
        </a>
        <h2 class="text-2xl font-bold text-gray-900">Hubungkan Device: {{ $device->name }}</h2>
        <p class="mt-1 text-sm text-gray-600">
            Scan QR code di bawah dengan WhatsApp untuk menghubungkan device ini.
        </p>
    </div>

    <!-- Connection Status -->
    <div id="connection-status" class="mb-6 rounded-lg bg-white border border-gray-200 shadow-sm p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div id="status-indicator" class="w-3 h-3 rounded-full bg-yellow-500 animate-pulse"></div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Status Koneksi</h3>
                    <p id="status-text" class="text-sm text-gray-600">Menunggu scan QR code...</p>
                </div>
            </div>
            <div id="timer" class="text-sm text-gray-500">
                Sisa waktu: <span id="time-remaining" class="font-semibold">5:00</span>
            </div>
        </div>
    </div>

    <!-- QR Code Card -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-6">
            <div class="flex flex-col lg:flex-row items-center lg:items-start gap-8">
                <!-- QR Code Container -->
                <div class="shrink-0">
                    <div id="qr-container"
                        class="w-72 h-72 flex items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 overflow-hidden">

                        <!-- Loading State -->
                        <div id="qr-loading" class="text-center">
                            <svg class="mx-auto w-10 h-10 animate-spin text-green-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <p class="mt-3 text-sm text-gray-500">Memuat QR code...</p>
                        </div>

                        <!-- QR Code Image -->
                        <img id="qr-code" src="" alt="QR Code" class="hidden w-full h-full object-contain p-2">

                        <!-- Error State -->
                        <div id="qr-error" class="hidden text-center px-4">
                            <svg class="mx-auto w-12 h-12 text-red-400" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                            <p class="mt-2 text-sm font-medium text-red-600">Gagal memuat QR code</p>
                            <p id="qr-error-message" class="mt-1 text-xs text-red-500"></p>
                            <button onclick="fetchQrCode()"
                                class="mt-3 px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition-colors">
                                Coba Lagi
                            </button>
                        </div>

                        <!-- Timeout State -->
                        <div id="qr-timeout" class="hidden text-center px-4">
                            <svg class="mx-auto w-12 h-12 text-gray-400" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="mt-2 text-sm font-medium text-gray-700">QR code kedaluwarsa</p>
                            <button onclick="window.location.reload()"
                                class="mt-3 px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition-colors">
                                Buat QR Code Baru
                            </button>
                        </div>

                        <!-- Success State -->
                        <div id="qr-success" class="hidden text-center px-4">
                            <svg class="mx-auto w-16 h-16 text-green-500" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="mt-3 text-sm font-semibold text-green-700">Terhubung!</p>
                            <p class="mt-1 text-xs text-gray-500">Mengalihkan...</p>
                        </div>
                    </div>
                </div>

                <!-- Instructions -->
                <div class="flex-1 space-y-6">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 mb-3">Cara Menghubungkan:</h3>
                        <ol class="space-y-3">
                            <li class="flex items-start gap-3">
                                <span
                                    class="shrink-0 w-7 h-7 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-sm font-bold">1</span>
                                <span class="text-sm text-gray-700 pt-1">Buka <strong>WhatsApp</strong> di HP Anda</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span
                                    class="shrink-0 w-7 h-7 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-sm font-bold">2</span>
                                <span class="text-sm text-gray-700 pt-1">Ketuk <strong>Menu</strong> atau
                                    <strong>Setelan</strong> lalu pilih <strong>Perangkat Tertaut</strong></span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span
                                    class="shrink-0 w-7 h-7 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-sm font-bold">3</span>
                                <span class="text-sm text-gray-700 pt-1">Ketuk <strong>Tautkan Perangkat</strong></span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span
                                    class="shrink-0 w-7 h-7 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-sm font-bold">4</span>
                                <span class="text-sm text-gray-700 pt-1">Arahkan HP Anda ke layar ini untuk <strong>scan QR
                                        code</strong></span>
                            </li>
                        </ol>
                    </div>

                    <!-- Info Box -->
                    <div class="rounded-lg border-2 border-blue-200 bg-blue-50 p-4">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z"
                                    clip-rule="evenodd" />
                            </svg>
                            <div>
                                <p class="text-sm text-blue-800">
                                    QR code akan di-refresh otomatis setiap <strong>20 detik</strong> dan kedaluwarsa
                                    setelah <strong>5 menit</strong>.
                                    Pastikan HP Anda terhubung ke internet.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            const qrCodeUrl = '{{ route('devices.qr-code', $device) }}';
            const checkStatusUrl = '{{ route('devices.check-status', $device) }}';
            const deviceShowUrl = '{{ route('devices.show', $device) }}';

            let statusInterval;
            let qrRefreshInterval;
            let countdownInterval;
            let timeoutTimer;
            let remainingSeconds = 300;

            // DOM elements
            const qrLoading = document.getElementById('qr-loading');
            const qrCode = document.getElementById('qr-code');
            const qrError = document.getElementById('qr-error');
            const qrTimeout = document.getElementById('qr-timeout');
            const qrSuccess = document.getElementById('qr-success');
            const statusIndicator = document.getElementById('status-indicator');
            const statusText = document.getElementById('status-text');
            const qrErrorMessage = document.getElementById('qr-error-message');

            function hideAllStates() {
                qrLoading.classList.add('hidden');
                qrCode.classList.add('hidden');
                qrError.classList.add('hidden');
                qrTimeout.classList.add('hidden');
                qrSuccess.classList.add('hidden');
            }

            // Fetch QR code from server
            async function fetchQrCode() {
                hideAllStates();
                qrLoading.classList.remove('hidden');

                try {
                    const response = await fetch(qrCodeUrl);
                    const data = await response.json();

                    if (data.success && data.qr_code) {
                        hideAllStates();
                        qrCode.src = data.qr_code;
                        qrCode.classList.remove('hidden');
                    } else {
                        hideAllStates();
                        qrError.classList.remove('hidden');
                        qrErrorMessage.textContent = data.error || 'QR code tidak tersedia';
                    }
                } catch (error) {
                    hideAllStates();
                    qrError.classList.remove('hidden');
                    qrErrorMessage.textContent = 'Tidak dapat terhubung ke server';
                    console.error('Failed to fetch QR code:', error);
                }
            }

            // Check connection status
            async function checkConnectionStatus() {
                try {
                    const response = await fetch(checkStatusUrl);
                    const data = await response.json();

                    if (data.success && data.status === 'connected') {
                        clearAllIntervals();
                        showSuccess();
                        setTimeout(() => {
                            window.location.href = deviceShowUrl;
                        }, 2000);
                    }
                } catch (error) {
                    console.error('Failed to check status:', error);
                }
            }

            function showSuccess() {
                hideAllStates();
                qrSuccess.classList.remove('hidden');
                statusIndicator.classList.remove('bg-yellow-500', 'animate-pulse');
                statusIndicator.classList.add('bg-green-500');
                statusText.textContent = 'Device berhasil terhubung! Mengalihkan...';
            }

            function showTimeout() {
                clearAllIntervals();
                hideAllStates();
                qrTimeout.classList.remove('hidden');
                statusIndicator.classList.remove('bg-yellow-500', 'animate-pulse');
                statusIndicator.classList.add('bg-red-500');
                statusText.textContent = 'Waktu habis. Silakan coba lagi.';
            }

            function updateCountdown() {
                remainingSeconds--;
                if (remainingSeconds <= 0) {
                    showTimeout();
                    return;
                }
                const minutes = Math.floor(remainingSeconds / 60);
                const seconds = remainingSeconds % 60;
                document.getElementById('time-remaining').textContent =
                    `${minutes}:${seconds.toString().padStart(2, '0')}`;
            }

            function clearAllIntervals() {
                clearInterval(statusInterval);
                clearInterval(qrRefreshInterval);
                clearInterval(countdownInterval);
                clearTimeout(timeoutTimer);
            }

            // Initialize
            document.addEventListener('DOMContentLoaded', function() {
                // Fetch QR code immediately
                fetchQrCode();

                // Refresh QR code every 20 seconds
                qrRefreshInterval = setInterval(fetchQrCode, 20000);

                // Check connection status every 3 seconds
                statusInterval = setInterval(checkConnectionStatus, 3000);

                // Countdown timer
                countdownInterval = setInterval(updateCountdown, 1000);

                // Timeout after 5 minutes
                timeoutTimer = setTimeout(showTimeout, 300000);
            });
        </script>
    @endpush
@endsection
