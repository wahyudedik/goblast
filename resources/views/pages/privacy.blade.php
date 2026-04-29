<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kebijakan Privasi - {{ config('app.name') }}</title>
    <meta name="description"
        content="Kebijakan Privasi Konektivitas. Pelajari bagaimana kami mengumpulkan, menggunakan, dan melindungi data Anda.">
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
            <h1 class="text-4xl sm:text-5xl font-bold text-gray-900 mb-4">Kebijakan <span
                    class="text-green-600">Privasi</span></h1>
            <p class="text-gray-600">Terakhir diperbarui: April 2026</p>
        </div>
    </section>

    <!-- Content -->
    <section class="py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto prose prose-gray max-w-none">

            <p class="text-gray-600 mb-8">
                Kebijakan Privasi ini menjelaskan bagaimana Konektivitas ("kami", "milik kami"), yang dioperasikan oleh
                Noteds Technology,
                mengumpulkan, menggunakan, menyimpan, dan melindungi informasi pribadi Anda saat menggunakan layanan
                kami di
                <a href="https://konektivitas.com" class="text-green-600 hover:text-green-700">konektivitas.com</a>.
            </p>

            <h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">1. Informasi yang Kami Kumpulkan</h2>

            <h3 class="text-lg font-semibold text-gray-900 mt-6 mb-3">1.1 Informasi Akun</h3>
            <p class="text-gray-600 mb-4">Saat Anda mendaftar, kami mengumpulkan:</p>
            <ul class="list-disc pl-6 text-gray-600 space-y-1 mb-4">
                <li>Nama lengkap</li>
                <li>Alamat email</li>
                <li>Nama perusahaan/organisasi</li>
                <li>Nomor telepon (opsional)</li>
            </ul>

            <h3 class="text-lg font-semibold text-gray-900 mt-6 mb-3">1.2 Data Penggunaan Layanan</h3>
            <p class="text-gray-600 mb-4">Selama penggunaan layanan, kami mengumpulkan:</p>
            <ul class="list-disc pl-6 text-gray-600 space-y-1 mb-4">
                <li>Log pengiriman pesan (nomor tujuan, status pengiriman, waktu)</li>
                <li>Data kontak yang Anda unggah ke platform</li>
                <li>Template pesan yang Anda buat</li>
                <li>Konfigurasi auto reply dan reminder</li>
                <li>Data penggunaan API (jika menggunakan fitur API)</li>
            </ul>

            <h3 class="text-lg font-semibold text-gray-900 mt-6 mb-3">1.3 Data WhatsApp</h3>
            <p class="text-gray-600 mb-4">
                Konektivitas menggunakan teknologi WhatsApp Web untuk menghubungkan perangkat Anda. Kami
                <strong>tidak</strong> menyimpan
                isi pesan WhatsApp Anda secara permanen. Data sesi WhatsApp disimpan secara terenkripsi dan hanya
                digunakan untuk
                menjaga koneksi perangkat Anda.
            </p>

            <h3 class="text-lg font-semibold text-gray-900 mt-6 mb-3">1.4 Data Teknis</h3>
            <ul class="list-disc pl-6 text-gray-600 space-y-1 mb-4">
                <li>Alamat IP</li>
                <li>Jenis browser dan perangkat</li>
                <li>Halaman yang dikunjungi dan waktu akses</li>
                <li>Cookie dan teknologi pelacakan serupa</li>
            </ul>

            <h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">2. Penggunaan Informasi</h2>
            <p class="text-gray-600 mb-4">Kami menggunakan informasi yang dikumpulkan untuk:</p>
            <ul class="list-disc pl-6 text-gray-600 space-y-1 mb-4">
                <li>Menyediakan dan mengelola layanan Konektivitas</li>
                <li>Memproses pengiriman pesan WhatsApp sesuai permintaan Anda</li>
                <li>Mengirim notifikasi terkait akun dan layanan</li>
                <li>Meningkatkan kualitas dan performa layanan</li>
                <li>Mendeteksi dan mencegah penyalahgunaan layanan</li>
                <li>Memenuhi kewajiban hukum yang berlaku</li>
                <li>Memberikan dukungan teknis</li>
            </ul>

            <h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">3. Penyimpanan Data</h2>
            <p class="text-gray-600 mb-4">
                Data Anda disimpan di server yang aman selama akun Anda aktif. Log pengiriman pesan disimpan selama
                periode tertentu
                untuk keperluan monitoring dan kemudian dihapus secara otomatis. Setelah akun dihapus, data pribadi Anda
                akan dihapus
                dalam waktu 30 hari, kecuali jika kami diwajibkan oleh hukum untuk menyimpannya lebih lama.
            </p>

            <h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">4. Berbagi Data dengan Pihak Ketiga</h2>
            <p class="text-gray-600 mb-4">Kami <strong>tidak</strong> menjual data pribadi Anda kepada pihak ketiga.
                Kami hanya membagikan data dalam situasi berikut:</p>
            <ul class="list-disc pl-6 text-gray-600 space-y-1 mb-4">
                <li><strong>Penyedia layanan:</strong> Kami menggunakan penyedia hosting dan infrastruktur pihak ketiga
                    untuk menjalankan layanan kami. Mereka hanya memproses data sesuai instruksi kami.</li>
                <li><strong>Kewajiban hukum:</strong> Jika diwajibkan oleh hukum, peraturan, atau proses hukum yang
                    berlaku.</li>
                <li><strong>Perlindungan hak:</strong> Untuk melindungi hak, properti, atau keselamatan kami, pengguna
                    kami, atau publik.</li>
            </ul>

            <h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">5. Keamanan Data</h2>
            <p class="text-gray-600 mb-4">
                Kami menerapkan langkah-langkah keamanan teknis dan organisasi yang sesuai untuk melindungi data Anda,
                termasuk:
            </p>
            <ul class="list-disc pl-6 text-gray-600 space-y-1 mb-4">
                <li>Enkripsi data saat transit (HTTPS/TLS)</li>
                <li>Enkripsi data sesi WhatsApp</li>
                <li>Akses terbatas ke data berdasarkan kebutuhan</li>
                <li>Monitoring keamanan secara berkala</li>
                <li>Backup data secara rutin</li>
            </ul>

            <h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">6. Hak Anda</h2>
            <p class="text-gray-600 mb-4">Anda memiliki hak untuk:</p>
            <ul class="list-disc pl-6 text-gray-600 space-y-1 mb-4">
                <li>Mengakses data pribadi yang kami simpan tentang Anda</li>
                <li>Meminta koreksi data yang tidak akurat</li>
                <li>Meminta penghapusan data pribadi Anda</li>
                <li>Mengekspor data Anda dalam format yang dapat dibaca</li>
                <li>Menarik persetujuan penggunaan data kapan saja</li>
            </ul>

            <h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">7. Cookie</h2>
            <p class="text-gray-600 mb-4">
                Kami menggunakan cookie yang diperlukan untuk fungsi dasar website, seperti menjaga sesi login Anda.
                Kami tidak menggunakan cookie pelacakan pihak ketiga untuk iklan.
            </p>

            <h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">8. Perubahan Kebijakan</h2>
            <p class="text-gray-600 mb-4">
                Kami dapat memperbarui Kebijakan Privasi ini dari waktu ke waktu. Perubahan signifikan akan
                diberitahukan melalui
                email atau notifikasi di platform. Penggunaan layanan setelah perubahan dianggap sebagai persetujuan
                terhadap
                kebijakan yang diperbarui.
            </p>

            <h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">9. Hubungi Kami</h2>
            <p class="text-gray-600 mb-4">
                Jika Anda memiliki pertanyaan tentang Kebijakan Privasi ini atau ingin menggunakan hak Anda terkait data
                pribadi,
                silakan hubungi kami:
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
