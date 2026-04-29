# Dokumen Persyaratan: WA Automation

## Pendahuluan

WA Automation adalah modul SaaS berbasis Laravel yang memungkinkan pengguna mengirim pesan WhatsApp secara otomatis, terjadwal, dan massal. Sistem ini terintegrasi dengan gateway Node.js (Baileys) melalui HTTP API internal, menggunakan Laravel Queue untuk pengiriman bertahap guna menghindari pemblokiran nomor. Fitur mencakup pengiriman pesan terpicu (trigger-based), broadcast massal, reminder otomatis, manajemen template, log pengiriman, auto-reply berbasis kata kunci, dukungan multi-device, dan API publik untuk integrasi eksternal. Model bisnis berbasis langganan SaaS dengan kuota pesan per paket.

---

## Glosarium

- **WA_Automation_System**: Sistem keseluruhan modul WA Automation dalam aplikasi Laravel.
- **Dashboard**: Antarmuka web berbasis Blade + Tailwind CSS yang digunakan oleh Admin dan User.
- **Admin**: Pengguna dengan peran administrator yang mengelola template, device, dan konfigurasi sistem.
- **User**: Pengguna akhir (tenant) yang menggunakan fitur pengiriman pesan sesuai paket langganan.
- **Baileys_Gateway**: Layanan Node.js eksternal yang menghubungkan sistem ke WhatsApp melalui protokol Baileys.
- **Queue_Worker**: Proses Laravel Queue yang memproses job pengiriman pesan secara asinkron.
- **Scheduler**: Laravel Scheduler yang menjalankan reminder dan tugas terjadwal secara berkala.
- **Message_Job**: Job Laravel yang merepresentasikan satu tugas pengiriman pesan ke satu nomor tujuan.
- **Template**: Pesan berformat dengan variabel dinamis (contoh: `{nama}`, `{status}`) yang dapat digunakan ulang.
- **Broadcast**: Pengiriman pesan ke banyak nomor tujuan sekaligus dalam satu sesi.
- **Reminder**: Pesan yang dikirim secara otomatis berdasarkan kondisi waktu atau status data tertentu.
- **Device**: Nomor WhatsApp yang terhubung ke Baileys_Gateway dan digunakan untuk mengirim pesan.
- **Tenant**: Satu akun pelanggan SaaS yang memiliki kuota, device, dan data tersendiri.
- **Kuota**: Jumlah maksimum pesan yang dapat dikirim oleh Tenant dalam satu periode langganan.
- **Log_Pengiriman**: Catatan historis setiap upaya pengiriman pesan beserta statusnya.
- **Auto_Reply**: Fitur yang membalas pesan masuk secara otomatis berdasarkan kata kunci yang cocok.
- **Keyword_Rule**: Aturan yang mendefinisikan kata kunci pemicu dan balasan otomatis yang sesuai.
- **API_Token**: Token autentikasi yang digunakan untuk mengakses endpoint API publik WA Automation.
- **Paket_Langganan**: Tingkatan layanan SaaS (Starter, Pro, Business) dengan batasan kuota dan fitur berbeda.
- **Delay_Interval**: Jeda waktu antar pengiriman pesan dalam satu Broadcast untuk mencegah pemblokiran.
- **Superadmin**: Operator platform SaaS yang memiliki akses penuh ke seluruh sistem lintas tenant.
- **Superadmin_Dashboard**: Antarmuka khusus Superadmin yang terpisah dari Dashboard Tenant.
- **Trial**: Masa percobaan gratis yang diberikan kepada Tenant baru sebelum berlangganan berbayar.
- **Invoice_Billing**: Catatan pembayaran manual yang dibuat Superadmin untuk setiap transaksi langganan Tenant.
- **Periode_Langganan**: Rentang waktu aktif langganan Tenant (tanggal mulai hingga tanggal berakhir).
- **Kontak_WA**: Nomor WhatsApp yang digunakan untuk proses pemesanan langganan (wa.me/6281529211963).

---

## Persyaratan

### Persyaratan 1: Manajemen Koneksi Device WhatsApp

**User Story:** Sebagai Admin, saya ingin menghubungkan dan mengelola nomor WhatsApp ke sistem, sehingga pesan dapat dikirim melalui nomor yang valid dan aktif.

#### Kriteria Penerimaan

1. WHEN Admin meminta koneksi Device baru, THE WA_Automation_System SHALL mengirim permintaan ke Baileys_Gateway untuk menghasilkan QR code dan menampilkannya di Dashboard dalam waktu 10 detik.
2. WHEN pengguna memindai QR code dengan aplikasi WhatsApp, THE Baileys_Gateway SHALL mengonfirmasi status koneksi ke WA_Automation_System dan WA_Automation_System SHALL memperbarui status Device menjadi "terhubung".
3. WHILE Device berstatus "terhubung", THE WA_Automation_System SHALL melakukan pengecekan status koneksi ke Baileys_Gateway setiap 60 detik.
4. IF koneksi Device terputus, THEN THE WA_Automation_System SHALL memperbarui status Device menjadi "terputus" dan mencatat kejadian tersebut di Log_Pengiriman.
5. THE WA_Automation_System SHALL menampilkan daftar semua Device milik Tenant beserta status koneksi terkini di Dashboard.
6. WHEN Admin menghapus Device, THE WA_Automation_System SHALL memutus koneksi ke Baileys_Gateway dan menghapus data sesi Device tersebut.
7. WHERE paket langganan adalah Business, THE WA_Automation_System SHALL mengizinkan Tenant memiliki lebih dari satu Device aktif secara bersamaan.
8. IF Tenant mencoba menambah Device melebihi batas paket langganan, THEN THE WA_Automation_System SHALL menolak permintaan dan menampilkan pesan kesalahan yang menjelaskan batas paket.

