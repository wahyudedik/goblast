<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen flex flex-col lg:flex-row">
        <!-- Left Side - Form -->
        <div class="flex-1 flex items-center justify-center px-4 sm:px-6 lg:px-8 bg-white py-12 lg:py-0">
            <div class="w-full max-w-md">
                <!-- Logo & Back to Home -->
                <div class="text-center mb-8">
                    <a href="/" class="inline-block group">
                        <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" class="h-10 w-auto mx-auto">
                        <span class="block text-2xl font-bold text-green-600 mt-2">Konektivitas</span>
                    </a>
                </div>

                <!-- Title & Subtitle -->
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-bold text-gray-900">
                        {{ $title ?? 'Welcome' }}
                    </h2>
                    @isset($subtitle)
                        <p class="mt-2 text-sm text-gray-600">
                            {{ $subtitle }}
                        </p>
                    @endisset
                </div>

                <!-- Form Content -->
                <div>
                    {{ $slot }}
                </div>
            </div>
        </div>

        <!-- Right Side - Decorative -->
        <div class="hidden lg:flex lg:flex-1 bg-linear-to-br from-green-600 to-green-700 relative overflow-hidden">
            <div
                class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmZmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNSI+PHBhdGggZD0iTTM2IDE0YzMuMzEgMCA2LTIuNjkgNi02cy0yLjY5LTYtNi02LTYgMi42OS02IDYgMi42OSA2IDYgNnptLTEyIDZjMi4yMSAwIDQtMS43OSA0LTRzLTEuNzktNC00LTQtNCAxLjc5LTQgNCAxLjc5IDQgNCA0em0yNCAyNGMzLjMxIDAgNi0yLjY5IDYtNnMtMi42OS02LTYtNi02IDIuNjktNiA2IDIuNjkgNiA2IDZ6Ii8+PC9nPjwvZz48L3N2Zz4=')] opacity-20">
            </div>
            <div class="relative z-10 flex flex-col justify-center px-12 text-white">
                <div class="mb-8">
                    <h1 class="text-4xl font-bold mb-4">
                        Otomasi WhatsApp untuk Bisnis Anda
                    </h1>
                    <p class="text-lg text-green-50">
                        Kelola pesan WhatsApp bisnis dengan mudah dan efisien. Tingkatkan produktivitas tim Anda.
                    </p>
                </div>

                <ul class="space-y-4">
                    <li class="flex items-start gap-3">
                        <div class="w-6 h-6 bg-white/20 rounded-full flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="text-green-50">Kirim pesan broadcast ke ribuan kontak sekaligus</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <div class="w-6 h-6 bg-white/20 rounded-full flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="text-green-50">Auto-reply otomatis dengan keyword rules</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <div class="w-6 h-6 bg-white/20 rounded-full flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="text-green-50">Template pesan untuk efisiensi maksimal</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <div class="w-6 h-6 bg-white/20 rounded-full flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="text-green-50">Multi-device support dengan API yang powerful</span>
                    </li>
                </ul>

                <div class="mt-12 p-6 bg-white/10 backdrop-blur-sm rounded-lg border border-white/20">
                    <p class="text-sm text-green-50 italic">
                        "Platform ini sangat membantu bisnis kami dalam mengelola komunikasi dengan pelanggan. Sangat
                        direkomendasikan!"
                    </p>
                    <p class="text-sm text-white font-semibold mt-2">
                        - Pengguna WA Automation
                    </p>
                </div>
            </div>

            <!-- Decorative circles -->
            <div class="absolute top-20 right-20 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-20 left-20 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
        </div>
    </div>
</body>

</html>
