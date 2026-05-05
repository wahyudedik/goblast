# Dokumen Persyaratan

## Pendahuluan

Fitur ini mencakup migrasi gateway WhatsApp GoBlast dari Baileys (Node.js custom gateway) ke WAHA (WhatsApp HTTP API). GoBlast adalah platform SaaS WhatsApp automation multi-tenant yang saat ini menggunakan `BaileysGatewayClient` untuk semua operasi WhatsApp. Migrasi ini mengganti lapisan gateway tanpa mengubah UI, skema database, atau kontrak fungsional yang terlihat oleh pengguna akhir. Semua 758 test yang ada harus tetap passing setelah migrasi selesai.

## Glosarium

- **WAHA**: WhatsApp HTTP API — layanan gateway WhatsApp berbasis HTTP yang berjalan di `https://wa.konektivitas.com`, versi Core 2026.4.2.
- **Session**: Konsep WAHA yang setara dengan "device" di Baileys. Setiap akun WhatsApp direpresentasikan sebagai satu session dengan nama string unik.
- **Session Name**: Nama string yang digunakan WAHA untuk mengidentifikasi session. Dalam implementasi ini, `gateway_device_id` (UUID) dari tabel `devices` digunakan sebagai session name.
- **WahaGatewayClient**: Implementasi baru dari `GatewayClientInterface` yang berkomunikasi dengan WAHA API.
- **GatewayClientInterface**: Interface PHP baru yang menggantikan `BaileysGatewayClientInterface`, mendefinisikan kontrak operasi gateway yang agnostik terhadap implementasi.
- **GatewayResponse**: Value object baru yang menggantikan `BaileysResponse`, merepresentasikan respons dari operasi gateway.
- **WebhookController**: Controller Laravel yang menerima dan memvalidasi webhook masuk dari gateway WhatsApp.
- **ProcessWebhookJob**: Laravel job yang memproses payload webhook secara asinkron.
- **DeviceService**: Service yang mengelola siklus hidup perangkat WhatsApp, bergantung pada `GatewayClientInterface`.
- **SendMessageJob**: Laravel job yang mengirim pesan melalui gateway client.
- **AutoReplyService**: Service yang memproses pesan masuk dan mengirim balasan otomatis berdasarkan keyword rules.
- **chatId**: Format nomor penerima yang digunakan WAHA, yaitu `{nomor_telepon}@c.us` (contoh: `628123456789@c.us`).
- **X-Api-Key**: Header autentikasi yang digunakan untuk semua request ke WAHA API.
- **Tenant**: Pelanggan SaaS yang memiliki satu atau lebih perangkat WhatsApp.

---

## Persyaratan

### Persyaratan 1: Pembuatan Interface Gateway yang Agnostik

**User Story:** Sebagai developer, saya ingin interface gateway yang tidak terikat pada implementasi Baileys, sehingga implementasi WAHA dapat digunakan tanpa mengubah kode yang bergantung pada interface tersebut.

#### Kriteria Penerimaan

1. THE `GatewayClientInterface` SHALL mendefinisikan method `sendMessage(string $sessionName, string $to, string $message): GatewayResponse`.
2. THE `GatewayClientInterface` SHALL mendefinisikan method `getQrCode(string $sessionName): string` yang mengembalikan string QR code (base64 atau URL).
3. THE `GatewayClientInterface` SHALL mendefinisikan method `getConnectionStatus(string $sessionName): string` yang mengembalikan salah satu dari: `'connected'`, `'disconnected'`, `'error'`.
4. THE `GatewayClientInterface` SHALL mendefinisikan method `disconnectDevice(string $sessionName): void`.
5. THE `GatewayClientInterface` SHALL mendefinisikan method `restartInstance(string $sessionName): void`.
6. THE `GatewayResponse` value object SHALL memiliki properti `success` (bool), `status` (string), `messageId` (?string), dan `errorMessage` (?string).
7. THE `AppServiceProvider` SHALL mengikat `GatewayClientInterface` ke `WahaGatewayClient` sebagai implementasi aktif.
8. THE `AppServiceProvider` SHALL menghapus binding `BaileysGatewayClientInterface` ke `BaileysGatewayClient`.