---

### Persyaratan 2: Manajemen Template Pesan

**User Story:** Sebagai Admin, saya ingin membuat dan mengelola template pesan dengan variabel dinamis, sehingga pesan yang dikirim konsisten dan dapat dipersonalisasi.

#### Kriteria Penerimaan

1. THE WA_Automation_System SHALL menyediakan antarmuka di Dashboard untuk membuat, membaca, memperbarui, dan menghapus Template.
2. WHEN Admin menyimpan Template, THE WA_Automation_System SHALL memvalidasi bahwa nama Template tidak kosong dan isi Template tidak melebihi 4096 karakter.
3. THE WA_Automation_System SHALL mendukung variabel dinamis dalam Template menggunakan format `{nama_variabel}` (contoh: `{nama}`, `{status}`, `{tanggal}`, `{jumlah}`).
4. WHEN Template digunakan untuk mengirim pesan, THE WA_Automation_System SHALL mengganti setiap variabel `{nama_variabel}` dengan nilai yang sesuai dari data konteks pengiriman.
5. IF variabel dalam Template tidak memiliki nilai yang sesuai pada saat pengiriman, THEN THE WA_Automation_System SHALL mengganti variabel tersebut dengan string kosong dan mencatat peringatan di log.
6. THE WA_Automation_System SHALL mengkategorikan Template ke dalam tipe: Notifikasi, Promo, dan Reminder.
7. WHEN Admin menghapus Template yang sedang digunakan oleh Reminder aktif, THE WA_Automation_System SHALL menolak penghapusan dan menampilkan daftar Reminder yang menggunakan Template tersebut.

---

### Persyaratan 3: Pengiriman Pesan Otomatis Berbasis Trigger

**User Story:** Sebagai User, saya ingin pesan WhatsApp terkirim secara otomatis ketika terjadi event tertentu di sistem, sehingga penerima mendapat notifikasi tepat waktu tanpa intervensi manual.

#### Kriteria Penerimaan

1. THE WA_Automation_System SHALL mendukung trigger pengiriman pesan otomatis untuk event berikut: absensi masuk, keterlambatan/ketidakhadiran, pembuatan invoice, dan pembuatan booking.
2. WHEN salah satu event trigger terjadi, THE WA_Automation_System SHALL membuat Message_Job dan memasukkannya ke dalam antrian Queue_Worker dalam waktu 5 detik setelah event terjadi.
3. WHEN Message_Job diproses oleh Queue_Worker, THE WA_Automation_System SHALL mengirim permintaan HTTP ke Baileys_Gateway dengan payload berisi nomor tujuan, isi pesan hasil render Template, dan identitas Device pengirim.
4. IF Baileys_Gateway mengembalikan respons sukses, THEN THE WA_Automation_System SHALL mencatat status pengiriman sebagai "terkirim" di Log_Pengiriman.
5. IF Baileys_Gateway mengembalikan respons gagal atau tidak merespons dalam 30 detik, THEN THE WA_Automation_System SHALL mencatat status pengiriman sebagai "gagal" di Log_Pengiriman dan mencatat pesan kesalahan yang diterima.
6. THE WA_Automation_System SHALL memungkinkan Admin mengonfigurasi pasangan antara event trigger dan Template yang akan digunakan untuk setiap Tenant.
7. IF Kuota pesan Tenant telah habis, THEN THE WA_Automation_System SHALL membatalkan Message_Job, mencatat status sebagai "dibatalkan - kuota habis" di Log_Pengiriman, dan tidak mengirim pesan ke Baileys_Gateway.

---

### Persyaratan 4: Broadcast Pesan Massal

**User Story:** Sebagai User, saya ingin mengirim pesan ke banyak nomor sekaligus, sehingga saya dapat menjangkau banyak penerima secara efisien.

#### Kriteria Penerimaan

