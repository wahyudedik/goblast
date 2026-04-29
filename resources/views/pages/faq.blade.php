<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FAQ - {{ config('app.name') }}</title>
    <meta name="description"
        content="Pertanyaan yang sering diajukan tentang Konektivitas. Temukan jawaban seputar trial, pembayaran, fitur, dan lainnya.">
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
            <h1 class="text-4xl sm:text-5xl font-bold text-gray-900 mb-4">Pertanyaan <span
                    class="text-green-600">Umum</span></h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">Temukan jawaban untuk pertanyaan yang sering diajukan
                tentang Konektivitas.</p>
        </div>
    </section>

    <!-- FAQ -->
    <section class="py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto" x-data="{ active: null }">
            <div class="space-y-4">
                <!-- Trial -->
                <div class="border border-gray-200 rounded-lg">
                    <button x-on:click="active = active === 1 ? null : 1"
                        class="w-full flex items-center justify-between p-5 text-left">
                        <span class="font-semibold text-gray-900">Apakah ada trial gratis?</span>
                        <svg class="w-5 h-5 text-gray-500 transition-transform" :class="{ 'rotate-180': active === 1 }"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>
                    <div x-show="active === 1" x-collapse class="px-5 pb-5">
                        <p class="text-gray-600">Ya, kami menyediakan trial gratis selama 14 hari untuk semua pengguna
                            baru. Anda bisa mengakses semua fitur tanpa perlu memasukkan informasi pembayaran. Setelah
                            masa trial berakhir, Anda bisa memilih paket yang sesuai untuk melanjutkan.</p>
                    </div>
                </div>

                <!-- Payment -->
                <div class="border border-gray-200 rounded-lg">
                    <button x-on:click="active = active === 2 ? null : 2"
                        class="w-full flex items-center justify-between p-5 text-left">
                        <span class="font-semibold text-gray-900">Bagaimana cara pembayaran?</span>
                        <svg class="w-5 h-5 text-gray-500 transition-transform" :class="{ 'rotate-180': active === 2 }"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>
                    <div x-show="active === 2" x-collapse class="px-5 pb-5">
                        <p class="text-gray-600">Pembayaran dilakukan melalui transfer bank. Setelah memilih paket, Anda
                            akan menerima invoice dengan detail pembayaran. Konfirmasi pembayaran bisa dilakukan melalui
                            WhatsApp ke tim kami, dan akun Anda akan segera diaktifkan.</p>
                    </div>
                </div>

                <!-- Device Connection -->
                <div class="border border-gray-200 rounded-lg">
                    <button x-on:click="active = active === 3 ? null : 3"
                        class="w-full flex items-center justify-between p-5 text-left">
                        <span class="font-semibold text-gray-900">Bagaimana cara menghubungkan perangkat
                            WhatsApp?</span>
                        <svg class="w-5 h-5 text-gray-500 transition-transform" :class="{ 'rotate-180': active === 3 }"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>
                    <div x-show="active === 3" x-collapse class="px-5 pb-5">
                        <p class="text-gray-600">Setelah mendaftar, buat device baru di dashboard lalu scan QR code yang
                            muncul menggunakan WhatsApp di ponsel Anda (WhatsApp &gt; Perangkat Tertaut &gt; Tautkan
                            Perangkat). Proses koneksi hanya membutuhkan beberapa detik dan perangkat Anda akan langsung
                            terhubung.</p>
                    </div>
                </div>

                <!-- Message Limits -->
                <div class="border border-gray-200 rounded-lg">
                    <button x-on:click="active = active === 4 ? null : 4"
                        class="w-full flex items-center justify-between p-5 text-left">
                        <span class="font-semibold text-gray-900">Berapa batas pesan yang bisa dikirim?</span>
                        <svg class="w-5 h-5 text-gray-500 transition-transform" :class="{ 'rotate-180': active === 4 }"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>
                    <div x-show="active === 4" x-collapse class="px-5 pb-5">
                        <p class="text-gray-600">Batas pesan tergantung pada paket yang Anda pilih. Setiap paket
                            memiliki kuota pesan bulanan yang berbeda. Beberapa paket bahkan menawarkan pesan unlimited.
                            Anda bisa melihat detail kuota di halaman <a href="/#pricing"
                                class="text-green-600 hover:text-green-700 underline">harga</a>.</p>
                    </div>
                </div>

                <!-- API -->
                <div class="border border-gray-200 rounded-lg">
                    <button x-on:click="active = active === 5 ? null : 5"
                        class="w-full flex items-center justify-between p-5 text-left">
                        <span class="font-semibold text-gray-900">Apakah tersedia API untuk integrasi?</span>
                        <svg class="w-5 h-5 text-gray-500 transition-transform" :class="{ 'rotate-180': active === 5 }"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>
                    <div x-show="active === 5" x-collapse class="px-5 pb-5">
                        <p class="text-gray-600">Ya, fitur API tersedia pada paket Business. Anda bisa mengirim pesan
                            WhatsApp langsung dari aplikasi, website, atau sistem ERP Anda melalui REST API kami. Setiap
                            akun API dilengkapi dengan token autentikasi dan dokumentasi lengkap.</p>
                    </div>
                </div>

                <!-- Data Security -->
                <div class="border border-gray-200 rounded-lg">
                    <button x-on:click="active = active === 6 ? null : 6"
                        class="w-full flex items-center justify-between p-5 text-left">
                        <span class="font-semibold text-gray-900">Bagaimana keamanan data saya?</span>
                        <svg class="w-5 h-5 text-gray-500 transition-transform" :class="{ 'rotate-180': active === 6 }"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>
                    <div x-show="active === 6" x-collapse class="px-5 pb-5">
                        <p class="text-gray-600">Keamanan data adalah prioritas utama kami. Semua data disimpan di
                            server yang aman dengan enkripsi. Kami tidak menyimpan isi pesan WhatsApp Anda secara
                            permanen dan hanya menyimpan log pengiriman untuk keperluan monitoring. Baca selengkapnya di
                            <a href="{{ route('pages.privacy') }}"
                                class="text-green-600 hover:text-green-700 underline">Kebijakan Privasi</a> kami.</p>
                    </div>
                </div>

                <!-- Cancellation -->
                <div class="border border-gray-200 rounded-lg">
                    <button x-on:click="active = active === 7 ? null : 7"
                        class="w-full flex items-center justify-between p-5 text-left">
                        <span class="font-semibold text-gray-900">Bagaimana cara membatalkan langganan?</span>
                        <svg class="w-5 h-5 text-gray-500 transition-transform" :class="{ 'rotate-180': active === 7 }"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>
                    <div x-show="active === 7" x-collapse class="px-5 pb-5">
                        <p class="text-gray-600">Anda bisa membatalkan langganan kapan saja dengan menghubungi tim kami
                            melalui WhatsApp atau email. Tidak ada biaya pembatalan. Akun Anda akan tetap aktif hingga
                            akhir periode langganan yang sudah dibayar.</p>
                    </div>
                </div>

                <!-- Multi Device -->
                <div class="border border-gray-200 rounded-lg">
                    <button x-on:click="active = active === 8 ? null : 8"
                        class="w-full flex items-center justify-between p-5 text-left">
                        <span class="font-semibold text-gray-900">Bisakah saya menghubungkan lebih dari satu nomor
                            WhatsApp?</span>
                        <svg class="w-5 h-5 text-gray-500 transition-transform" :class="{ 'rotate-180': active === 8 }"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>
                    <div x-show="active === 8" x-collapse class="px-5 pb-5">
                        <p class="text-gray-600">Ya, jumlah device yang bisa dihubungkan tergantung pada paket Anda.
                            Paket dasar mendukung 1 device, sementara paket yang lebih tinggi mendukung multi-device.
                            Setiap device bisa menggunakan nomor WhatsApp yang berbeda.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="py-16 px-4 sm:px-6 lg:px-8 bg-gray-50">
        <div class="max-w-4xl mx-auto text-center">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Masih punya pertanyaan?</h2>
            <p class="text-gray-600 mb-8">Jangan ragu untuk menghubungi tim kami. Kami siap membantu Anda.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('pages.contact') }}"
                    class="bg-green-600 text-white hover:bg-green-700 px-6 py-3 rounded-lg font-semibold inline-block">Hubungi
                    Kami</a>
                <a href="https://wa.me/6281529211963" target="_blank"
                    class="bg-white text-green-600 border-2 border-green-600 hover:bg-green-50 px-6 py-3 rounded-lg font-semibold inline-block">Chat
                    WhatsApp</a>
            </div>
        </div>
    </section>

    @include('partials.public-footer')
</body>

</html>
