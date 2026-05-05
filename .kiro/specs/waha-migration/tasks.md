# Rencana Implementasi: Migrasi WAHA

## Ikhtisar

Migrasi lapisan gateway WhatsApp dari `BaileysGatewayClient` ke `WahaGatewayClient` dengan memperkenalkan abstraksi `GatewayClientInterface` + `GatewayResponse`. Semua komponen di atas lapisan gateway (`DeviceService`, `SendMessageJob`, `ProcessWebhookJob`) tidak perlu mengetahui detail implementasi gateway. Seluruh 758 test yang ada harus tetap passing setelah setiap task selesai.

> **Catatan PBT:** PHPUnit 12 tidak kompatibel dengan library eris/eris. Semua "property-like tests" menggunakan `#[DataProvider]` dengan data provider yang mencakup banyak kombinasi input — mengikuti pola yang sudah ada di `tests/Unit/PropertyBased/`.

---

## Tasks

- [x] 1. Buat abstraksi gateway: `GatewayClientInterface` dan `GatewayResponse`
  - Buat `app/Services/Contracts/GatewayClientInterface.php` dengan method: `sendMessage`, `getQrCode`, `getConnectionStatus`, `disconnectDevice`, `restartInstance`
  - Buat `app/Services/ValueObjects/GatewayResponse.php` sebagai readonly class dengan properti: `success`, `status`, `messageId`, `errorMessage`
  - Pastikan signature method identik dengan `BaileysGatewayClientInterface` kecuali nama parameter (`$sessionName` bukan `$deviceId`) dan return type `sendMessage` menggunakan `GatewayResponse`
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6_

  - [x] 1.1 Tulis property test untuk `GatewayResponse` (Property 1)
    - **Property 1: GatewayResponse menyimpan nilai dengan benar**
    - Buat `tests/Unit/PropertyBased/GatewayResponsePropertyTest.php`
    - Gunakan `#[DataProvider]` dengan kombinasi: `success` (true/false), `status` (berbagai string), `messageId` (null/string), `errorMessage` (null/string)
    - Verifikasi bahwa setiap properti yang dibaca identik dengan yang diberikan saat konstruksi
    - **Validates: Requirements 1.6**

- [x] 2. Tambahkan konfigurasi WAHA dan variabel environment
  - Tambahkan section `waha` ke `config/wa-automation.php` dengan key: `base_url`, `api_key`, `webhook_url`, `webhook_token`, `timeout`, `max_retries`, `retry_backoff`
  - Baca nilai dari env vars: `WAHA_BASE_URL` (default `https://wa.konektivitas.com`), `WAHA_API_KEY`, `WAHA_WEBHOOK_URL`, `WAHA_WEBHOOK_TOKEN`
  - Pertahankan section `baileys` yang sudah ada
  - Tambahkan `WAHA_BASE_URL`, `WAHA_API_KEY`, `WAHA_WEBHOOK_URL`, `WAHA_WEBHOOK_TOKEN` ke `.env.example` dengan nilai contoh yang jelas
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7_