1. THE WA_Automation_System SHALL menyediakan antarmuka Broadcast di Dashboard untuk memilih Template, Device pengirim, dan daftar nomor tujuan.
2. THE WA_Automation_System SHALL mendukung dua sumber daftar nomor tujuan: unggah file CSV dan pilihan dari database User terdaftar.
3. WHEN Admin mengunggah file CSV, THE WA_Automation_System SHALL memvalidasi bahwa file berformat CSV, berukuran tidak lebih dari 5 MB, dan memiliki kolom nomor telepon yang dapat diidentifikasi.
4. IF file CSV mengandung baris dengan format nomor telepon tidak valid, THEN THE WA_Automation_System SHALL melewati baris tersebut, mencatatnya sebagai "nomor tidak valid" dalam laporan pratinjau, dan melanjutkan proses untuk baris yang valid.
5. WHEN Admin mengonfirmasi pengiriman Broadcast, THE WA_Automation_System SHALL membuat Message_Job terpisah untuk setiap nomor tujuan dan memasukkan semua job ke dalam Queue_Worker dengan Delay_Interval acak antara 5 hingga 10 detik antar job.
6. WHILE Broadcast sedang berjalan, THE WA_Automation_System SHALL menampilkan progres pengiriman secara real-time di Dashboard, mencakup jumlah terkirim, gagal, dan pending.
7. THE WA_Automation_System SHALL memungkinkan Admin membatalkan Broadcast yang sedang berjalan, yang akan menghapus semua Message_Job yang masih pending dari antrian.
8. IF total nomor tujuan dalam satu Broadcast melebihi sisa Kuota Tenant, THEN THE WA_Automation_System SHALL menampilkan peringatan kepada Admin sebelum konfirmasi pengiriman, menunjukkan jumlah pesan yang dapat terkirim dan jumlah yang akan dibatalkan karena kuota.

---

### Persyaratan 5: Reminder Otomatis Terjadwal

**User Story:** Sebagai User, saya ingin sistem mengirim reminder secara otomatis berdasarkan kondisi data, sehingga pelanggan saya mendapat pengingat tepat waktu tanpa saya harus melakukannya secara manual.

#### Kriteria Penerimaan

1. THE WA_Automation_System SHALL mendukung tiga jenis Reminder: SPP jatuh tempo, invoice belum dibayar, dan jadwal booking hari berikutnya.
2. THE Scheduler SHALL menjalankan pengecekan kondisi Reminder setiap hari pada pukul 07.00 WIB.
3. WHEN Scheduler menjalankan pengecekan, THE WA_Automation_System SHALL mengidentifikasi semua data yang memenuhi kondisi Reminder aktif dan membuat Message_Job untuk setiap penerima yang relevan.
4. THE WA_Automation_System SHALL memungkinkan Admin mengonfigurasi Reminder dengan parameter: jenis kondisi, Template yang digunakan, Device pengirim, dan status aktif/nonaktif.
5. IF Reminder telah mengirim pesan ke nomor yang sama untuk kondisi yang sama dalam 24 jam terakhir, THEN THE WA_Automation_System SHALL melewati pengiriman ulang untuk nomor tersebut guna mencegah duplikasi.
6. WHEN Admin menonaktifkan Reminder, THE WA_Automation_System SHALL menghentikan pembuatan Message_Job baru untuk Reminder tersebut pada siklus Scheduler berikutnya.
7. THE WA_Automation_System SHALL mencatat setiap eksekusi Scheduler beserta jumlah Reminder yang diproses dan jumlah Message_Job yang dibuat di log sistem.

---

### Persyaratan 6: Sistem Antrian dan Pengiriman Bertahap

**User Story:** Sebagai Admin, saya ingin pesan dikirim secara bertahap dengan jeda waktu, sehingga nomor WhatsApp yang digunakan tidak diblokir oleh WhatsApp karena aktivitas mencurigakan.

#### Kriteria Penerimaan

1. THE WA_Automation_System SHALL menggunakan Laravel Queue dengan driver database untuk semua pengiriman pesan.
2. WHEN Queue_Worker memproses Message_Job, THE WA_Automation_System SHALL menerapkan Delay_Interval acak antara 5 hingga 10 detik sebelum memproses Message_Job berikutnya dari Device yang sama.
3. THE WA_Automation_System SHALL membatasi pengiriman pesan dari satu Device menjadi maksimum 200 pesan per jam.
4. IF Message_Job gagal karena kesalahan sementara dari Baileys_Gateway, THEN THE WA_Automation_System SHALL mencoba ulang Message_Job tersebut sebanyak maksimum 3 kali dengan jeda eksponensial (30 detik, 60 detik, 120 detik).
5. IF Message_Job masih gagal setelah 3 kali percobaan ulang, THEN THE WA_Automation_System SHALL memindahkan job ke antrian failed_jobs dan mencatat status pengiriman sebagai "gagal permanen" di Log_Pengiriman.
6. THE WA_Automation_System SHALL menyediakan antarmuka di Dashboard untuk Admin melihat antrian pesan yang pending, sedang diproses, dan gagal.
7. WHEN Admin meminta pengiriman ulang Message_Job yang gagal permanen, THE WA_Automation_System SHALL memindahkan job kembali ke antrian aktif dan mereset hitungan percobaan.

---

### Persyaratan 7: Log Pengiriman dan Pelaporan

**User Story:** Sebagai Admin, saya ingin melihat riwayat dan status setiap pesan yang dikirim, sehingga saya dapat memantau performa pengiriman dan mendiagnosis masalah.

#### Kriteria Penerimaan

