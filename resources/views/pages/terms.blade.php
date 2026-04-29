<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Syarat & Ketentuan - {{ config('app.name') }}</title>
    <meta name="description"
        content="Syarat dan Ketentuan penggunaan layanan Konektivitas. Baca sebelum menggunakan platform kami.">
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
            <h1 class="text-4xl sm:text-5xl font-bold text-gray-900 mb-4">Syarat & <span
                    class="text-green-600">Ketentuan</span></h1>
            <p class="text-gray-600">Terakhir diperbarui: April 2026</p>
        </div>
    </section>

    <!-- Content -->
    <section class="py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto prose prose-gray max-w-none">

            <p class="text-gray-600 mb-8">
                Syarat dan Ketentuan ini mengatur penggunaan layanan Konektivitas ("Layanan") yang disediakan oleh
                Noteds Technology ("kami").
                Dengan mendaftar dan menggunakan Layanan, Anda ("Pengguna") menyetujui untuk terikat oleh syarat dan
                ketentuan berikut.
            </p>

            <h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">1. Definisi</h2>
            <ul class="list-disc pl-6 text-gray-600 space-y-1 mb-4">
                <li><strong>"Layanan"</strong> merujuk pada platform otomasi WhatsApp Konektivitas yang tersedia di <a
                        href="https://konektivitas.com"
                        class="text-green-600 hover:text-green-700">konektivitas.com</a>.</li>
                <li><strong>"Pengguna"</strong> merujuk pada individu atau entitas yang mendaftar dan menggunakan
                    Layanan.</li>
                <li><strong>"Akun"</strong> merujuk pada akun yang dibuat oleh Pengguna untuk mengakses Layanan.</li>
                <li><strong>"Konten"</strong> merujuk pada pesan, template, kontak, dan data lain yang diunggah atau
                    dibuat oleh Pengguna.</li>
            </ul>

            <h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">2. Pendaftaran Akun</h2>
            <ul class="list-disc pl-6 text-gray-600 space-y-1 mb-4">
                <li>Anda harus berusia minimal 18 tahun atau memiliki izin dari wali yang sah untuk menggunakan Layanan.
                </li>
                <li>Informasi yang Anda berikan saat pendaftaran harus akurat dan lengkap.</li>
                <li>Anda bertanggung jawab untuk menjaga kerahasiaan kredensial akun Anda.</li>
                <li>Anda bertanggung jawab atas semua aktivitas yang terjadi di akun Anda.</li>
                <li>Segera beritahu kami jika Anda mencurigai adanya akses tidak sah ke akun Anda.</li>
            </ul>

            <h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">3. Penggunaan yang Diperbolehkan</h2>
            <p class="text-gray-600 mb-4">Anda setuju untuk menggunakan Layanan hanya untuk tujuan yang sah dan sesuai
                dengan ketentuan berikut:</p>
            <ul class="list-disc pl-6 text-gray-600 space-y-1 mb-4">
                <li>Mengirim pesan hanya kepada penerima yang telah memberikan persetujuan (opt-in).</li>
                <li>Tidak mengirim spam, pesan penipuan, atau konten yang melanggar hukum.</li>
                <li>Tidak menggunakan Layanan untuk tujuan yang melanggar ketentuan layanan WhatsApp.</li>
                <li>Tidak mencoba mengakses sistem atau data pengguna lain tanpa izin.</li>
                <li>Tidak menggunakan Layanan untuk mengirim konten yang mengandung SARA, pornografi, atau kekerasan.
                </li>
                <li>Mematuhi semua hukum dan peraturan yang berlaku di Indonesia.</li>
            </ul>

            <h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">4. Paket Langganan dan Pembayaran</h2>

            <h3 class="text-lg font-semibold text-gray-900 mt-6 mb-3">4.1 Trial Gratis</h3>
            <p class="text-gray-600 mb-4">
                Pengguna baru mendapatkan trial gratis selama 14 hari. Selama masa trial, Anda dapat mengakses fitur
                sesuai paket trial.
                Setelah masa trial berakhir, Anda harus berlangganan paket berbayar untuk melanjutkan penggunaan.
            </p>

            <h3 class="text-lg font-semibold text-gray-900 mt-6 mb-3">4.2 Pembayaran</h3>
            <ul class="list-disc pl-6 text-gray-600 space-y-1 mb-4">
                <li>Pembayaran dilakukan di muka untuk periode langganan yang dipilih.</li>
                <li>Harga dapat berubah dengan pemberitahuan minimal 30 hari sebelumnya.</li>
                <li>Pembayaran yang telah dilakukan tidak dapat dikembalikan (non-refundable), kecuali ditentukan lain.
                </li>
                <li>Keterlambatan pembayaran dapat mengakibatkan penangguhan layanan.</li>
            </ul>

            <h3 class="text-lg font-semibold text-gray-900 mt-6 mb-3">4.3 Kuota dan Batasan</h3>
            <p class="text-gray-600 mb-4">
                Setiap paket memiliki kuota pesan dan batasan fitur yang berbeda. Penggunaan melebihi kuota akan
                dibatasi hingga
                periode berikutnya atau hingga Anda melakukan upgrade paket.
            </p>

            <h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">5. Ketersediaan Layanan</h2>
            <ul class="list-disc pl-6 text-gray-600 space-y-1 mb-4">
                <li>Kami berusaha menyediakan Layanan 24/7, namun tidak menjamin ketersediaan tanpa gangguan.</li>
                <li>Kami dapat melakukan pemeliharaan terjadwal yang mungkin menyebabkan gangguan sementara.</li>
                <li>Ketersediaan pengiriman pesan bergantung pada layanan WhatsApp dan koneksi perangkat Anda.</li>
                <li>Kami tidak bertanggung jawab atas gangguan yang disebabkan oleh pihak ketiga, termasuk WhatsApp.
                </li>
            </ul>

            <h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">6. Konten Pengguna</h2>
            <ul class="list-disc pl-6 text-gray-600 space-y-1 mb-4">
                <li>Anda memiliki hak atas Konten yang Anda buat dan unggah ke platform.</li>
                <li>Anda memberikan kami lisensi terbatas untuk memproses Konten Anda guna menyediakan Layanan.</li>
                <li>Anda bertanggung jawab penuh atas Konten yang Anda kirim melalui Layanan.</li>
                <li>Kami berhak menghapus Konten yang melanggar ketentuan ini tanpa pemberitahuan.</li>
            </ul>

            <h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">7. Penangguhan dan Penghentian</h2>

            <h3 class="text-lg font-semibold text-gray-900 mt-6 mb-3">7.1 Oleh Pengguna</h3>
            <p class="text-gray-600 mb-4">
                Anda dapat menghentikan penggunaan Layanan kapan saja dengan menghubungi tim kami. Akun Anda akan tetap
                aktif
                hingga akhir periode langganan yang sudah dibayar.
            </p>

            <h3 class="text-lg font-semibold text-gray-900 mt-6 mb-3">7.2 Oleh Kami</h3>
            <p class="text-gray-600 mb-4">Kami berhak menangguhkan atau menghentikan akun Anda jika:</p>
            <ul class="list-disc pl-6 text-gray-600 space-y-1 mb-4">
                <li>Anda melanggar Syarat dan Ketentuan ini.</li>
                <li>Anda menggunakan Layanan untuk aktivitas ilegal atau spam.</li>
                <li>Pembayaran Anda tertunggak melebihi batas waktu yang ditentukan.</li>
                <li>Penggunaan Anda membahayakan sistem atau pengguna lain.</li>
            </ul>

            <h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">8. Batasan Tanggung Jawab</h2>
            <ul class="list-disc pl-6 text-gray-600 space-y-1 mb-4">
                <li>Layanan disediakan "sebagaimana adanya" (as is) tanpa jaminan apapun.</li>
                <li>Kami tidak bertanggung jawab atas kerugian tidak langsung, insidental, atau konsekuensial yang
                    timbul dari penggunaan Layanan.</li>
                <li>Kami tidak bertanggung jawab atas pemblokiran nomor WhatsApp Anda oleh WhatsApp.</li>
                <li>Total tanggung jawab kami tidak akan melebihi jumlah yang Anda bayarkan dalam 3 bulan terakhir.</li>
                <li>Kami tidak bertanggung jawab atas kerugian yang disebabkan oleh force majeure.</li>
            </ul>

            <h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">9. Ganti Rugi</h2>
            <p class="text-gray-600 mb-4">
                Anda setuju untuk membebaskan dan melindungi kami dari segala klaim, kerugian, dan biaya (termasuk biaya
                hukum)
                yang timbul dari pelanggaran Anda terhadap Syarat dan Ketentuan ini atau penggunaan Layanan yang tidak
                sah.
            </p>

            <h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">10. Hukum yang Berlaku</h2>
            <p class="text-gray-600 mb-4">
                Syarat dan Ketentuan ini diatur oleh dan ditafsirkan sesuai dengan hukum Republik Indonesia.
                Setiap sengketa yang timbul akan diselesaikan melalui musyawarah terlebih dahulu, dan jika tidak
                tercapai kesepakatan,
                akan diselesaikan melalui pengadilan yang berwenang di Indonesia.
            </p>

            <h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">11. Perubahan Syarat dan Ketentuan</h2>
            <p class="text-gray-600 mb-4">
                Kami berhak mengubah Syarat dan Ketentuan ini kapan saja. Perubahan signifikan akan diberitahukan
                melalui email
                atau notifikasi di platform minimal 14 hari sebelum berlaku. Penggunaan Layanan setelah perubahan
                berlaku dianggap
                sebagai persetujuan terhadap syarat yang diperbarui.
            </p>

            <h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">12. Hubungi Kami</h2>
            <p class="text-gray-600 mb-4">
                Jika Anda memiliki pertanyaan tentang Syarat dan Ketentuan ini, silakan hubungi kami:
            </p>
            <ul class="list-none text-gray-600 space-y-2 mb-4">
                <li><strong>Email:</strong> <a href="mailto:info@konektivitas.com"
                        class="text-green-600 hover:text-green-700">info@konektivitas.com</a></li>
                <li><strong>WhatsApp:</strong> <a href="https://wa.me/6281529211963" target="_blank"
                        class="text-green-600 hover:text-green-700">+62 815-2921-1963</a></li>
                <li><strong>Perusahaan:</strong> Noteds Technology</li>
            </ul>

        </div>
    </section>

    @include('partials.public-footer')
</body>

</html>
