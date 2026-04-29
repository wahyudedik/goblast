<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tentang Kami - {{ config('app.name') }}</title>
    <meta name="description"
        content="Tentang Konektivitas dan Noteds Technology. Misi kami membantu bisnis mengotomasi komunikasi WhatsApp.">
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
            <h1 class="text-4xl sm:text-5xl font-bold text-gray-900 mb-4">Tentang <span
                    class="text-green-600">Kami</span></h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">Mengenal lebih dekat Konektivitas dan tim di baliknya.
            </p>
        </div>
    </section>

    <!-- About Company -->
    <section class="py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <div class="prose prose-lg max-w-none">
                <h2 class="text-3xl font-bold text-gray-900 mb-6">Noteds Technology</h2>
                <p class="text-gray-600 mb-6">
                    Konektivitas adalah produk dari <strong>Noteds Technology</strong>, perusahaan teknologi yang
                    berbasis di Indonesia.
                    Kami berfokus pada pengembangan solusi digital yang membantu bisnis berkembang melalui otomasi dan
                    efisiensi.
                </p>
                <p class="text-gray-600 mb-6">
                    Berawal dari kebutuhan banyak bisnis di Indonesia untuk berkomunikasi dengan pelanggan secara
                    efektif melalui WhatsApp,
                    kami membangun Konektivitas sebagai platform otomasi WhatsApp yang mudah digunakan, terjangkau, dan
                    andal.
                </p>
            </div>
        </div>
    </section>

    <!-- Mission & Vision -->
    <section class="py-16 px-4 sm:px-6 lg:px-8 bg-gray-50">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <div class="bg-white p-8 rounded-xl border border-gray-200">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Misi Kami</h3>
                    <p class="text-gray-600">
                        Membantu bisnis di Indonesia mengotomasi komunikasi WhatsApp mereka sehingga dapat fokus pada
                        hal yang lebih penting:
                        mengembangkan bisnis dan melayani pelanggan dengan lebih baik.
                    </p>
                </div>
                <div class="bg-white p-8 rounded-xl border border-gray-200">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Visi Kami</h3>
                    <p class="text-gray-600">
                        Menjadi platform otomasi WhatsApp terdepan di Indonesia yang dipercaya oleh ribuan bisnis
                        untuk mengelola komunikasi pelanggan mereka secara efisien dan profesional.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Values -->
    <section class="py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <h2 class="text-3xl font-bold text-gray-900 text-center mb-12">Nilai-Nilai Kami</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Keamanan</h3>
                    <p class="text-gray-600">Data pelanggan Anda adalah prioritas kami. Kami menerapkan standar keamanan
                        tinggi untuk melindungi setiap informasi.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Inovasi</h3>
                    <p class="text-gray-600">Kami terus mengembangkan fitur baru dan meningkatkan platform untuk
                        memberikan pengalaman terbaik bagi pengguna.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Dukungan</h3>
                    <p class="text-gray-600">Tim support kami siap membantu Anda kapan saja melalui WhatsApp. Kami
                        percaya layanan pelanggan yang baik adalah kunci kesuksesan.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="py-16 px-4 sm:px-6 lg:px-8 bg-green-600">
        <div class="max-w-4xl mx-auto text-center">
            <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">Bergabung Bersama Kami</h2>
            <p class="text-xl text-green-100 mb-8">Mulai otomasi WhatsApp bisnis Anda hari ini dengan trial gratis 14
                hari.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('register') }}"
                    class="bg-white text-green-600 hover:bg-gray-100 px-8 py-3 rounded-lg text-lg font-semibold inline-block">Mulai
                    Trial Gratis</a>
                <a href="{{ route('pages.contact') }}"
                    class="bg-green-700 text-white hover:bg-green-800 px-8 py-3 rounded-lg text-lg font-semibold inline-block">Hubungi
                    Kami</a>
            </div>
        </div>
    </section>

    @include('partials.public-footer')
</body>

</html>