1. THE WA_Automation_System SHALL mencatat setiap upaya pengiriman pesan di Log_Pengiriman dengan atribut: nomor tujuan, isi pesan, nama Template yang digunakan, Device pengirim, waktu pengiriman, status (terkirim/gagal/pending/dibatalkan), dan pesan kesalahan jika ada.
2. THE WA_Automation_System SHALL menampilkan Log_Pengiriman di Dashboard dengan kemampuan filter berdasarkan: rentang tanggal, status pengiriman, Device pengirim, dan nomor tujuan.
3. THE WA_Automation_System SHALL menampilkan ringkasan statistik pengiriman di Dashboard, mencakup total terkirim, total gagal, total pending, dan persentase keberhasilan untuk periode yang dipilih.
4. WHEN Admin mengekspor Log_Pengiriman, THE WA_Automation_System SHALL menghasilkan file CSV yang berisi semua atribut log untuk rentang tanggal yang dipilih dalam waktu 30 detik.
5. THE WA_Automation_System SHALL menyimpan Log_Pengiriman selama minimal 90 hari sebelum dapat dihapus secara otomatis.
6. IF Log_Pengiriman melebihi 90 hari, THEN THE Scheduler SHALL menghapus entri log yang lebih lama dari 90 hari secara otomatis setiap minggu.

---

### Persyaratan 8: Auto Reply Berbasis Kata Kunci

**User Story:** Sebagai User, saya ingin sistem membalas pesan masuk secara otomatis berdasarkan kata kunci tertentu, sehingga pertanyaan umum dari pelanggan dapat dijawab tanpa intervensi manual.

#### Kriteria Penerimaan

1. THE WA_Automation_System SHALL menyediakan antarmuka di Dashboard untuk Admin membuat, memperbarui, dan menghapus Keyword_Rule.
2. WHEN membuat Keyword_Rule, THE WA_Automation_System SHALL memvalidasi bahwa kata kunci tidak kosong, balasan tidak kosong, dan tidak ada Keyword_Rule lain dengan kata kunci yang identik untuk Device yang sama.
3. WHEN Baileys_Gateway menerima pesan masuk, THE Baileys_Gateway SHALL meneruskan pesan tersebut ke WA_Automation_System melalui webhook HTTP.
4. WHEN WA_Automation_System menerima pesan masuk melalui webhook, THE WA_Automation_System SHALL memeriksa apakah isi pesan mengandung salah satu kata kunci dari Keyword_Rule yang aktif untuk Device penerima (pencocokan tidak peka huruf besar/kecil).
5. IF pesan masuk cocok dengan Keyword_Rule, THEN THE WA_Automation_System SHALL membuat Message_Job untuk mengirim balasan yang dikonfigurasi ke nomor pengirim pesan masuk tersebut.
6. IF pesan masuk cocok dengan lebih dari satu Keyword_Rule, THEN THE WA_Automation_System SHALL menggunakan Keyword_Rule dengan urutan prioritas tertinggi yang dikonfigurasi Admin.
7. THE WA_Automation_System SHALL mencatat setiap pesan masuk yang diterima dan status pencocokan kata kunci di log terpisah dari Log_Pengiriman.
8. THE WA_Automation_System SHALL membatasi Auto_Reply ke satu balasan per nomor pengirim per kata kunci dalam jangka waktu 60 menit untuk mencegah loop balasan.

---

### Persyaratan 9: Manajemen Paket Langganan dan Kuota

**User Story:** Sebagai Admin SaaS, saya ingin membatasi penggunaan fitur berdasarkan paket langganan Tenant, sehingga model bisnis berjenjang dapat diterapkan dan pendapatan dapat dikelola.

#### Kriteria Penerimaan

1. THE WA_Automation_System SHALL mendukung empat tingkatan Paket_Langganan: Starter (100 pesan/bulan, 1 Device), Pro (1.000 pesan/bulan, 1 Device, Reminder aktif), Business (pesan tidak terbatas, multi-Device, API aktif), dan Pay-per-message (untuk kelebihan kuota).
2. THE WA_Automation_System SHALL melacak penggunaan Kuota pesan setiap Tenant secara real-time dan menampilkan sisa Kuota di Dashboard.
3. WHEN Tenant mengirim pesan dan Kuota tersisa lebih dari 0, THE WA_Automation_System SHALL mengurangi Kuota sebesar 1 untuk setiap pesan yang berhasil masuk ke antrian.
4. IF Tenant dengan paket Starter atau Pro mencoba menggunakan fitur Reminder, THEN THE WA_Automation_System SHALL memblokir akses fitur Reminder untuk paket Starter dan mengizinkan akses untuk paket Pro.
5. IF Tenant dengan paket Starter atau Pro mencoba mengakses endpoint API publik, THEN THE WA_Automation_System SHALL mengembalikan respons HTTP 403 dengan pesan yang menjelaskan bahwa fitur API hanya tersedia untuk paket Business.
6. IF Tenant dengan paket Starter mencoba menambah Device kedua, THEN THE WA_Automation_System SHALL menolak permintaan dan menampilkan informasi upgrade ke paket Business.
7. WHEN Kuota Tenant mencapai 0, THE WA_Automation_System SHALL mengirim notifikasi email ke alamat email Tenant yang terdaftar.
8. WHEN periode langganan Tenant diperbarui, THE WA_Automation_System SHALL mereset Kuota pesan ke nilai awal sesuai Paket_Langganan yang aktif.

---

### Persyaratan 10: API Publik untuk Integrasi Eksternal

