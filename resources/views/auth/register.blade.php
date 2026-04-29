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
