<!-- Footer -->
<footer class="bg-gray-900 text-gray-300 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-8">
            <div>
                <h3 class="text-white text-lg font-semibold mb-4">{{ config('app.name') }}</h3>
                <p class="text-sm">Solusi otomasi WhatsApp terpercaya untuk bisnis Anda.</p>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-4">Produk</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('pages.features') }}" class="hover:text-white">Fitur</a></li>
                    <li><a href="/#pricing" class="hover:text-white">Harga</a></li>
                    <li><a href="{{ route('pages.faq') }}" class="hover:text-white">FAQ</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-4">Perusahaan</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('pages.about') }}" class="hover:text-white">Tentang Kami</a></li>
                    <li><a href="{{ route('pages.contact') }}" class="hover:text-white">Kontak</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-4">Legal</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('pages.privacy') }}" class="hover:text-white">Kebijakan Privasi</a></li>
                    <li><a href="{{ route('pages.terms') }}" class="hover:text-white">Syarat & Ketentuan</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-4">Dukungan</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="mailto:info@konektivitas.com" class="hover:text-white">info@konektivitas.com</a></li>
                    <li><a href="https://wa.me/6281529211963" target="_blank" class="hover:text-white">WhatsApp
                            Support</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-gray-800 mt-8 pt-8 text-center text-sm">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved. Develop by <b>Noteds
                    Technology</b></p>
        </div>
    </div>
</footer>