- [x] 3. Implementasi `WahaGatewayClient`
  - Buat `app/Services/WahaGatewayClient.php` yang mengimplementasikan `GatewayClientInterface`
  - Constructor membaca `base_url` dan `api_key` dari `config('wa-automation.waha')`
  - Semua request HTTP menyertakan header `X-Api-Key` dan menggunakan timeout 30 detik / connect timeout 10 detik
  - Implementasi konversi nomor telepon ke `chatId`: hapus prefix `+`, tambahkan suffix `@c.us` jika belum ada
  - Implementasi `sendMessage`: POST ke `/api/sendText` dengan body `{session, chatId, text}`, retry 3x dengan exponential backoff untuk error jaringan, kembalikan `GatewayResponse(success: true, status: 'sent')` untuk HTTP 2xx, lempar `GatewayException` untuk HTTP non-2xx
  - Implementasi `getQrCode`: (1) POST `/api/sessions` dengan konfigurasi webhook, tangani HTTP 422 "exists"/"already" sebagai idempotent; (2) POST `/api/sessions/{name}/start`; (3) polling GET `/api/sessions/{name}` maks 5x interval 500ms hingga status `SCAN_QR_CODE`; (4) GET `/api/{name}/auth/qr?format=base64`, kembalikan field `value`. Tambahkan komentar PHPDoc bahwa polling bersifat blocking — acceptable karena dipanggil dari job/controller yang sudah async
  - Implementasi `getConnectionStatus`: GET `/api/sessions/{name}`, kembalikan `'connected'` jika status `WORKING`, `'disconnected'` untuk status lain, `'error'` jika exception jaringan
  - Implementasi `disconnectDevice`: POST `/api/sessions/{name}/stop`, lalu DELETE `/api/sessions/{name}`, lempar `GatewayException` jika gagal
  - Implementasi `restartInstance`: POST `/api/sessions/{name}/restart`, lempar `GatewayException` jika gagal
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 2.8, 2.9, 2.10, 2.11, 2.12, 2.13, 2.14, 2.15, 2.16, 2.17, 2.18, 2.19, 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 9.1, 9.2, 9.3_

  - [x] 3.1 Tulis property test untuk konversi nomor telepon ke chatId (Property 14)
    - **Property 14: Konversi nomor telepon ke chatId**
    - Buat `tests/Unit/PropertyBased/WahaGatewayClientPropertyTest.php`
    - Gunakan `#[DataProvider]` dengan kombinasi: nomor tanpa prefix/suffix, dengan `+`, dengan `@c.us`, dengan keduanya
    - Verifikasi output selalu `{nomor_tanpa_plus}@c.us` tanpa duplikasi
    - **Validates: Requirements 9.1, 9.2, 9.3**

  - [x] 3.2 Tulis property test untuk header X-Api-Key (Property 2)
    - **Property 2: Header X-Api-Key selalu disertakan**
    - Tambahkan ke `tests/Unit/PropertyBased/WahaGatewayClientPropertyTest.php`
    - Gunakan `Http::fake()` dan `#[DataProvider]` untuk method sederhana: `sendMessage`, `getConnectionStatus`, `disconnectDevice`, `restartInstance`
    - Untuk `getQrCode`, buat test terpisah yang mock semua 4 endpoint berurutan (POST `/api/sessions`, POST `/api/sessions/{name}/start`, GET `/api/sessions/{name}` dengan status `SCAN_QR_CODE`, GET `/api/{name}/auth/qr`) — verifikasi header `X-Api-Key` ada di setiap request menggunakan `Http::assertSent()`
    - Verifikasi setiap HTTP request menyertakan header `X-Api-Key` dengan nilai dari konfigurasi
    - **Validates: Requirements 2.3**

  - [x] 3.3 Tulis property test untuk format request `sendMessage` (Property 3)
    - **Property 3: sendMessage memformat request dengan benar**
    - Tambahkan ke `tests/Unit/PropertyBased/WahaGatewayClientPropertyTest.php`
    - Gunakan `#[DataProvider]` dengan kombinasi `sessionName`, `to`, `message`
    - Verifikasi body request mengandung `session = sessionName`, `chatId` dalam format `{to}@c.us`, `text = message`
    - **Validates: Requirements 2.4, 9.1, 9.2, 9.3**

  - [x] 3.4 Tulis property test untuk `sendMessage` sukses (Property 4)
    - **Property 4: sendMessage sukses mengembalikan GatewayResponse yang benar**
    - Tambahkan ke `tests/Unit/PropertyBased/WahaGatewayClientPropertyTest.php`
    - Gunakan `#[DataProvider]` dengan berbagai HTTP 2xx response codes
    - Verifikasi `GatewayResponse` memiliki `success = true` dan `status = 'sent'`
    - **Validates: Requirements 2.5**

  - [x] 3.5 Tulis property test untuk `sendMessage` gagal (Property 5)
    - **Property 5: sendMessage gagal melempar GatewayException**
    - Tambahkan ke `tests/Unit/PropertyBased/WahaGatewayClientPropertyTest.php`
    - Gunakan `#[DataProvider]` dengan berbagai HTTP 4xx dan 5xx response codes
    - Verifikasi `GatewayException` dilempar untuk setiap kode error
    - **Validates: Requirements 2.6**

  - [x] 3.6 Tulis property test untuk format URL `getQrCode` (Property 6)
    - **Property 6: getQrCode memformat URL request dengan benar**
    - Tambahkan ke `tests/Unit/PropertyBased/WahaGatewayClientPropertyTest.php`
    - Gunakan `#[DataProvider]` dengan berbagai `sessionName`
    - Verifikasi GET request dikirim ke URL yang mengandung `/{sessionName}/auth/qr` dengan `format=base64`
    - **Validates: Requirements 2.7**

  - [x] 3.7 Tulis property test untuk return value `getQrCode` (Property 7)
    - **Property 7: getQrCode mengembalikan nilai dari field value**
    - Tambahkan ke `tests/Unit/PropertyBased/WahaGatewayClientPropertyTest.php`
    - Gunakan `#[DataProvider]` dengan berbagai string base64
    - Verifikasi string yang dikembalikan identik dengan field `value` dari respons WAHA
    - **Validates: Requirements 2.8**

  - [x] 3.8 Tulis property test untuk mapping status `getConnectionStatus` (Property 8)
    - **Property 8: getConnectionStatus memetakan status non-WORKING ke 'disconnected'**
    - Tambahkan ke `tests/Unit/PropertyBased/WahaGatewayClientPropertyTest.php`
    - Gunakan `#[DataProvider]` dengan status: `STOPPED`, `FAILED`, `SCAN_QR_CODE`, `STARTING`, string acak lainnya
    - Verifikasi semua status non-`WORKING` menghasilkan return value `'disconnected'`
    - **Validates: Requirements 2.12**

  - [x] 3.9 Tulis property test untuk format URL `disconnectDevice` dan `restartInstance` (Property 9)
    - **Property 9: disconnectDevice dan restartInstance memformat URL dengan benar**
    - Tambahkan ke `tests/Unit/PropertyBased/WahaGatewayClientPropertyTest.php`
    - Gunakan `#[DataProvider]` dengan berbagai `sessionName`
    - Verifikasi `disconnectDevice` mengirim POST ke `/api/sessions/{sessionName}/stop` **dan** DELETE ke `/api/sessions/{sessionName}` (dua request berurutan)
    - Verifikasi `restartInstance` mengirim POST ke `/api/sessions/{sessionName}/restart`
    - **Validates: Requirements 2.14, 2.16, 3.7**

  - [x] 3.10 Tulis unit test example untuk `WahaGatewayClient`
    - Buat `tests/Unit/Services/WahaGatewayClientTest.php`
    - Test: `getConnectionStatus` dengan status `WORKING` → return `'connected'`
    - Test: `getConnectionStatus` dengan network exception → return `'error'`
    - Test: retry 3x untuk `sendMessage` dengan network error sementara
    - Test: HTTP 422 "exists" tidak melempar exception (idempotent session creation)
    - Test: polling `SCAN_QR_CODE` sukses setelah N polling
    - Test: polling gagal setelah 5x → `GatewayException('Session not ready for QR code')`
    - Test: `disconnectDevice` memanggil stop lalu delete session
    - _Requirements: 2.11, 2.12, 2.13, 2.19, 3.4, 3.5, 3.6, 3.7_

