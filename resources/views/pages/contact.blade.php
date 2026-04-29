<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kontak - {{ config('app.name') }}</title>
    <meta name="description" content="Hubungi tim Konektivitas melalui email atau WhatsApp. Kami siap membantu Anda.">
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
            <h1 class="text-4xl sm:text-5xl font-bold text-gray-900 mb-4">Hubungi <span
                    class="text-green-600">Kami</span></h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">Punya pertanyaan atau butuh bantuan? Tim kami siap
                membantu Anda.</p>
        </div>
    </section>

    <section class="py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                <!-- Contact Info -->
                <div class="space-y-8">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">Informasi Kontak</h2>
                        <div class="space-y-6">
                            <div class="flex items-start gap-4">
                                <div
                                    class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">Email</h3>
                                    <a href="mailto:info@konektivitas.com"
                                        class="text-green-600 hover:text-green-700">info@konektivitas.com</a>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <div
                                    class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z">
                                        </path>
                                        <path
                                            d="M12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.832-1.438A9.955 9.955 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18a8 8 0 01-4.243-1.214l-.252-.149-2.868.852.852-2.868-.149-.252A8 8 0 1112 20z">
                                        </path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">WhatsApp</h3>
                                    <a href="https://wa.me/6281529211963" target="_blank"
                                        class="text-green-600 hover:text-green-700">+62 815-2921-1963</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="font-semibold text-gray-900 mb-3">Jam Operasional</h3>
                        <div class="space-y-2 text-sm text-gray-600">
                            <div class="flex justify-between">
                                <span>Senin - Jumat</span>
                                <span class="font-medium text-gray-900">09:00 - 17:00 WIB</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Sabtu</span>
                                <span class="font-medium text-gray-900">09:00 - 14:00 WIB</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Minggu & Hari Libur</span>
                                <span class="font-medium text-gray-900">Tutup</span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-3">* WhatsApp support tersedia di luar jam operasional untuk
                            pertanyaan mendesak.</p>
                    </div>
                </div>

                <!-- Contact Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white p-8 rounded-xl border border-gray-200" x-data="{
                        name: '',
                        email: '',
                        subject: '',
                        message: '',
                        get waLink() {
                            let text = `Halo, saya ${this.name}`;
                            if (this.email) text += ` (${this.email})`;
                            if (this.subject) text += `\n\nSubjek: ${this.subject}`;
                            if (this.message) text += `\n\n${this.message}`;
                            return `https://wa.me/6281529211963?text=${encodeURIComponent(text)}`;
                        }
                    }">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">Kirim Pesan</h2>
                        <form x-on:submit.prevent="window.open(waLink, '_blank')" class="space-y-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama
                                        Lengkap</label>
                                    <input type="text" id="name" x-model="name" required
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                        placeholder="Nama Anda">
                                </div>
                                <div>
                                    <label for="email"
                                        class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <input type="email" id="email" x-model="email"
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                        placeholder="email@contoh.com">
                                </div>
                            </div>
                            <div>
                                <label for="subject"
                                    class="block text-sm font-medium text-gray-700 mb-1">Subjek</label>
                                <input type="text" id="subject" x-model="subject"
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                    placeholder="Tentang apa pesan Anda?">
                            </div>
                            <div>
                                <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Pesan</label>
                                <textarea id="message" x-model="message" rows="5" required
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                    placeholder="Tulis pesan Anda di sini..."></textarea>
                            </div>
                            <div class="flex items-center gap-4">
                                <button type="submit"
                                    class="bg-green-600 text-white hover:bg-green-700 px-6 py-3 rounded-lg font-semibold transition inline-flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z">
                                        </path>
                                        <path
                                            d="M12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.832-1.438A9.955 9.955 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18a8 8 0 01-4.243-1.214l-.252-.149-2.868.852.852-2.868-.149-.252A8 8 0 1112 20z">
                                        </path>
                                    </svg>
                                    Kirim via WhatsApp
                                </button>
                                <span class="text-sm text-gray-500">Pesan akan dikirim melalui WhatsApp</span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('partials.public-footer')
</body>

</html>
