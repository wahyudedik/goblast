<x-guest-layout>
    <x-slot name="title">
        Lupa Password?
    </x-slot>
    <x-slot name="subtitle">
        Masukkan email Anda untuk reset password
    </x-slot>

    <div class="mb-6 text-sm text-gray-600">
        Tidak masalah. Masukkan alamat email Anda dan kami akan mengirimkan link reset password yang memungkinkan Anda
        membuat password baru.
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-6" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-2 w-full" type="email" name="email" :value="old('email')"
                required autofocus placeholder="nama@email.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="pt-1">
            <x-primary-button class="w-full justify-center">
                Kirim Link Reset Password
            </x-primary-button>
        </div>

        <div class="text-center">
            <a href="{{ route('login') }}"
                class="inline-flex items-center text-sm text-green-600 hover:text-green-700 font-medium transition">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Kembali ke Login
            </a>
        </div>
    </form>
</x-guest-layout>