**User Story:** Sebagai Developer, saya ingin mengakses fitur pengiriman pesan melalui API, sehingga saya dapat mengintegrasikan WA Automation ke dalam aplikasi atau sistem eksternal saya.

#### Kriteria Penerimaan

1. THE WA_Automation_System SHALL menyediakan endpoint `POST /api/v1/send-message` yang menerima parameter: `device_id`, `to` (nomor tujuan), `message` (isi pesan teks), dan `template_id` (opsional).
2. THE WA_Automation_System SHALL menyediakan endpoint `POST /api/v1/send-bulk` yang menerima parameter: `device_id`, `recipients` (array nomor tujuan), `message` (isi pesan teks), dan `template_id` (opsional).
3. WHEN permintaan diterima di endpoint API, THE WA_Automation_System SHALL memvalidasi API_Token pada header `Authorization: Bearer {token}` sebelum memproses permintaan.
4. IF API_Token tidak valid atau tidak ditemukan, THEN THE WA_Automation_System SHALL mengembalikan respons HTTP 401 dengan body JSON `{"error": "Unauthorized", "message": "Token tidak valid atau tidak ditemukan"}`.
5. IF parameter wajib tidak lengkap atau format nomor tujuan tidak valid, THEN THE WA_Automation_System SHALL mengembalikan respons HTTP 422 dengan body JSON yang merinci setiap kesalahan validasi.
6. WHEN permintaan API valid diterima, THE WA_Automation_System SHALL membuat Message_Job dan memasukkannya ke Queue_Worker, lalu mengembalikan respons HTTP 202 dengan body JSON berisi `job_id` dan status "queued".
7. THE WA_Automation_System SHALL menyediakan endpoint `GET /api/v1/message-status/{job_id}` untuk memeriksa status pengiriman berdasarkan `job_id`.
8. THE WA_Automation_System SHALL membatasi permintaan API dari satu API_Token menjadi maksimum 60 permintaan per menit dan mengembalikan HTTP 429 jika batas terlampaui.
9. THE WA_Automation_System SHALL menyediakan antarmuka di Dashboard untuk Admin membuat, melihat, dan mencabut API_Token milik Tenant.

---

### Persyaratan 11: Integrasi dengan Baileys Gateway

**User Story:** Sebagai sistem, saya ingin berkomunikasi dengan Baileys Gateway secara andal, sehingga pesan dapat dikirim dan diterima melalui WhatsApp dengan benar.

#### Kriteria Penerimaan

1. THE WA_Automation_System SHALL berkomunikasi dengan Baileys_Gateway melalui HTTP REST API dengan format payload JSON.
2. WHEN mengirim pesan, THE WA_Automation_System SHALL mengirim permintaan `POST` ke endpoint Baileys_Gateway dengan payload berisi: `device_id`, `to`, dan `message`.
3. THE WA_Automation_System SHALL mengonfigurasi timeout koneksi ke Baileys_Gateway sebesar 30 detik untuk setiap permintaan.
4. IF Baileys_Gateway tidak dapat dijangkau (connection refused atau timeout), THEN THE WA_Automation_System SHALL mencatat kesalahan koneksi dan menandai Message_Job untuk percobaan ulang sesuai Persyaratan 6.4.
5. THE WA_Automation_System SHALL memvalidasi respons dari Baileys_Gateway dan menganggap pengiriman berhasil hanya jika respons HTTP memiliki status 200 dan body JSON mengandung field `status: "sent"`.
6. THE WA_Automation_System SHALL menyimpan URL base Baileys_Gateway sebagai konfigurasi environment variable (`BAILEYS_GATEWAY_URL`) dan tidak meng-hardcode URL tersebut dalam kode.
7. WHEN Baileys_Gateway mengirim webhook pesan masuk, THE WA_Automation_System SHALL memvalidasi signature webhook menggunakan secret key yang dikonfigurasi di environment variable (`BAILEYS_WEBHOOK_SECRET`) sebelum memproses payload.

---

### Persyaratan 12: Keamanan dan Autentikasi

**User Story:** Sebagai Admin, saya ingin sistem memiliki kontrol akses yang ketat, sehingga data dan fitur hanya dapat diakses oleh pengguna yang berwenang.

#### Kriteria Penerimaan

1. THE WA_Automation_System SHALL memastikan semua halaman Dashboard hanya dapat diakses oleh pengguna yang telah terautentikasi melalui sistem autentikasi Laravel.
2. THE WA_Automation_System SHALL menerapkan isolasi data antar Tenant sehingga satu Tenant tidak dapat mengakses data Device, Template, Log_Pengiriman, atau Keyword_Rule milik Tenant lain.
3. WHEN Admin mengakses data Tenant lain melalui manipulasi parameter URL atau request body, THE WA_Automation_System SHALL mengembalikan respons HTTP 403 dan mencatat upaya akses tidak sah di log sistem.
4. THE WA_Automation_System SHALL menyimpan API_Token dalam bentuk hash (bcrypt atau SHA-256) di database dan tidak pernah menyimpan nilai plaintext setelah pembuatan awal.
5. WHEN API_Token baru dibuat, THE WA_Automation_System SHALL menampilkan nilai plaintext token hanya sekali kepada Admin dan tidak dapat ditampilkan kembali setelahnya.
6. THE WA_Automation_System SHALL memvalidasi dan membersihkan semua input dari pengguna sebelum digunakan dalam query database atau dikirim ke Baileys_Gateway untuk mencegah injeksi.

