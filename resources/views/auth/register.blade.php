<x-guest-layout>
    <x-slot name="title">
        Buat Akun Baru
    </x-slot>
    <x-slot name="subtitle">
        Mulai otomasi WhatsApp bisnis Anda hari ini
    </x-slot>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Nama Lengkap')" />
            <x-text-input id="name" class="block mt-2 w-full" type="text" name="name" :value="old('name')" required
                autofocus autocomplete="name" placeholder="Masukkan nama lengkap Anda" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-2 w-full" type="email" name="email" :value="old('email')"
                required autocomplete="username" placeholder="nama@email.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Phone -->
        <div>
            <x-input-label for="phone" :value="__('Nomor Telepon')" />
            <x-text-input id="phone" class="block mt-2 w-full" type="text" name="phone" :value="old('phone')"
                required autocomplete="tel" placeholder="628123456789" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            <p class="mt-1.5 text-xs text-gray-500">Format: 628123456789 (tanpa tanda +)</p>
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-2 w-full" type="password" name="password" required
                autocomplete="new-password" placeholder="Minimal 8 karakter" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" :value="__('Konfirmasi Password')" />
            <x-text-input id="password_confirmation" class="block mt-2 w-full" type="password"
                name="password_confirmation" required autocomplete="new-password" placeholder="Ulangi password Anda" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Terms and Conditions -->
        <div>
            <label for="terms" class="flex items-start">
                <input id="terms" type="checkbox"
                    class="h-4 w-4 rounded border-gray-300 text-green-600 focus:ring-green-500 mt-0.5 shrink-0"
                    name="terms" required>
                <span class="ml-2 text-sm text-gray-700">
                    Saya setuju dengan <a href="#" class="text-green-600 hover:text-green-700 font-medium">Syarat
                        dan Ketentuan</a> serta <a href="#"
                        class="text-green-600 hover:text-green-700 font-medium">Kebijakan Privasi</a>
                </span>
            </label>
            <x-input-error :messages="$errors->get('terms')" class="mt-2" />
        </div>

        <div class="pt-1">
            <x-primary-button class="w-full justify-center">
                Daftar Sekarang
            </x-primary-button>
        </div>

        @if (config('services.google.client_id'))
            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-200"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-3 bg-white text-gray-500">Atau</span>
                </div>
            </div>

            <div>
                <a href="{{ route('auth.google.redirect') }}"
                    class="inline-flex items-center justify-center w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all gap-3">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                            fill="#4285F4" />
                        <path
                            d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                            fill="#34A853" />
                        <path
                            d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                            fill="#FBBC05" />
                        <path
                            d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                            fill="#EA4335" />
                    </svg>
                    Daftar dengan Google
                </a>
            </div>
        @endif

        <div class="relative">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-200"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-3 bg-white text-gray-500">Sudah punya akun?</span>
            </div>
        </div>

        <div>
            <a href="{{ route('login') }}"
                class="inline-flex items-center justify-center w-full px-4 py-2.5 border-2 border-green-600 rounded-lg text-sm font-semibold text-green-600 bg-white hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all">
                Masuk ke Akun
            </a>
        </div>
    </form>
</x-guest-layout>