- [x] 4. Checkpoint — Pastikan semua test passing
  - Jalankan `php artisan test --compact` dan pastikan seluruh 758 test yang ada tetap passing ditambah test baru.
  - Tanyakan kepada user jika ada pertanyaan sebelum melanjutkan.

- [x] 5. Perbarui `DeviceService` dan `SendMessageJob`: ganti tipe dependensi
  - Di `DeviceService`: ganti `BaileysGatewayClientInterface` → `GatewayClientInterface` pada constructor dan semua type hint
  - Di `SendMessageJob`: ganti `BaileysGatewayClientInterface` → `GatewayClientInterface` pada method `handle()` dan semua type hint
  - Di `SendMessageJob`: ganti `BaileysResponse` → `GatewayResponse` pada akses properti (properti `success`, `status`, `errorMessage` identik — tidak ada perubahan logika)
  - Semua logika bisnis dipertahankan tanpa perubahan
  - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_

  - [x] 5.1 Tulis test untuk `DeviceService` menggunakan `GatewayClientInterface`
    - Buat atau perbarui `tests/Unit/Services/DeviceServiceTest.php`
    - Test: `DeviceService::requestConnection` memanggil `GatewayClientInterface::getQrCode` dengan `gateway_device_id` sebagai session name
    - Test: `DeviceService::checkConnectionStatus` memanggil `GatewayClientInterface::getConnectionStatus`
    - Test: `DeviceService::disconnect` memanggil `GatewayClientInterface::disconnectDevice`
    - Mock `GatewayClientInterface` (bukan `BaileysGatewayClientInterface`)
    - _Requirements: 8.1, 8.3, 8.5_

  - [x] 5.2 Tulis test untuk `SendMessageJob` menggunakan `GatewayClientInterface`
    - Buat atau perbarui `tests/Unit/Jobs/SendMessageJobTest.php`
    - Test: `SendMessageJob` memanggil `GatewayClientInterface::sendMessage` dengan `gateway_device_id` sebagai session name
    - Test: `GatewayResponse` dengan `success = true` dan `status = 'sent'` → message log diupdate ke `sent`
    - Mock `GatewayClientInterface` (bukan `BaileysGatewayClientInterface`)
    - _Requirements: 8.2, 8.4_

