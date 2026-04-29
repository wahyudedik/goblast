<!-- Navigation -->
<nav class="bg-white border-b border-gray-200 fixed w-full z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <a href="/" class="flex items-center gap-2">
                    <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" class="h-8 w-auto">
                    <span class="text-xl font-bold text-green-600">Konektivitas</span>
                </a>
            </div>

            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center gap-1">
                <a href="{{ route('pages.features') }}"
                    class="text-gray-700 hover:text-green-600 px-3 py-2 rounded-md text-sm font-medium">Fitur</a>
                <a href="/#pricing"
                    class="text-gray-700 hover:text-green-600 px-3 py-2 rounded-md text-sm font-medium">Harga</a>
                <a href="{{ route('pages.faq') }}"
                    class="text-gray-700 hover:text-green-600 px-3 py-2 rounded-md text-sm font-medium">FAQ</a>
                <a href="{{ route('pages.contact') }}"
                    class="text-gray-700 hover:text-green-600 px-3 py-2 rounded-md text-sm font-medium">Kontak</a>
            </div>

            <div class="flex items-center gap-2">
                @auth
                    @if (auth()->user()->role === 'superadmin')
                        <a href="{{ route('admin.dashboard') }}"
                            class="text-gray-700 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                    @else
                        <a href="{{ route('dashboard') }}"
                            class="text-gray-700 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                    @endif
                @else
                    <a href="{{ route('login') }}"
                        class="text-gray-700 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Login</a>
                    <a href="{{ route('register') }}"
                        class="bg-green-600 text-white hover:bg-green-700 px-4 py-2 rounded-md text-sm font-medium">Daftar
                        Gratis</a>
                @endauth

                <!-- Mobile menu button -->
                <button type="button"
                    class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-700 hover:text-green-600 hover:bg-gray-100"
                    x-data x-on:click="$dispatch('toggle-mobile-nav')">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-data="{ open: false }" x-on:toggle-mobile-nav.window="open = !open" x-show="open" x-cloak
            class="md:hidden border-t border-gray-200 py-2">
            <a href="{{ route('pages.features') }}"
                class="block px-3 py-2 text-gray-700 hover:text-green-600 text-sm font-medium">Fitur</a>
            <a href="/#pricing" class="block px-3 py-2 text-gray-700 hover:text-green-600 text-sm font-medium">Harga</a>
            <a href="{{ route('pages.faq') }}"
                class="block px-3 py-2 text-gray-700 hover:text-green-600 text-sm font-medium">FAQ</a>
            <a href="{{ route('pages.contact') }}"
                class="block px-3 py-2 text-gray-700 hover:text-green-600 text-sm font-medium">Kontak</a>
        </div>
    </div>
</nav>
