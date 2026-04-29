# Rencana Implementasi: Login Gmail (Google OAuth)

## Overview

Implementasi fitur login dan registrasi via Google OAuth 2.0 menggunakan Laravel Socialite. Fitur ini terintegrasi dengan arsitektur multi-tenant yang sudah ada, mendukung login pengguna terdaftar dan registrasi otomatis pengguna baru dengan pembuatan Tenant + User + Subscription trial dalam satu transaction.

## Tasks

- [x] 1. Setup dependensi dan konfigurasi Google OAuth
  - [x] 1.1 Install package Laravel Socialite dan tambahkan konfigurasi Google di `config/services.php`
    - Install `laravel/socialite` via Composer
    - Tambahkan entry `google` di `config/services.php` dengan `client_id`, `client_secret`, dan `redirect` dari environment variables
    - _Requirements: 6.1, 6.2_
  - [x] 1.2 Tambahkan environment variables Google OAuth di `.env.example`
    - Tambahkan `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, dan `GOOGLE_REDIRECT_URI` di `.env.example`
    - _Requirements: 6.1, 7.4_

- [x] 2. Migrasi database dan update model User
  - [x] 2.1 Buat migration untuk menambahkan kolom Google OAuth pada tabel `users`
    - Tambahkan kolom `google_id` (string, nullable, unique index) setelah `email_verified_at`
    - Tambahkan kolom `google_avatar` (string, nullable) setelah `google_id`
    - Ubah kolom `password` menjadi nullable
    - _Requirements: 4.1, 4.2, 4.3_
  - [x] 2.2 Update model `User` untuk menambahkan `google_id` dan `google_avatar` ke atribut fillable
    - Tambahkan `google_id` dan `google_avatar` ke `#[Fillable(...)]` attribute
    - _Requirements: 4.1, 4.2_
  - [x] 2.3 Tulis feature test untuk memverifikasi migration berjalan dengan benar
    - Test kolom `google_id` dan `google_avatar` ada setelah migration
    - Test kolom `password` menjadi nullable
    - Test index unik pada kolom `google_id`
    - _Requirements: 4.1, 4.2, 4.3_

- [x] 3. Implementasi service layer Google Auth
  - [x] 3.1 Buat interface `GoogleAuthServiceInterface` di `app/Services/Contracts/`
    - Definisikan method `findOrCreateUser(array $googleUser): User`
    - Gunakan PHPDoc array shape untuk parameter `$googleUser`
    - _Requirements: 2.1, 3.1, 3.2_
  - [x] 3.2 Buat `GoogleAuthService` di `app/Services/`
    - Implementasikan `findOrCreateUser()`: cari user berdasarkan email, jika ditemukan update `google_id` dan `google_avatar`, jika tidak buat Tenant + User + Subscription baru dalam DB transaction
    - Implementasikan `createTenantWithUser()`: ikuti pola yang sama dengan `RegisteredUserController::store()` — buat Tenant (status trial), User (role admin, is_active true, password null, email_verified_at diisi), dan Subscription (Starter Plan, durasi dari config `wa-automation.trial_duration_days`)
    - Tangani race condition dengan catch `QueryException` unique violation, fallback ke login user yang sudah ada
    - _Requirements: 2.2, 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 8.3_
  - [x] 3.3 Daftarkan binding `GoogleAuthServiceInterface` ke `GoogleAuthService` di `AppServiceProvider`
    - Tambahkan `$this->app->bind(GoogleAuthServiceInterface::class, GoogleAuthService::class)` di method `register()`
    - _Requirements: 2.1, 3.1_
  - [x] 3.4 Tulis property test: Registrasi pengguna baru via Google menghasilkan setup tenant yang lengkap
    - **Property 1: Registrasi pengguna baru via Google menghasilkan setup tenant yang lengkap**
    - Generate 100 profil Google acak dengan Faker, verifikasi Tenant + User + Subscription dibuat dengan benar untuk setiap iterasi
    - Verifikasi User memiliki role `admin`, `is_active` true, `google_id` terisi, `password` null, `email_verified_at` tidak null
    - Verifikasi Subscription terhubung ke Starter Plan dengan durasi sesuai konfigurasi
    - **Validates: Requirements 3.1, 3.2, 3.3, 3.4**
  - [x] 3.5 Tulis property test: Menghubungkan akun Google ke user yang sudah ada memperbarui data Google tanpa mengubah data lainnya
    - **Property 2: Menghubungkan akun Google ke user yang sudah ada memperbarui data Google tanpa mengubah data lainnya**
    - Generate 100 user acak + profil Google acak, verifikasi hanya `google_id` dan `google_avatar` berubah
    - Verifikasi `password`, `name`, `role`, `is_active`, `tenant_id` tetap tidak berubah
    - Verifikasi tidak ada Tenant atau Subscription baru yang dibuat
    - **Validates: Requirements 2.2, 8.3**
  - [x] 3.6 Tulis unit test untuk `GoogleAuthService`
    - Test pembuatan Tenant + User + Subscription untuk email baru
    - Test update google_id/google_avatar untuk user yang sudah ada
    - Test password tidak berubah saat linking
    - Test email_verified_at diisi untuk user baru
    - Test penanganan race condition
    - Test transaction rollback saat kegagalan database
    - _Requirements: 2.2, 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 8.3_