- [x] 6. Perbarui `AppServiceProvider`: ganti binding DI container
  - **Lakukan setelah task 5** — pada titik ini `DeviceService` dan `SendMessageJob` sudah menggunakan `GatewayClientInterface`, sehingga penggantian binding tidak menyebabkan container resolution error
  - Hapus binding `BaileysGatewayClientInterface::class → BaileysGatewayClient::class`
  - Tambahkan binding `GatewayClientInterface::class → WahaGatewayClient::class`
  - Pertahankan semua binding lain yang sudah ada
  - `BaileysGatewayClient` dan `BaileysGatewayClientInterface` **tidak dihapus** dari codebase — hanya tidak di-bind
  - _Requirements: 1.7, 1.8_

  - [x] 6.1 Tulis test integrasi untuk DI container
    - Tambahkan ke `tests/Unit/Services/WahaGatewayClientTest.php` atau buat `tests/Feature/Integration/GatewayBindingTest.php`
    - Test: DI container me-resolve `GatewayClientInterface` ke instance `WahaGatewayClient`
    - Test: `DeviceService` dapat di-resolve dari container (tidak ada error binding)
    - Test: `SendMessageJob` dapat di-resolve dari container
    - _Requirements: 1.7, 1.8_

- [x] 7. Perbarui `ProcessWebhookJob`: validasi payload per-event-type
  - Perbarui method `validatePayload()` agar validasi field wajib berbeda per event:
    - Event `message`: wajib ada `event`, `device_id`, `from`, `message`
    - Event `session.restore_complete` dan `device.manual_intervention`: wajib ada `event` dan `device_id`
    - Event lain (unknown): wajib ada `event` dan `device_id`
  - Perbarui `handleDefault()` agar tidak langsung mengakses `$payload['from']` dan `$payload['message']` tanpa guard — tambahkan pengecekan keberadaan field sebelum akses, dan log warning + return jika tidak ada (sesuai Requirements 6.5)
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

  - [x] 7.1 Tulis test untuk `ProcessWebhookJob` dengan payload WAHA yang dinormalisasi
    - Perbarui `tests/Unit/ProcessWebhookJobTest.php`
    - Test: event `session.restore_complete` dengan hanya field `event` + `device_id` → validasi lulus, `SystemLog` dibuat
    - Test: event `device.manual_intervention` dengan hanya field `event` + `device_id` → validasi lulus, device status diupdate
    - Test: event `message` tanpa field `from` → validasi gagal, tidak ada pemrosesan
    - Test: event `message` tanpa field `message` → validasi gagal, tidak ada pemrosesan
    - Test: event unknown dengan `event` + `device_id` → validasi lulus, job diproses
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [x] 8. Tambahkan route `/webhook/waha` dan perbarui `WebhookController`
  - Tambahkan route ke `routes/web.php`:
    ```php
    Route::post('/webhook/waha', [WebhookController::class, 'waha'])->name('webhook.waha');
    ```
  - Tambahkan method `waha()` ke `WebhookController` dengan alur:
    1. Validasi header `X-Webhook-Token` menggunakan `hash_equals()` terhadap `config('wa-automation.waha.webhook_token')` → HTTP 401 jika tidak cocok. **Perhatian:** jika config bernilai `null` (env tidak di-set), `hash_equals()` akan throw `TypeError` di PHP 8.4 — cast ke string terlebih dahulu atau return 401 langsung jika config null
    2. Validasi keberadaan field `event` dan `session` → HTTP 400 jika tidak ada
    3. Normalisasi payload ke format internal (lihat tabel di design)
    4. Dispatch `ProcessWebhookJob` dengan payload yang sudah dinormalisasi
    5. Return HTTP 200 `{"success": true, "message": "Webhook processed"}`
  - Pertahankan method `baileys()` yang sudah ada tanpa perubahan
  - Catatan: CSRF sudah dikecualikan untuk `webhook/*` di `bootstrap/app.php` — tidak perlu perubahan
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8, 4.9, 5.1, 5.2, 5.3, 5.4, 5.5_

  - [x] 8.1 Tulis property test untuk validasi token webhook (Property 12)
    - **Property 12: Validasi token webhook menolak semua token yang tidak cocok**
    - Buat `tests/Feature/WahaWebhookControllerTest.php`
    - Gunakan `#[DataProvider]` dengan berbagai string token yang salah (string acak, string kosong, token hampir benar)
    - Verifikasi semua token yang tidak cocok menghasilkan HTTP 401
    - Verifikasi hanya token yang tepat menghasilkan HTTP 200
    - **Validates: Requirements 4.2, 4.3**

  - [x] 8.2 Tulis property test untuk validasi payload webhook (Property 13)
    - **Property 13: Validasi payload webhook menolak payload tanpa field wajib**
    - Tambahkan ke `tests/Feature/WahaWebhookControllerTest.php`
    - Gunakan `#[DataProvider]` dengan payload yang tidak memiliki `event`, tidak memiliki `session`, atau tidak memiliki keduanya
    - Verifikasi semua payload tersebut menghasilkan HTTP 400
    - **Validates: Requirements 4.4**

  - [x] 8.3 Tulis property test untuk normalisasi payload event `message` (Property 10)
    - **Property 10: Payload webhook WAHA dinormalisasi dengan benar**
    - Tambahkan ke `tests/Feature/WahaWebhookControllerTest.php`
    - Gunakan `#[DataProvider]` dengan berbagai kombinasi `session`, `payload.from` (dengan/tanpa `@c.us`), `payload.body`
    - Verifikasi `ProcessWebhookJob` di-dispatch dengan `device_id = session`, `from` tanpa `@c.us`, `message = payload.body`
    - **Validates: Requirements 5.1, 5.2**

  - [x] 8.4 Tulis property test untuk mapping event `session.status` (Property 11)
    - **Property 11: Mapping status session.status ke event internal**
    - Tambahkan ke `tests/Feature/WahaWebhookControllerTest.php`
    - Gunakan `#[DataProvider]` dengan status: `WORKING` → `session.restore_complete`, `FAILED` → `device.manual_intervention`, status lain → event asli
    - Verifikasi `ProcessWebhookJob` di-dispatch dengan event yang tepat
    - **Validates: Requirements 5.3**

  - [x] 8.5 Tulis example test untuk `WebhookController` endpoint `/webhook/waha`
    - Tambahkan ke `tests/Feature/WahaWebhookControllerTest.php`
    - Test: webhook valid dengan token benar → HTTP 200 + job dispatched
    - Test: event `session.status` FAILED → field `message` ada di payload yang dinormalisasi
    - Test: event tidak dikenal → job dispatched dengan event asli
    - Test: endpoint `/webhook/baileys` masih berfungsi (backward compatibility)
    - _Requirements: 4.6, 4.7, 4.9, 5.4, 5.5_

