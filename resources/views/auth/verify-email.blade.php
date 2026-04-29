<x-guest-layout>
    <x-slot name="title">
        Verifikasi Email
    </x-slot>
    <x-slot name="subtitle">
        Konfirmasi alamat email Anda
    </x-slot>

    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <p class="text-sm text-blue-800">
            Terima kasih telah mendaftar! Sebelum memulai, mohon verifikasi alamat email Anda dengan mengklik link yang
            baru saja kami kirimkan. Jika Anda tidak menerima email, kami akan dengan senang hati mengirimkan yang baru.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-sm text-green-800 font-medium">
                Link verifikasi baru telah dikirim ke alamat email yang Anda berikan saat pendaftaran.
            </p>
        </div>
    @endif

    <div class="space-y-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button class="w-full justify-center">
                Kirim Ulang Email Verifikasi
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="w-full px-4 py-2.5 border-2 border-gray-300 rounded-lg text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all">
                Keluar
            </button>
        </form>
    </div>
</x-guest-layout>