---

### Persyaratan 2: Implementasi WahaGatewayClient

**User Story:** Sebagai sistem, saya ingin `WahaGatewayClient` yang berkomunikasi dengan WAHA API, sehingga semua operasi WhatsApp (kirim pesan, QR code, status koneksi, disconnect, restart) berjalan melalui WAHA.

#### Kriteria Penerimaan

1. THE `WahaGatewayClient` SHALL mengimplementasikan `GatewayClientInterface`.
2. THE `WahaGatewayClient` SHALL membaca `base_url` dan `api_key` dari konfigurasi `wa-automation.waha`.
3. THE `WahaGatewayClient` SHALL menyertakan header `X-Api-Key` pada setiap request HTTP ke WAHA API.
4. WHEN `sendMessage` dipanggil, THE `WahaGatewayClient` SHALL mengirim POST request ke `/api/sendText` dengan body `{session: $sessionName, chatId: "{$to}@c.us", text: $message}`.
5. WHEN `sendMessage` berhasil (HTTP 2xx), THE `WahaGatewayClient` SHALL mengembalikan `GatewayResponse` dengan `success = true` dan `status = 'sent'`.
6. WHEN `sendMessage` gagal (HTTP non-2xx atau exception), THE `WahaGatewayClient` SHALL melempar `GatewayException` dengan pesan error yang deskriptif.
7. WHEN `getQrCode` dipanggil, THE `WahaGatewayClient` SHALL mengirim GET request ke `/api/{sessionName}/auth/qr` dengan query parameter `format=base64`.
8. WHEN `getQrCode` berhasil, THE `WahaGatewayClient` SHALL mengembalikan string base64 QR code dari field `value` pada respons JSON.
9. WHEN `getQrCode` gagal (HTTP non-2xx atau exception), THE `WahaGatewayClient` SHALL melempar `GatewayException`.
10. WHEN `getConnectionStatus` dipanggil, THE `WahaGatewayClient` SHALL mengirim GET request ke `/api/sessions/{sessionName}`.
11. WHEN `getConnectionStatus` menerima respons dengan `status = 'WORKING'`, THE `WahaGatewayClient` SHALL mengembalikan string `'connected'`.
12. WHEN `getConnectionStatus` menerima respons dengan `status` selain `'WORKING'` (misalnya `'STOPPED'`, `'FAILED'`, `'SCAN_QR_CODE'`), THE `WahaGatewayClient` SHALL mengembalikan string `'disconnected'`.
13. WHEN `getConnectionStatus` gagal karena exception jaringan, THE `WahaGatewayClient` SHALL mengembalikan string `'error'`.
14. WHEN `disconnectDevice` dipanggil, THE `WahaGatewayClient` SHALL mengirim POST request ke `/api/sessions/{sessionName}/stop`.
15. WHEN `disconnectDevice` gagal (HTTP non-2xx atau exception), THE `WahaGatewayClient` SHALL melempar `GatewayException`.
16. WHEN `restartInstance` dipanggil, THE `WahaGatewayClient` SHALL mengirim POST request ke `/api/sessions/{sessionName}/restart`.
17. WHEN `restartInstance` gagal (HTTP non-2xx atau exception), THE `WahaGatewayClient` SHALL melempar `GatewayException`.
18. THE `WahaGatewayClient` SHALL menggunakan timeout 30 detik dan connect timeout 10 detik untuk semua request HTTP.
19. THE `WahaGatewayClient` SHALL melakukan retry maksimal 3 kali dengan exponential backoff untuk request `sendMessage` yang gagal karena error jaringan sementara.

---

### Persyaratan 3: Manajemen Session WAHA

**User Story:** Sebagai sistem, saya ingin session WAHA dibuat dan dikonfigurasi secara otomatis saat perangkat baru didaftarkan, sehingga webhook dapat diterima dengan benar tanpa konfigurasi manual.

#### Kriteria Penerimaan