---

### Persyaratan 13: Dashboard Superadmin

**User Story:** Sebagai Superadmin, saya ingin memiliki panel khusus yang terpisah dari Dashboard Tenant, sehingga saya dapat mengelola seluruh platform SaaS, memantau kesehatan sistem, dan mengambil tindakan operasional lintas tenant dari satu tempat.

#### Kriteria Penerimaan

##### 13.1 Manajemen Tenant

1. THE Superadmin_Dashboard SHALL menyediakan antarmuka untuk membuat, membaca, memperbarui, dan menghapus data Tenant.
2. WHEN Superadmin membuka detail Tenant, THE Superadmin_Dashboard SHALL menampilkan informasi penggunaan Tenant mencakup jumlah pesan terkirim, jumlah Device aktif, sisa Kuota, dan Paket_Langganan yang aktif.
3. WHEN Superadmin menangguhkan akun Tenant, THE WA_Automation_System SHALL memblokir semua akses login dan pengiriman pesan untuk Tenant tersebut dan mencatat tindakan beserta alasannya di log sistem.
4. WHEN Superadmin mengaktifkan kembali akun Tenant yang ditangguhkan, THE WA_Automation_System SHALL memulihkan akses login dan pengiriman pesan untuk Tenant tersebut.
5. IF Superadmin mencoba menghapus Tenant yang masih memiliki Device aktif atau Message_Job pending, THEN THE Superadmin_Dashboard SHALL menampilkan peringatan yang merinci jumlah Device aktif dan job pending sebelum konfirmasi penghapusan.

##### 13.2 Manajemen Paket Langganan

1. THE Superadmin_Dashboard SHALL menyediakan antarmuka untuk membuat, memperbarui, dan menghapus Paket_Langganan.
2. WHEN Superadmin membuat atau memperbarui Paket_Langganan, THE WA_Automation_System SHALL memvalidasi bahwa nama paket tidak kosong, harga tidak negatif, dan kuota pesan adalah bilangan bulat positif atau bernilai tidak terbatas.
3. IF Superadmin mencoba menghapus Paket_Langganan yang sedang digunakan oleh satu atau lebih Tenant aktif, THEN THE Superadmin_Dashboard SHALL menolak penghapusan dan menampilkan jumlah Tenant yang menggunakan paket tersebut.
4. WHEN Superadmin mengubah kuota atau harga Paket_Langganan, THE WA_Automation_System SHALL menerapkan perubahan hanya pada langganan baru dan tidak mengubah kuota Tenant yang sudah aktif pada periode berjalan.

##### 13.3 Monitoring Global

1. THE Superadmin_Dashboard SHALL menampilkan statistik platform secara real-time mencakup: total pesan terkirim hari ini, total Tenant aktif, total Device terhubung, dan total pendapatan bulan berjalan.
2. THE Superadmin_Dashboard SHALL menampilkan grafik tren pengiriman pesan harian untuk 30 hari terakhir lintas semua Tenant.
3. WHEN Superadmin memilih rentang tanggal pada halaman monitoring, THE Superadmin_Dashboard SHALL memperbarui semua statistik dan grafik sesuai rentang tanggal yang dipilih dalam waktu 5 detik.
4. THE Superadmin_Dashboard SHALL menampilkan daftar 10 Tenant dengan penggunaan pesan tertinggi pada periode yang dipilih.

##### 13.4 Manajemen Baileys Gateway

1. THE Superadmin_Dashboard SHALL menampilkan daftar semua instance Baileys_Gateway beserta status operasionalnya (aktif, tidak aktif, atau error).
2. WHILE instance Baileys_Gateway berstatus error, THE Superadmin_Dashboard SHALL menampilkan indikator visual yang jelas dan mencantumkan pesan kesalahan terakhir yang diterima dari instance tersebut.
3. WHEN Superadmin meminta restart instance Baileys_Gateway, THE WA_Automation_System SHALL mengirim perintah restart ke instance yang dituju dan mencatat tindakan tersebut di log sistem beserta identitas Superadmin yang melakukan tindakan.
4. IF perintah restart Baileys_Gateway tidak menghasilkan respons dalam 60 detik, THEN THE Superadmin_Dashboard SHALL menampilkan notifikasi kegagalan restart dan mempertahankan status instance sebagai error.

##### 13.5 Manajemen Pengguna

1. THE Superadmin_Dashboard SHALL menyediakan antarmuka untuk melihat, memperbarui, dan menonaktifkan akun pengguna lintas semua Tenant.
2. WHEN Superadmin mereset kata sandi pengguna, THE WA_Automation_System SHALL mengirim email berisi tautan reset kata sandi ke alamat email pengguna yang terdaftar dan mencatat tindakan di log sistem.
3. WHEN Superadmin mengubah peran pengguna, THE WA_Automation_System SHALL menerapkan perubahan peran secara langsung dan mencatat perubahan beserta identitas Superadmin yang melakukan tindakan di log sistem.
4. IF Superadmin mencoba menonaktifkan akun pengguna yang merupakan satu-satunya Admin aktif pada sebuah Tenant, THEN THE Superadmin_Dashboard SHALL menampilkan peringatan dan meminta konfirmasi eksplisit sebelum melanjutkan.

