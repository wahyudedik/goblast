<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - Otomasi WhatsApp untuk Bisnis Anda</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="antialiased bg-white">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200 fixed w-full z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex items-center gap-2">
                        <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" class="h-8 w-auto">
                        <span class="text-xl font-bold text-green-600">Konektivitas</span>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    @auth
                        <a href="{{ route('devices.index') }}"
                            class="text-gray-700 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}"
                            class="text-gray-700 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Login</a>
                        <a href="{{ route('register') }}"
                            class="bg-green-600 text-white hover:bg-green-700 px-4 py-2 rounded-md text-sm font-medium">Daftar
                            Gratis</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="pt-24 pb-16 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-green-50 to-white">
        <div class="max-w-7xl mx-auto">
            <div class="text-center">
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 mb-6">
                    Otomasi WhatsApp untuk<br>
                    <span class="text-green-600">Bisnis Anda</span>
                </h1>
                <p class="text-xl text-gray-600 mb-8 max-w-3xl mx-auto">
                    Kirim pesan WhatsApp otomatis, terjadwal, dan massal dengan mudah. Tingkatkan efisiensi komunikasi
                    bisnis Anda dengan WA Automation.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('register') }}"
                        class="bg-green-600 text-white hover:bg-green-700 px-8 py-3 rounded-lg text-lg font-semibold inline-block">
                        Mulai Trial Gratis 14 Hari
                    </a>
                    <a href="https://wa.me/6281529211963" target="_blank"
                        class="bg-white text-green-600 border-2 border-green-600 hover:bg-green-50 px-8 py-3 rounded-lg text-lg font-semibold inline-block">
                        Hubungi Kami
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">Fitur Unggulan</h2>
                <p class="text-lg text-gray-600">Semua yang Anda butuhkan untuk otomasi WhatsApp bisnis</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="bg-white p-6 rounded-lg border border-gray-200 hover:shadow-lg transition">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Broadcast Massal</h3>
                    <p class="text-gray-600">Kirim pesan ke ribuan kontak sekaligus dengan delay otomatis untuk
                        menghindari pemblokiran.</p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-white p-6 rounded-lg border border-gray-200 hover:shadow-lg transition">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Reminder Otomatis</h3>
                    <p class="text-gray-600">Atur pengingat otomatis untuk SPP, invoice, atau jadwal booking tanpa perlu
                        intervensi manual.</p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-white p-6 rounded-lg border border-gray-200 hover:shadow-lg transition">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Template Pesan</h3>
                    <p class="text-gray-600">Buat template pesan dengan variabel dinamis untuk personalisasi pesan yang
                        lebih baik.</p>
                </div>

                <!-- Feature 4 -->
                <div class="bg-white p-6 rounded-lg border border-gray-200 hover:shadow-lg transition">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Auto Reply</h3>
                    <p class="text-gray-600">Balas pesan masuk secara otomatis berdasarkan kata kunci tertentu untuk
                        respon yang lebih cepat.</p>
                </div>

                <!-- Feature 5 -->
                <div class="bg-white p-6 rounded-lg border border-gray-200 hover:shadow-lg transition">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Laporan Lengkap</h3>
                    <p class="text-gray-600">Pantau status pengiriman pesan dengan log lengkap dan statistik real-time.
                    </p>
                </div>

                <!-- Feature 6 -->
                <div class="bg-white p-6 rounded-lg border border-gray-200 hover:shadow-lg transition">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">API Integration</h3>
                    <p class="text-gray-600">Integrasikan dengan sistem Anda menggunakan API yang mudah digunakan
                        (paket Business).</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="py-16 px-4 sm:px-6 lg:px-8 bg-gray-50">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">Paket Langganan</h2>
                <p class="text-lg text-gray-600">Pilih paket yang sesuai dengan kebutuhan bisnis Anda</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-{{ min($plans->count(), 4) }} gap-8 max-w-6xl mx-auto">
                @foreach ($plans as $plan)
                    <div
                        class="bg-white rounded-lg border-2 {{ $loop->index === 1 ? 'border-green-600' : 'border-gray-200' }} p-8 relative">
                        @if ($loop->index === 1)
                            <div
                                class="absolute top-0 right-0 bg-green-600 text-white px-3 py-1 text-sm font-semibold rounded-bl-lg rounded-tr-lg">
                                Populer</div>
                        @endif
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $plan->name }}</h3>
                        <div class="mb-6">
                            <span class="text-4xl font-bold text-gray-900">Rp
                                {{ number_format($plan->price / 1000, 0) }}K</span>
                            <span class="text-gray-600">/bulan</span>
                        </div>
                        <ul class="space-y-3 mb-8">
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-600 mr-2 mt-0.5 shrink-0" fill="currentColor"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd"></path>
                                </svg>
                                <span
                                    class="text-gray-600">{{ $plan->message_quota ? number_format($plan->message_quota) . ' pesan/bulan' : 'Pesan Unlimited' }}</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-600 mr-2 mt-0.5 shrink-0" fill="currentColor"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd"></path>
                                </svg>
                                <span
                                    class="text-gray-600">{{ $plan->has_multi_device ? 'Multi Device' : $plan->max_devices . ' Device' }}</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-600 mr-2 mt-0.5 shrink-0" fill="currentColor"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-gray-600">Broadcast & Template</span>
                            </li>
                            @if ($plan->has_reminder)
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-green-600 mr-2 mt-0.5 shrink-0" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-gray-600">Reminder & Auto Reply</span>
                                </li>
                            @endif
                            @if ($plan->has_api)
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-green-600 mr-2 mt-0.5 shrink-0" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-gray-600">API Integration</span>
                                </li>
                            @endif
                        </ul>
                        <a href="https://wa.me/6281529211963?text=Halo,%20saya%20ingin%20berlangganan%20paket%20{{ urlencode($plan->name) }}"
                            target="_blank"
                            class="block w-full {{ $loop->index === 1 ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-900 hover:bg-gray-800' }} text-white text-center py-3 rounded-lg font-semibold transition">
                            Pesan Sekarang
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 px-4 sm:px-6 lg:px-8 bg-green-600">
        <div class="max-w-4xl mx-auto text-center">
            <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">
                Siap Meningkatkan Efisiensi Bisnis Anda?
            </h2>
            <p class="text-xl text-green-100 mb-8">
                Mulai trial gratis 14 hari tanpa perlu kartu kredit. Batalkan kapan saja.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('register') }}"
                    class="bg-white text-green-600 hover:bg-gray-100 px-8 py-3 rounded-lg text-lg font-semibold inline-block">
                    Mulai Trial Gratis
                </a>
                <a href="https://wa.me/6281529211963" target="_blank"
                    class="bg-green-700 text-white hover:bg-green-800 px-8 py-3 rounded-lg text-lg font-semibold inline-block">
                    Hubungi via WhatsApp
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-white text-lg font-semibold mb-4">{{ config('app.name') }}</h3>
                    <p class="text-sm">Solusi otomasi WhatsApp terpercaya untuk bisnis Anda.</p>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-4">Produk</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white">Fitur</a></li>
                        <li><a href="#" class="hover:text-white">Harga</a></li>
                        <li><a href="#" class="hover:text-white">API</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-4">Perusahaan</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white">Tentang Kami</a></li>
                        <li><a href="#" class="hover:text-white">Kontak</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-4">Dukungan</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white">Dokumentasi</a></li>
                        <li><a href="#" class="hover:text-white">FAQ</a></li>
                        <li><a href="https://wa.me/6281529211963" target="_blank" class="hover:text-white">WhatsApp
                                Support</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-sm">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved. Develop by <b>Noteds Technology</b></p>
            </div>
        </div>
    </footer>
</body>

</html>
