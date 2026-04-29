<x-guest-layout>
    <x-slot name="title">
        Masuk ke Akun Anda
    </x-slot>
    <x-slot name="subtitle">
        Selamat datang kembali! Silakan masuk untuk melanjutkan
    </x-slot>

    <!-- Session Status -->
    <x-auth-session-status class="mb-6" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-2 w-full" type="email" name="email" :value="old('email')" required
                autofocus autocomplete="username" placeholder="nama@email.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between mb-2">
                <x-input-label for="password" :value="__('Password')" />
                @if (Route::has('password.request'))
                    <a class="text-sm text-green-600 hover:text-green-700 font-medium transition"
                        href="{{ route('password.request') }}">
                        Lupa password?
                    </a>
                @endif
            </div>

            <x-text-input id="password" class="block w-full" type="password" name="password" required
                autocomplete="current-password" placeholder="Masukkan password Anda" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center">
            <input id="remember_me" type="checkbox"
                class="h-4 w-4 rounded border-gray-300 text-green-600 focus:ring-green-500" name="remember">
            <label for="remember_me" class="ml-2 block text-sm text-gray-700">
                Ingat saya
            </label>
        </div>

        <div class="pt-1">
            <x-primary-button class="w-full justify-center">
                Masuk
            </x-primary-button>
        </div>

        <div class="relative">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-200"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-3 bg-white text-gray-500">Belum punya akun?</span>
            </div>
        </div>

        <div>
            <a href="{{ route('register') }}"
                class="inline-flex items-center justify-center w-full px-4 py-2.5 border-2 border-green-600 rounded-lg text-sm font-semibold text-green-600 bg-white hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all">
                Daftar Sekarang
            </a>
        </div>
    </form>
</x-guest-layout>