1. WHEN `DeviceService::requestConnection` dipanggil untuk perangkat baru, THE `WahaGatewayClient` SHALL membuat session WAHA baru via POST `/api/sessions` dengan `name = gateway_device_id` dan konfigurasi webhook.
2. WHEN session WAHA dibuat, THE `WahaGatewayClient` SHALL menyertakan konfigurasi webhook `{url: {webhook_url}, events: ['message', 'session.status']}` dalam payload pembuatan session.
3. THE `WahaGatewayClient` SHALL membaca `webhook_url` dari konfigurasi `wa-automation.waha.webhook_url`.
4. WHEN session WAHA sudah ada (HTTP 422 dari WAHA dengan body yang mengandung kata "exists" atau "already"), THE `WahaGatewayClient` SHALL melanjutkan tanpa melempar exception (idempotent). IF respons HTTP tidak dapat dikategorikan sebagai duplikat, THEN THE `WahaGatewayClient` SHALL melempar `GatewayException`.
5. WHEN `DeviceService::requestConnection` dipanggil, THE `WahaGatewayClient` SHALL memanggil POST `/api/sessions/{sessionName}/start` setelah session berhasil dibuat, dan SHALL menunggu hingga status session menjadi `'SCAN_QR_CODE'` (dengan polling GET `/api/sessions/{sessionName}` maksimal 5 kali dengan interval 500ms) sebelum memanggil `getQrCode`.
6. IF status session tidak mencapai `'SCAN_QR_CODE'` setelah polling maksimal, THEN THE `WahaGatewayClient` SHALL melempar `GatewayException` dengan pesan `'Session not ready for QR code'`.
7. WHEN `disconnectDevice` dipanggil, THE `WahaGatewayClient` SHALL memanggil DELETE `/api/sessions/{sessionName}` setelah session berhasil di-stop untuk membersihkan session dari WAHA.
8. WHEN `DeviceService::requestConnection` dipanggil untuk device yang sudah memiliki `gateway_device_id` di database (reconnect), THE `WahaGatewayClient` SHALL menangani pembuatan session secara idempotent sehingga session yang sudah ada di WAHA tidak menyebabkan error.

---

### Persyaratan 4: Pembaruan WebhookController untuk Format WAHA

**User Story:** Sebagai sistem, saya ingin `WebhookController` dapat menerima dan memvalidasi webhook dari WAHA secara aman, sehingga event WhatsApp dapat diproses dengan benar dan endpoint tidak dapat disalahgunakan.

#### Kriteria Penerimaan

1. THE `WebhookController` SHALL menyediakan endpoint baru `POST /webhook/waha` untuk menerima webhook dari WAHA.
2. WHEN webhook diterima di `/webhook/waha`, THE `WebhookController` SHALL memvalidasi token rahasia dengan membandingkan header `X-Webhook-Token` terhadap nilai `wa-automation.waha.webhook_token` dari konfigurasi.
3. IF header `X-Webhook-Token` tidak ada atau tidak cocok dengan konfigurasi, THEN THE `WebhookController` SHALL mengembalikan HTTP 401 dengan body `{"error": "Unauthorized"}` dan mencatat log warning dengan IP pengirim.
4. WHEN webhook diterima di `/webhook/waha`, THE `WebhookController` SHALL memvalidasi keberadaan field `event` dan `session` dalam payload.
5. IF field `event` atau `session` tidak ada dalam payload, THEN THE `WebhookController` SHALL mengembalikan HTTP 400 dengan body `{"error": "Malformed payload"}`.
6. WHEN webhook valid diterima, THE `WebhookController` SHALL mendispatch `ProcessWebhookJob` dengan payload WAHA yang sudah dinormalisasi.
7. WHEN webhook valid diterima, THE `WebhookController` SHALL mengembalikan HTTP 200 dengan body `{"success": true, "message": "Webhook processed"}`.
8. IF terjadi exception yang tidak tertangani, THEN THE `WebhookController` SHALL mengembalikan HTTP 500 dengan body `{"error": "Internal server error"}`.
9. THE `WebhookController` SHALL mempertahankan endpoint `/webhook/baileys` yang sudah ada agar tidak memutus integrasi yang sedang berjalan selama periode transisi.

---

### Persyaratan 5: Normalisasi Payload Webhook WAHA