##### 13.6 Log Sistem Global

1. THE Superadmin_Dashboard SHALL menampilkan log sistem dari semua Tenant dalam satu tampilan terpadu dengan atribut: waktu kejadian, nama Tenant, jenis log, tingkat keparahan (info/warning/error), dan pesan log.
2. THE Superadmin_Dashboard SHALL menyediakan kemampuan filter log berdasarkan: nama Tenant, rentang tanggal, tingkat keparahan, dan kata kunci pada pesan log.
3. WHEN Superadmin mengekspor log sistem, THE Superadmin_Dashboard SHALL menghasilkan file CSV yang berisi semua entri log sesuai filter yang aktif dalam waktu 60 detik.
4. THE WA_Automation_System SHALL menyimpan log sistem global selama minimal 180 hari.

##### 13.7 Konfigurasi Sistem

1. THE Superadmin_Dashboard SHALL menyediakan antarmuka untuk mengubah parameter konfigurasi global platform mencakup: batas rate pengiriman default per Device per jam, kuota default untuk setiap Paket_Langganan baru, dan Delay_Interval minimum dan maksimum.
2. WHEN Superadmin mengaktifkan maintenance mode, THE WA_Automation_System SHALL menampilkan halaman pemeliharaan kepada semua Tenant yang mencoba mengakses Dashboard dan menghentikan pemrosesan Message_Job baru oleh Queue_Worker.
3. WHEN Superadmin menonaktifkan maintenance mode, THE WA_Automation_System SHALL memulihkan akses Dashboard untuk semua Tenant dan melanjutkan pemrosesan antrian Queue_Worker.
4. IF Superadmin menyimpan nilai konfigurasi yang berada di luar rentang yang diizinkan (misalnya batas rate kurang dari 1 atau lebih dari 1000 pesan per jam), THEN THE Superadmin_Dashboard SHALL menolak penyimpanan dan menampilkan pesan kesalahan yang menyebutkan rentang nilai yang valid.

##### 13.8 Notifikasi dan Alert

1. THE WA_Automation_System SHALL mengirim alert ke Superadmin ketika instance Baileys_Gateway tidak merespons selama lebih dari 5 menit.
2. THE WA_Automation_System SHALL mengirim alert ke Superadmin ketika penggunaan Kuota sebuah Tenant mencapai 90% dari batas paket langganannya.
3. THE WA_Automation_System SHALL mengirim alert ke Superadmin ketika terjadi error kritis di sistem, yaitu ketika jumlah Message_Job gagal permanen dalam satu jam melebihi 50 job.
4. THE Superadmin_Dashboard SHALL menampilkan daftar alert aktif yang belum ditangani beserta waktu kejadian dan tingkat keparahannya.
5. WHEN Superadmin menandai alert sebagai ditangani, THE WA_Automation_System SHALL memperbarui status alert menjadi "ditangani", mencatat waktu penanganan dan identitas Superadmin, dan menghapus alert dari daftar aktif.
6. THE WA_Automation_System SHALL mendukung pengiriman notifikasi alert melalui dua saluran: email ke alamat Superadmin yang dikonfigurasi dan notifikasi dalam aplikasi di Superadmin_Dashboard.

---

### Persyaratan 14: Masa Trial

**User Story:** Sebagai Tenant baru, saya ingin mendapatkan masa percobaan gratis sebelum berlangganan berbayar, sehingga saya dapat mengevaluasi fitur sistem sebelum memutuskan untuk berlangganan.

#### Kriteria Penerimaan

1. WHEN Tenant baru berhasil mendaftar, THE WA_Automation_System SHALL secara otomatis mengaktifkan masa Trial dengan durasi sesuai konfigurasi default yang ditetapkan Superadmin.
2. WHILE Tenant berada dalam masa Trial, THE WA_Automation_System SHALL membatasi akses Tenant hanya pada fitur dasar dengan kuota pesan terbatas sesuai konfigurasi Trial yang aktif.
3. THE WA_Automation_System SHALL menampilkan sisa hari Trial secara jelas di Dashboard Tenant selama masa Trial berlangsung.
4. WHEN sisa masa Trial Tenant mencapai 3 hari, THE WA_Automation_System SHALL mengirim notifikasi kepada Tenant melalui email dan pesan WhatsApp yang menginformasikan bahwa Trial akan segera berakhir.
5. WHEN sisa masa Trial Tenant mencapai 1 hari, THE WA_Automation_System SHALL mengirim notifikasi pengingat kedua kepada Tenant melalui email dan pesan WhatsApp.
6. WHEN masa Trial Tenant habis dan Tenant belum berlangganan, THE WA_Automation_System SHALL menonaktifkan akun Tenant secara otomatis dan menampilkan halaman informasi yang mengarahkan Tenant untuk berlangganan melalui Kontak_WA.
7. WHEN Superadmin memperpanjang masa Trial sebuah Tenant dari Superadmin_Dashboard, THE WA_Automation_System SHALL memperbarui tanggal berakhir Trial Tenant tersebut sesuai jumlah hari tambahan yang dimasukkan Superadmin dan mencatat tindakan di log sistem.
8. THE Superadmin_Dashboard SHALL menyediakan antarmuka untuk mengonfigurasi durasi Trial default (dalam satuan hari) yang akan diterapkan kepada setiap Tenant baru yang mendaftar.
9. IF Superadmin menyimpan nilai durasi Trial default kurang dari 1 hari, THEN THE Superadmin_Dashboard SHALL menolak penyimpanan dan menampilkan pesan kesalahan yang menyebutkan nilai minimum yang valid.