- [x] 4. Checkpoint — Pastikan semua test lulus
  - Pastikan semua test lulus, tanyakan ke user jika ada pertanyaan.

- [x] 5. Implementasi controller dan routes Google OAuth
  - [x] 5.1 Buat `GoogleAuthController` di `app/Http/Controllers/Auth/`
    - Implementasikan method `redirect()`: gunakan `Socialite::driver('google')->scopes(['openid', 'profile', 'email'])->redirect()`
    - Implementasikan method `callback()`: ambil data profil dari Google, delegasikan ke `GoogleAuthService::findOrCreateUser()`, login user, regenerasi session, redirect ke dashboard
    - Tangani `InvalidStateException` dengan redirect ke login dan pesan error "Sesi autentikasi tidak valid"
    - Tangani exception umum dari Socialite dengan redirect ke login dan pesan error "Tidak dapat terhubung ke layanan Google"
    - Tambahkan logging untuk error dan login sukses
    - _Requirements: 1.1, 1.2, 1.3, 2.1, 2.3, 2.4, 7.2, 7.3_
  - [x] 5.2 Tambahkan routes Google OAuth di `routes/auth.php`
    - Tambahkan `GET /auth/google/redirect` dengan name `auth.google.redirect` dalam group middleware `guest`
    - Tambahkan `GET /auth/google/callback` dengan name `auth.google.callback` dan middleware `throttle:10,1` dalam group middleware `guest`
    - _Requirements: 1.1, 2.1, 7.1_
  - [x] 5.3 Tulis feature test untuk alur redirect dan callback Google OAuth
    - Test redirect ke Google mengembalikan URL yang benar dengan scope yang sesuai
    - Test callback login user yang sudah ada dan redirect ke dashboard
    - Test callback registrasi user baru dengan pembuatan Tenant + Subscription
    - Test penanganan state tidak valid (InvalidStateException)
    - Test rate limiting pada callback route
    - Test session diregenerasi setelah login Google
    - _Requirements: 1.1, 1.2, 1.3, 2.1, 2.3, 2.4, 7.1, 7.2, 7.3_

- [x] 6. Modifikasi LoginRequest untuk menangani user Google-only
  - [x] 6.1 Update method `authenticate()` di `LoginRequest` untuk mendeteksi user tanpa password
    - Sebelum `Auth::attempt()`, cek apakah user dengan email tersebut memiliki `password` null
    - Jika ya, throw `ValidationException` dengan pesan "Akun ini terdaftar menggunakan Google. Silakan login dengan Google."
    - Pastikan alur login email/password yang sudah ada tetap berfungsi normal
    - _Requirements: 8.1, 8.2_
  - [x] 6.2 Tulis property test: User tanpa password tidak dapat login via form email/password
    - **Property 3: User tanpa password tidak dapat login via form email/password**
    - Generate 100 user acak tanpa password (google_id terisi), verifikasi login via form ditolak dengan pesan error yang sesuai
    - **Validates: Requirements 8.2**
  - [x] 6.3 Tulis feature test untuk kompatibilitas autentikasi
    - Test login email/password tetap berfungsi untuk user normal
    - Test user Google-only mendapat pesan error saat login via form
    - _Requirements: 8.1, 8.2_

- [x] 7. Update tampilan Blade (login dan register) dengan tombol Google
  - [x] 7.1 Tambahkan tombol "Login dengan Google" di `resources/views/auth/login.blade.php`
    - Tambahkan separator "Atau" dengan garis horizontal
    - Tambahkan tombol dengan ikon Google SVG yang mengarah ke route `auth.google.redirect`
    - Tombol hanya ditampilkan jika `config('services.google.client_id')` terisi
    - _Requirements: 5.1, 5.3, 5.4, 6.3_
  - [x] 7.2 Tambahkan tombol "Daftar dengan Google" di `resources/views/auth/register.blade.php`
    - Tambahkan separator "Atau" dengan garis horizontal
    - Tambahkan tombol dengan ikon Google SVG yang mengarah ke route `auth.google.redirect`
    - Tombol hanya ditampilkan jika `config('services.google.client_id')` terisi
    - _Requirements: 5.2, 5.3, 5.4, 6.3_
  - [x] 7.3 Tulis feature test untuk visibilitas tombol Google
    - Test tombol muncul saat `GOOGLE_CLIENT_ID` dan `GOOGLE_CLIENT_SECRET` dikonfigurasi
    - Test tombol tersembunyi saat konfigurasi kosong
    - _Requirements: 5.1, 5.2, 6.3_

- [x] 8. Checkpoint akhir — Pastikan semua test lulus
  - Pastikan semua test lulus, tanyakan ke user jika ada pertanyaan.

## Catatan

- Task yang ditandai dengan `*` bersifat opsional dan dapat dilewati untuk MVP yang lebih cepat
- Setiap task mereferensikan requirement spesifik untuk traceability
- Checkpoint memastikan validasi bertahap selama implementasi
- Property test memvalidasi correctness properties universal yang didefinisikan di dokumen design
- Unit test dan feature test memvalidasi contoh spesifik dan edge case