**User Story:** Sebagai sistem, saya ingin payload webhook WAHA dinormalisasi ke format internal yang konsisten, sehingga `ProcessWebhookJob` dapat memproses event tanpa mengetahui sumber gateway.

#### Kriteria Penerimaan

1. THE `WebhookController` SHALL mengekstrak `session` dari payload WAHA sebagai `device_id` dalam format internal.
2. WHEN event WAHA adalah `message`, THE `WebhookController` SHALL mengekstrak `payload.from` sebagai `from` (dengan menghapus suffix `@c.us` jika ada) dan `payload.body` sebagai `message` dalam format internal.
3. WHEN event WAHA adalah `session.status`, THE `WebhookController` SHALL memetakan status WAHA ke event internal: `status = 'WORKING'` dipetakan ke event `session.restore_complete`, dan `status = 'FAILED'` dipetakan ke event `device.manual_intervention`.
4. WHEN event WAHA adalah `session.status` dengan `status = 'FAILED'`, THE `WebhookController` SHALL menyertakan field `message` dengan nilai `'Device requires manual intervention'` dalam payload yang dinormalisasi.
5. WHEN event WAHA tidak dikenali (bukan `message` atau `session.status`), THE `WebhookController` SHALL tetap mendispatch `ProcessWebhookJob` dengan event asli untuk logging.

---

### Persyaratan 6: Pembaruan ProcessWebhookJob untuk Event WAHA

**User Story:** Sebagai sistem, saya ingin `ProcessWebhookJob` dapat memproses event dari WAHA yang sudah dinormalisasi, sehingga auto-reply, alert, dan logging tetap berfungsi.

#### Kriteria Penerimaan

1. WHEN `ProcessWebhookJob` menerima event `session.restore_complete` (dipetakan dari `session.status` WAHA dengan `status = 'WORKING'`), THE `ProcessWebhookJob` SHALL membuat entri `SystemLog` dengan type `'gateway'`, severity `'info'`.
2. WHEN `ProcessWebhookJob` menerima event `device.manual_intervention` (dipetakan dari `session.status` WAHA dengan `status = 'FAILED'`), THE `ProcessWebhookJob` SHALL memperbarui status device menjadi `'error'` dan membuat alert dengan type `'gateway.down'` dan severity `'critical'`.
3. WHEN `ProcessWebhookJob` menerima event `message` (dari WAHA event `message`), THE `ProcessWebhookJob` SHALL memanggil `AutoReplyService::processIncomingMessage` dengan `device_id`, `from`, dan `message` yang sudah dinormalisasi.
4. WHEN `ProcessWebhookJob` menerima payload dengan field `device_id` yang tidak ditemukan di database, THE `ProcessWebhookJob` SHALL mencatat log warning dan menghentikan pemrosesan tanpa melempar exception.
5. WHEN event adalah `message` tetapi field `from` atau `message` kosong, THE `ProcessWebhookJob` SHALL mencatat log warning dan menghentikan pemrosesan.

---

### Persyaratan 7: Pembaruan Konfigurasi

**User Story:** Sebagai developer/operator, saya ingin konfigurasi gateway dipusatkan di `config/wa-automation.php` dengan key WAHA yang jelas, sehingga deployment dan perubahan konfigurasi mudah dilakukan.

#### Kriteria Penerimaan

1. THE `config/wa-automation.php` SHALL menambahkan section `waha` dengan key: `base_url`, `api_key`, `webhook_url`, `webhook_token`, `timeout`, `max_retries`, dan `retry_backoff`.
2. THE `config/wa-automation.php` SHALL membaca `base_url` dari environment variable `WAHA_BASE_URL` dengan default `'https://wa.konektivitas.com'`.
3. THE `config/wa-automation.php` SHALL membaca `api_key` dari environment variable `WAHA_API_KEY` tanpa default value (wajib diisi).
4. THE `config/wa-automation.php` SHALL membaca `webhook_url` dari environment variable `WAHA_WEBHOOK_URL` tanpa default value (wajib diisi).
5. THE `config/wa-automation.php` SHALL membaca `webhook_token` dari environment variable `WAHA_WEBHOOK_TOKEN` tanpa default value (wajib diisi).
6. THE `config/wa-automation.php` SHALL mempertahankan section `baileys` yang sudah ada untuk kompatibilitas mundur selama periode transisi.
7. THE `.env.example` SHALL menambahkan variabel `WAHA_BASE_URL`, `WAHA_API_KEY`, `WAHA_WEBHOOK_URL`, dan `WAHA_WEBHOOK_TOKEN` dengan nilai contoh yang jelas.