- [x] 9. Checkpoint — Pastikan semua test passing
  - Jalankan `php artisan test --compact` dan pastikan seluruh test (758 existing + test baru) passing.
  - Tanyakan kepada user jika ada pertanyaan sebelum melanjutkan.

- [x] 10. Wiring akhir: jalankan Pint dan verifikasi keseluruhan
  - Jalankan `vendor/bin/pint --dirty --format agent` untuk memastikan semua file PHP baru/dimodifikasi mengikuti code style proyek
  - Jalankan `php artisan test --compact` untuk verifikasi final seluruh test suite
  - _Requirements: 10.5_

---

## Catatan

- Task bertanda `*` bersifat opsional dan dapat dilewati untuk MVP yang lebih cepat
- Setiap task mereferensikan requirements spesifik untuk traceability
- Checkpoint di task 4 dan 9 memastikan validasi inkremental
- Property tests menggunakan `#[DataProvider]` (PHPUnit 12 native) — bukan library eris/eris yang tidak kompatibel
- `BaileysGatewayClient` dan `BaileysGatewayClientInterface` **tidak dihapus** — hanya tidak di-bind di container
- CSRF sudah dikecualikan untuk `webhook/*` di `bootstrap/app.php` — tidak perlu perubahan tambahan
- Polling di `getQrCode()` bersifat blocking — didokumentasikan di kode bahwa ini acceptable karena dipanggil dari job/controller yang sudah async
