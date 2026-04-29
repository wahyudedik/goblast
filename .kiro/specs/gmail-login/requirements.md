# Dokumen Requirements — Login Gmail (Google OAuth)

## Pendahuluan

Fitur ini memungkinkan pengguna untuk login dan mendaftar ke aplikasi menggunakan akun Google (Gmail) mereka melalui protokol OAuth 2.0. Fitur ini melengkapi sistem autentikasi email/password yang sudah ada (Laravel Breeze) dan terintegrasi dengan arsitektur multi-tenant aplikasi. Pengguna baru yang mendaftar via Google akan otomatis mendapatkan Tenant dan subscription trial, sama seperti alur registrasi manual yang sudah ada.

## Glosarium

- **Sistem**: Aplikasi Laravel (WA Automation) secara keseluruhan
- **OAuth_Controller**: Controller yang menangani alur redirect dan callback Google OAuth
- **Google_Provider**: Layanan pihak ketiga Google yang menyediakan autentikasi OAuth 2.0
- **User**: Model pengguna yang tersimpan di tabel `users`
- **Tenant**: Entitas organisasi/bisnis yang memiliki satu atau lebih User
- **Halaman_Login**: Halaman login yang ditampilkan kepada pengguna tamu (`auth/login`)
- **Halaman_Register**: Halaman registrasi yang ditampilkan kepada pengguna tamu (`auth/register`)
- **Google_ID**: Identifier unik pengguna dari Google (`google_id` pada tabel `users`)
- **Google_Avatar**: URL foto profil pengguna dari akun Google
- **Starter_Plan**: Paket langganan awal yang diberikan saat trial

## Requirements

### Requirement 1: Redirect ke Google OAuth

**User Story:** Sebagai pengguna tamu, saya ingin mengklik tombol "Login dengan Google" agar saya diarahkan ke halaman autentikasi Google.

#### Acceptance Criteria

1. WHEN pengguna mengklik tombol "Login dengan Google", THE OAuth_Controller SHALL mengarahkan pengguna ke halaman consent Google_Provider dengan scope `openid`, `profile`, dan `email`
2. THE OAuth_Controller SHALL menyertakan parameter `state` yang berisi CSRF token untuk mencegah serangan cross-site request forgery
3. IF Google_Provider tidak dapat dijangkau, THEN THE Sistem SHALL menampilkan pesan error "Tidak dapat terhubung ke layanan Google" dan mengarahkan pengguna kembali ke Halaman_Login

### Requirement 2: Callback dan Autentikasi Pengguna Terdaftar

**User Story:** Sebagai pengguna yang sudah terdaftar, saya ingin login menggunakan akun Google saya agar saya dapat mengakses dashboard tanpa memasukkan password.

#### Acceptance Criteria

1. WHEN Google_Provider mengirimkan callback dengan kode otorisasi yang valid, THE OAuth_Controller SHALL menukarkan kode tersebut dengan access token dan mengambil data profil pengguna (email, nama, Google_ID, Google_Avatar)
2. WHEN email dari Google_Provider cocok dengan email User yang sudah terdaftar, THE OAuth_Controller SHALL memperbarui kolom `google_id` dan `google_avatar` pada User tersebut
3. WHEN email dari Google_Provider cocok dengan email User yang sudah terdaftar, THE OAuth_Controller SHALL melakukan login otomatis dan mengarahkan User ke halaman dashboard
4. IF parameter `state` pada callback tidak valid, THEN THE OAuth_Controller SHALL menolak request dan mengarahkan pengguna ke Halaman_Login dengan pesan error "Sesi autentikasi tidak valid"

### Requirement 3: Registrasi Pengguna Baru via Google

**User Story:** Sebagai pengguna baru, saya ingin mendaftar menggunakan akun Google saya agar proses registrasi lebih cepat tanpa perlu mengisi formulir manual.

#### Acceptance Criteria

1. WHEN email dari Google_Provider tidak ditemukan di tabel User, THE OAuth_Controller SHALL membuat Tenant baru dengan nama dan email dari profil Google
2. WHEN email dari Google_Provider tidak ditemukan di tabel User, THE OAuth_Controller SHALL membuat User baru dengan role `admin`, `is_active` bernilai `true`, `google_id` dari Google_Provider, dan password bernilai `null`
3. WHEN User baru dibuat via Google OAuth, THE OAuth_Controller SHALL membuat subscription trial dengan Starter_Plan yang berlaku selama durasi trial yang dikonfigurasi di `wa-automation.trial_duration_days`
4. WHEN User baru dibuat via Google OAuth, THE OAuth_Controller SHALL menandai email User sebagai terverifikasi (`email_verified_at` diisi dengan waktu saat ini) karena Google sudah memverifikasi kepemilikan email
5. WHEN User baru berhasil dibuat, THE OAuth_Controller SHALL menjalankan seluruh proses pembuatan Tenant, User, dan Subscription dalam satu database transaction
6. IF pembuatan User baru gagal karena race condition (email sudah terdaftar oleh proses lain), THEN THE OAuth_Controller SHALL melakukan login ke User yang sudah ada