---

### Persyaratan 8: Pembaruan Dependensi di DeviceService dan SendMessageJob

**User Story:** Sebagai sistem, saya ingin `DeviceService` dan `SendMessageJob` menggunakan `GatewayClientInterface` yang baru, sehingga keduanya otomatis menggunakan `WahaGatewayClient` tanpa perubahan logika bisnis.

#### Kriteria Penerimaan

1. THE `DeviceService` SHALL mengganti dependensi `BaileysGatewayClientInterface` dengan `GatewayClientInterface`.
2. THE `SendMessageJob` SHALL mengganti dependensi `BaileysGatewayClientInterface` dengan `GatewayClientInterface`.
3. WHEN `DeviceService::requestConnection` dipanggil, THE `DeviceService` SHALL memanggil `GatewayClientInterface::getQrCode` dengan `gateway_device_id` sebagai session name.
4. WHEN `SendMessageJob` dieksekusi, THE `SendMessageJob` SHALL memanggil `GatewayClientInterface::sendMessage` dengan `gateway_device_id` sebagai session name.
5. THE `DeviceService` SHALL mempertahankan semua logika bisnis yang ada (validasi batas device, pembuatan record device, update status) tanpa perubahan.

---

### Persyaratan 9: Kompatibilitas Format Nomor Telepon

**User Story:** Sebagai sistem, saya ingin nomor telepon dikonversi ke format `chatId` WAHA secara otomatis, sehingga pesan terkirim ke nomor yang benar tanpa perubahan pada lapisan di atas gateway.

#### Kriteria Penerimaan

1. WHEN `WahaGatewayClient::sendMessage` dipanggil dengan nomor `to` tanpa suffix `@c.us`, THE `WahaGatewayClient` SHALL menambahkan suffix `@c.us` untuk membentuk `chatId` yang valid.
2. WHEN `WahaGatewayClient::sendMessage` dipanggil dengan nomor `to` yang sudah memiliki suffix `@c.us`, THE `WahaGatewayClient` SHALL menggunakan nomor tersebut apa adanya tanpa duplikasi suffix.
3. THE `WahaGatewayClient` SHALL menghapus karakter `+` di awal nomor telepon sebelum membentuk `chatId` (contoh: `+628123456789` → `628123456789@c.us`).

---

### Persyaratan 10: Kelengkapan Test Coverage

**User Story:** Sebagai developer, saya ingin semua komponen baru dan yang dimodifikasi memiliki test coverage yang memadai, sehingga regresi dapat terdeteksi secara otomatis.

#### Kriteria Penerimaan

1. THE test suite SHALL mencakup unit test untuk `WahaGatewayClient` yang memverifikasi setiap method (`sendMessage`, `getQrCode`, `getConnectionStatus`, `disconnectDevice`, `restartInstance`) dengan HTTP mock.
2. THE test suite SHALL mencakup test untuk `WebhookController` endpoint `/webhook/waha` yang memverifikasi: payload valid, payload malformed, dan dispatch job.
3. THE test suite SHALL mencakup test untuk normalisasi payload webhook: event `message`, event `session.status` dengan status `WORKING`, dan event `session.status` dengan status `FAILED`.
4. THE test suite SHALL mencakup test untuk `ProcessWebhookJob` dengan payload yang sudah dinormalisasi dari WAHA.
5. WHEN semua test dijalankan dengan `php artisan test --compact`, THE test suite SHALL menampilkan seluruh 758 test yang ada tetap passing ditambah test baru untuk komponen WAHA.
6. THE test suite SHALL mencakup test untuk konversi format nomor telepon ke `chatId` WAHA (dengan dan tanpa `@c.us`, dengan dan tanpa prefix `+`).
