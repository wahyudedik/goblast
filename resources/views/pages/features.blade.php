<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fitur - {{ config('app.name') }}</title>
    <meta name="description"
        content="Fitur lengkap Konektivitas untuk otomasi WhatsApp bisnis Anda. Broadcast, Reminder, Template, Auto Reply, dan API Integration.">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="antialiased bg-white">
    @include('partials.public-nav')

    <!-- Hero -->
    <section class="pt-24 pb-12 px-4 sm:px-6 lg:px-8 bg-linear-to-b from-green-50 to-white">
        <div class="max-w-7xl mx-auto text-center">
            <h1 class="text-4xl sm:text-5xl font-bold text-gray-900 mb-4">Fitur <span
                    class="text-green-600">Lengkap</span></h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">Semua yang Anda butuhkan untuk mengotomasi komunikasi
                WhatsApp bisnis dalam satu platform.</p>
        </div>
    </section>

    <!-- Broadcast -->
    <section class="py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z">
                            </path>
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">Broadcast Massal</h2>
                    <p class="text-gray-600 mb-6">Kirim pesan ke ribuan kontak sekaligus dengan sistem antrian cerdas.
                        Delay otomatis antar pesan membantu menghindari pemblokiran dari WhatsApp.</p>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-green-600 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-600">Kirim ke ribuan kontak dalam satu klik</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-green-600 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-600">Delay otomatis antar pesan untuk keamanan</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-green-600 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-600">Jadwalkan broadcast untuk waktu tertentu</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-green-600 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-600">Retry otomatis untuk pesan yang gagal</span>
                        </li>
                    </ul>
                </div>
                <div class="bg-gray-50 rounded-2xl p-8 border border-gray-200">
                    <div class="space-y-4">
                        <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-100">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center"><span
                                        class="text-green-600 text-sm font-bold">1</span></div>
                                <span class="font-medium text-gray-900">Pilih kontak atau grup</span>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-100">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center"><span
                                        class="text-green-600 text-sm font-bold">2</span></div>
                                <span class="font-medium text-gray-900">Tulis atau pilih template</span>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-100">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center"><span
                                        class="text-green-600 text-sm font-bold">3</span></div>
                                <span class="font-medium text-gray-900">Kirim atau jadwalkan</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Reminder -->
    <section class="py-16 px-4 sm:px-6 lg:px-8 bg-gray-50">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div class="order-2 lg:order-1 bg-white rounded-2xl p-8 border border-gray-200">
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 p-3 bg-green-50 rounded-lg">
                            <svg class="w-5 h-5 text-green-600 shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-sm text-gray-700">Reminder SPP - Setiap tanggal 1</span>
                        </div>
                        <div class="flex items-center gap-3 p-3 bg-blue-50 rounded-lg">
                            <svg class="w-5 h-5 text-blue-600 shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-sm text-gray-700">Reminder Invoice - H-3 jatuh tempo</span>
                        </div>
                        <div class="flex items-center gap-3 p-3 bg-purple-50 rounded-lg">
                            <svg class="w-5 h-5 text-purple-600 shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-sm text-gray-700">Reminder Booking - 1 jam sebelumnya</span>
                        </div>
                    </div>
                </div>
                <div class="order-1 lg:order-2">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">Reminder Otomatis</h2>
                    <p class="text-gray-600 mb-6">Atur pengingat otomatis untuk berbagai kebutuhan bisnis. Cocok untuk
                        tagihan SPP, invoice, jadwal booking, dan lainnya.</p>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-green-600 mt-0.5 shrink-0" fill="currentColor"
                                viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-600">Jadwal harian, mingguan, atau bulanan</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-green-600 mt-0.5 shrink-0" fill="currentColor"
                                viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-600">Variabel dinamis untuk personalisasi</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-green-600 mt-0.5 shrink-0" fill="currentColor"
                                viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-600">Log pengiriman lengkap</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Template, Auto Reply, Message Logs, API -->
    <section class="py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Fitur Lainnya</h2>
                <p class="text-lg text-gray-600">Tools tambahan untuk memaksimalkan otomasi WhatsApp Anda</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Template Pesan -->
                <div class="bg-white p-8 rounded-xl border border-gray-200 hover:shadow-lg transition">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Template Pesan</h3>
                    <p class="text-gray-600 mb-4">Buat dan kelola template pesan dengan variabel dinamis. Personalisasi
                        setiap pesan dengan nama, nomor invoice, tanggal, dan data lainnya.</p>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            Variabel dinamis (nama, nomor, tanggal, dll)
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            Duplikasi template dengan satu klik
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            Gunakan di broadcast dan reminder
                        </li>
                    </ul>
                </div>

                <!-- Auto Reply -->
                <div class="bg-white p-8 rounded-xl border border-gray-200 hover:shadow-lg transition">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Auto Reply</h3>
                    <p class="text-gray-600 mb-4">Balas pesan masuk secara otomatis berdasarkan kata kunci. Tingkatkan
                        kecepatan respon tanpa perlu memantau chat 24 jam.</p>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            Atur kata kunci dan balasan otomatis
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            Cooldown untuk mencegah spam
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            Aktifkan/nonaktifkan per aturan
                        </li>
                    </ul>
                </div>

                <!-- Message Logs -->
                <div class="bg-white p-8 rounded-xl border border-gray-200 hover:shadow-lg transition">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Laporan & Log Pesan</h3>
                    <p class="text-gray-600 mb-4">Pantau setiap pesan yang terkirim dengan log lengkap. Lihat status
                        pengiriman, retry pesan gagal, dan ekspor data.</p>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            Status real-time (terkirim, gagal, pending)
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            Retry pesan gagal dengan satu klik
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            Ekspor laporan ke file
                        </li>
                    </ul>
                </div>

                <!-- API Integration -->
                <div class="bg-white p-8 rounded-xl border border-gray-200 hover:shadow-lg transition">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">API Integration</h3>
                    <p class="text-gray-600 mb-4">Integrasikan Konektivitas dengan sistem Anda melalui REST API. Kirim
                        pesan WhatsApp langsung dari aplikasi, website, atau sistem ERP Anda.</p>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            REST API dengan dokumentasi lengkap
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            Token API untuk autentikasi aman
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            Webhook untuk notifikasi real-time
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="py-16 px-4 sm:px-6 lg:px-8 bg-green-600">
        <div class="max-w-4xl mx-auto text-center">
            <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">Siap Mencoba Semua Fitur?</h2>
            <p class="text-xl text-green-100 mb-8">Mulai trial gratis 14 hari dan akses semua fitur tanpa batasan.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('register') }}"
                    class="bg-white text-green-600 hover:bg-gray-100 px-8 py-3 rounded-lg text-lg font-semibold inline-block">Mulai
                    Trial Gratis</a>
                <a href="https://wa.me/6281529211963" target="_blank"
                    class="bg-green-700 text-white hover:bg-green-800 px-8 py-3 rounded-lg text-lg font-semibold inline-block">Hubungi
                    via WhatsApp</a>
            </div>
        </div>
    </section>

    @include('partials.public-footer')
</body>

</html>