### Requirement 4: Migrasi Database

**User Story:** Sebagai developer, saya ingin tabel `users` memiliki kolom untuk menyimpan data Google OAuth agar sistem dapat mengidentifikasi pengguna yang login via Google.

#### Acceptance Criteria

1. THE Sistem SHALL menambahkan kolom `google_id` bertipe string nullable dengan index unik pada tabel `users`
2. THE Sistem SHALL menambahkan kolom `google_avatar` bertipe string nullable pada tabel `users`
3. THE Sistem SHALL mengubah kolom `password` pada tabel `users` menjadi nullable agar pengguna yang mendaftar via Google tidak memerlukan password

### Requirement 5: Tombol Login Google pada Halaman Login dan Register

**User Story:** Sebagai pengguna tamu, saya ingin melihat tombol "Login dengan Google" di halaman login dan register agar saya tahu bahwa opsi login via Google tersedia.

#### Acceptance Criteria

1. THE Halaman_Login SHALL menampilkan tombol "Login dengan Google" dengan ikon Google yang ditempatkan di antara form login dan tautan "Belum punya akun?"
2. THE Halaman_Register SHALL menampilkan tombol "Daftar dengan Google" dengan ikon Google yang ditempatkan di antara form registrasi dan tautan "Sudah punya akun?"
3. THE Sistem SHALL menampilkan separator visual berupa garis horizontal dengan teks "Atau" di antara form dan tombol Google
4. WHEN tombol Google diklik, THE Sistem SHALL mengarahkan pengguna ke route OAuth redirect menggunakan metode GET

### Requirement 6: Konfigurasi Google OAuth

**User Story:** Sebagai developer, saya ingin mengkonfigurasi kredensial Google OAuth melalui environment variables agar konfigurasi dapat berbeda di setiap environment (development, staging, production).

#### Acceptance Criteria

1. THE Sistem SHALL membaca `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, dan `GOOGLE_REDIRECT_URI` dari environment variables
2. THE Sistem SHALL mendaftarkan konfigurasi Google OAuth pada file `config/services.php` dengan key `google`
3. IF `GOOGLE_CLIENT_ID` atau `GOOGLE_CLIENT_SECRET` tidak dikonfigurasi, THEN THE Sistem SHALL menyembunyikan tombol "Login dengan Google" dari Halaman_Login dan Halaman_Register

### Requirement 7: Keamanan dan Validasi

**User Story:** Sebagai pemilik sistem, saya ingin memastikan alur login Google aman dari serangan dan penyalahgunaan agar data pengguna tetap terlindungi.

#### Acceptance Criteria

1. THE OAuth_Controller SHALL menerapkan rate limiting pada route callback dengan maksimal 10 request per menit per IP address
2. THE OAuth_Controller SHALL memvalidasi bahwa email yang diterima dari Google_Provider memiliki format email yang valid
3. WHILE User login via Google OAuth, THE Sistem SHALL meregenerasi session ID untuk mencegah session fixation attack
4. THE Sistem SHALL menyimpan `GOOGLE_CLIENT_SECRET` hanya di environment variables dan tidak menyimpannya di source code atau file konfigurasi yang di-commit ke repository

### Requirement 8: Kompatibilitas dengan Autentikasi yang Sudah Ada

**User Story:** Sebagai pengguna yang sudah terdaftar dengan email/password, saya ingin tetap bisa login dengan cara lama meskipun fitur Google OAuth sudah ditambahkan.

#### Acceptance Criteria

1. WHEN User yang memiliki password login menggunakan email dan password, THE Sistem SHALL memproses login seperti biasa tanpa terpengaruh oleh fitur Google OAuth
2. WHEN User yang mendaftar via Google (password bernilai `null`) mencoba login menggunakan form email/password, THE Sistem SHALL menampilkan pesan error "Akun ini terdaftar menggunakan Google. Silakan login dengan Google."
3. WHEN User yang sudah terdaftar dengan email/password login via Google OAuth untuk pertama kali, THE OAuth_Controller SHALL menghubungkan akun Google dengan mengisi kolom `google_id` dan `google_avatar` tanpa mengubah password yang sudah ada