---

### Persyaratan 15: Manajemen Billing Manual

**User Story:** Sebagai Tenant, saya ingin dapat berlangganan paket layanan melalui proses yang jelas, sehingga saya dapat mengaktifkan atau memperpanjang langganan saya setelah melakukan pembayaran.

#### Kriteria Penerimaan

##### 15.1 Halaman Langganan untuk Tenant

1. THE WA_Automation_System SHALL menampilkan halaman langganan yang memuat daftar semua Paket_Langganan aktif beserta harga, deskripsi fitur, dan kuota pesan masing-masing paket.
2. THE WA_Automation_System SHALL menampilkan tombol atau tautan "Berlangganan" pada setiap Paket_Langganan yang mengarahkan Tenant ke Kontak_WA (wa.me/6281529211963) untuk memulai proses pemesanan.
3. THE WA_Automation_System SHALL menampilkan status langganan Tenant saat ini, mencakup nama paket aktif, tanggal mulai, tanggal berakhir Periode_Langganan, dan sisa Kuota pesan di halaman langganan.

##### 15.2 Pencatatan Pembayaran oleh Superadmin

1. THE Superadmin_Dashboard SHALL menyediakan antarmuka untuk Superadmin mencatat pembayaran masuk secara manual, dengan mengisi data: nama Tenant, Paket_Langganan yang dipilih, nominal pembayaran, tanggal pembayaran, dan durasi Periode_Langganan.
2. WHEN Superadmin menyimpan catatan pembayaran, THE WA_Automation_System SHALL membuat Invoice_Billing baru yang terhubung dengan Tenant yang bersangkutan dan menyimpan seluruh data pembayaran tersebut.
3. THE Superadmin_Dashboard SHALL menyediakan antarmuka untuk melihat, memperbarui, dan menghapus Invoice_Billing per Tenant.
4. THE WA_Automation_System SHALL mencatat riwayat pembayaran per Tenant dengan atribut: tanggal bayar, nominal, Paket_Langganan yang dipilih, Periode_Langganan, dan identitas Superadmin yang mencatat.

##### 15.3 Aktivasi dan Perpanjangan Langganan

1. WHEN Superadmin mengaktifkan langganan Tenant setelah pembayaran dikonfirmasi, THE WA_Automation_System SHALL memperbarui status langganan Tenant menjadi aktif, menetapkan Paket_Langganan yang sesuai, mengatur Periode_Langganan, dan mereset Kuota pesan ke nilai awal paket tersebut.
2. WHEN Superadmin memperpanjang langganan Tenant yang masih aktif, THE WA_Automation_System SHALL menambahkan durasi perpanjangan ke tanggal berakhir Periode_Langganan yang sedang berjalan tanpa mengubah sisa Kuota pesan yang ada.
3. WHEN langganan Tenant diaktifkan atau diperpanjang oleh Superadmin, THE WA_Automation_System SHALL mengirim notifikasi email kepada Tenant yang berisi informasi paket aktif, tanggal mulai, dan tanggal berakhir Periode_Langganan.

##### 15.4 Notifikasi Berakhirnya Langganan

1. WHEN sisa Periode_Langganan Tenant mencapai 7 hari, THE WA_Automation_System SHALL mengirim notifikasi email kepada Tenant yang menginformasikan bahwa langganan akan segera berakhir dan mengarahkan Tenant ke Kontak_WA untuk perpanjangan.
2. WHEN sisa Periode_Langganan Tenant mencapai 3 hari, THE WA_Automation_System SHALL mengirim notifikasi pengingat kedua kepada Tenant melalui email.
3. WHEN Periode_Langganan Tenant habis dan belum diperpanjang, THE WA_Automation_System SHALL menonaktifkan akses pengiriman pesan Tenant secara otomatis dan menampilkan informasi yang mengarahkan Tenant ke Kontak_WA.

##### 15.5 Laporan Pendapatan

1. THE Superadmin_Dashboard SHALL menyediakan halaman laporan pendapatan yang menampilkan total pendapatan per bulan, total pendapatan per Paket_Langganan, dan total pendapatan per Tenant untuk rentang tanggal yang dipilih.
2. WHEN Superadmin memilih rentang tanggal pada halaman laporan pendapatan, THE Superadmin_Dashboard SHALL memperbarui semua data laporan sesuai rentang tanggal yang dipilih dalam waktu 5 detik.
3. THE Superadmin_Dashboard SHALL menampilkan daftar Invoice_Billing yang dapat difilter berdasarkan nama Tenant, Paket_Langganan, dan rentang tanggal pembayaran.
