# Dokumen Desain: Migrasi WAHA

## Ikhtisar

Fitur ini mengganti lapisan gateway WhatsApp GoBlast dari Baileys (Node.js custom gateway) ke **WAHA** (WhatsApp HTTP API) yang berjalan di `https://wa.konektivitas.com`. Migrasi dilakukan dengan memperkenalkan abstraksi baru (`GatewayClientInterface` + `GatewayResponse`) sehingga semua kode di atas lapisan gateway — `DeviceService`, `SendMessageJob`, `ProcessWebhookJob` — tidak perlu mengetahui detail implementasi gateway yang digunakan.

Tujuan utama:
- Mengganti `BaileysGatewayClient` dengan `WahaGatewayClient` tanpa mengubah logika bisnis.
- Mempertahankan endpoint `/webhook/baileys` selama periode transisi.
- Menambahkan endpoint `/webhook/waha` dengan normalisasi payload ke format internal yang sudah ada.
- Seluruh 758 test yang ada tetap passing setelah migrasi.

---

## Arsitektur

### Gambaran Umum Lapisan

```
┌─────────────────────────────────────────────────────────────┐
│                      HTTP Layer                             │
│  POST /webhook/waha          POST /webhook/baileys          │
│  WebhookController::waha()   WebhookController::baileys()   │
└──────────────────┬──────────────────────────────────────────┘
                   │ normalize payload → dispatch
                   ▼
┌─────────────────────────────────────────────────────────────┐
│                   Queue Layer                               │
│              ProcessWebhookJob                              │
│  (event: message | session.restore_complete |               │
│          device.manual_intervention | unknown)              │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│                  Service Layer                              │
│   DeviceService          AutoReplyService                   │
│   (GatewayClientInterface)                                  │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│               Gateway Client Layer                          │
│         GatewayClientInterface (contract)                   │
│         ┌──────────────────────────────┐                    │
│         │     WahaGatewayClient        │  ← implementasi    │
│         │  (menggantikan Baileys)      │    aktif           │
│         └──────────────────────────────┘                    │
│         ┌──────────────────────────────┐                    │
│         │   BaileysGatewayClient       │  ← dipertahankan   │
│         │   (legacy, tidak di-bind)    │    untuk referensi │
│         └──────────────────────────────┘                    │
└──────────────────┬──────────────────────────────────────────┘
                   │ HTTP (X-Api-Key)
                   ▼
         WAHA API (https://wa.konektivitas.com)
```

### Alur Pendaftaran Device Baru

```
DeviceService::requestConnection()
  │
  ├─ POST /api/sessions  {name, webhook config}
  │    └─ jika 422 "exists" → lanjutkan (idempotent)
  │
  ├─ POST /api/sessions/{name}/start
  │
  ├─ polling GET /api/sessions/{name}  (maks 5x, interval 500ms)
  │    └─ tunggu status = SCAN_QR_CODE
  │
  └─ GET /api/{name}/auth/qr?format=base64
       └─ kembalikan QR code ke DeviceService
```

### Alur Webhook WAHA

```
WAHA → POST /webhook/waha
  │
  ├─ validasi X-Webhook-Token
  ├─ validasi field event + session
  ├─ normalisasi payload:
  │    event=message          → {event, device_id, from, message}
  │    event=session.status   → {event (mapped), device_id, ...}
  │    event=unknown          → {event asli, device_id, ...}
  │
  └─ ProcessWebhookJob::dispatch(normalizedPayload)
```

---

## Komponen dan Interface

### 1. `GatewayClientInterface` (baru)

**Path:** `app/Services/Contracts/GatewayClientInterface.php`

Interface agnostik yang menggantikan `BaileysGatewayClientInterface`. Signature method identik dengan interface lama, hanya nama parameter diubah dari `$deviceId` menjadi `$sessionName` untuk kejelasan semantik.

```php
interface GatewayClientInterface
{
    public function sendMessage(string $sessionName, string $to, string $message): GatewayResponse;
    public function getQrCode(string $sessionName): string;
    public function getConnectionStatus(string $sessionName): string; // 'connected'|'disconnected'|'error'
    public function disconnectDevice(string $sessionName): void;
    public function restartInstance(string $sessionName): void;
}
```

### 2. `GatewayResponse` (baru)

**Path:** `app/Services/ValueObjects/GatewayResponse.php`

Value object readonly yang menggantikan `BaileysResponse`. Struktur properti identik.

```php
readonly class GatewayResponse
{
    public function __construct(
        public bool $success,
        public string $status,
        public ?string $messageId = null,
        public ?string $errorMessage = null,
    ) {}
}
```

### 3. `WahaGatewayClient` (baru)

**Path:** `app/Services/WahaGatewayClient.php`

Implementasi `GatewayClientInterface` yang berkomunikasi dengan WAHA API. Bertanggung jawab atas:
- Autentikasi via header `X-Api-Key`
- Manajemen session (create, start, polling, stop, delete)
- Konversi nomor telepon ke format `chatId` (`628xxx@c.us`)
- Retry dengan exponential backoff untuk `sendMessage`

**Keputusan desain — pemisahan tanggung jawab session:**
Logika pembuatan session WAHA (POST `/api/sessions`, start, polling) diletakkan di dalam `WahaGatewayClient::getQrCode()` karena `DeviceService` memanggil `getQrCode()` sebagai satu-satunya entry point untuk memulai koneksi device baru. Ini menjaga `DeviceService` tetap agnostik terhadap detail protokol WAHA.

**Keputusan desain — idempotency session:**
HTTP 422 dari WAHA yang body-nya mengandung kata "exists" atau "already" dianggap sukses (session sudah ada). Error 422 lainnya tetap melempar `GatewayException`.

### 4. `WebhookController` (diperbarui)

**Path:** `app/Http/Controllers/WebhookController.php`

Ditambahkan method `waha()` untuk endpoint `/webhook/waha`. Method `baileys()` yang sudah ada dipertahankan tanpa perubahan.

Normalisasi payload WAHA ke format internal:

| WAHA Event | WAHA Status | Internal Event | Keterangan |
|---|---|---|---|
| `message` | — | `message` | `from` = `payload.from` tanpa `@c.us` |
| `session.status` | `WORKING` | `session.restore_complete` | — |
| `session.status` | `FAILED` | `device.manual_intervention` | + field `message` |
| lainnya | — | event asli | untuk logging |

Autentikasi webhook menggunakan header `X-Webhook-Token` (bukan HMAC seperti Baileys) karena WAHA tidak mendukung HMAC signing — token rahasia dibandingkan langsung menggunakan `hash_equals()`.

### 5. `ProcessWebhookJob` (diperbarui)

**Path:** `app/Jobs/ProcessWebhookJob.php`

Validasi payload diperbarui: field wajib untuk event `message` adalah `event`, `device_id`, `from`, `message`. Untuk event lain (`session.restore_complete`, `device.manual_intervention`), hanya `event` dan `device_id` yang wajib ada.

Handler yang sudah ada (`handleSessionRestoreComplete`, `handleDeviceManualIntervention`, `handleDefault`) tidak berubah karena payload sudah dinormalisasi oleh `WebhookController`.

### 6. `DeviceService` (diperbarui)

**Path:** `app/Services/DeviceService.php`

Hanya mengganti tipe dependensi dari `BaileysGatewayClientInterface` ke `GatewayClientInterface`. Semua logika bisnis dipertahankan.

### 7. `SendMessageJob` (diperbarui)

**Path:** `app/Jobs/SendMessageJob.php`

Hanya mengganti tipe dependensi dari `BaileysGatewayClientInterface` ke `GatewayClientInterface`. Return type `sendMessage` berubah dari `BaileysResponse` ke `GatewayResponse`, tetapi properti yang diakses (`success`, `status`, `errorMessage`) identik.

### 8. `AppServiceProvider` (diperbarui)

**Path:** `app/Providers/AppServiceProvider.php`

- Hapus: `$this->app->bind(BaileysGatewayClientInterface::class, BaileysGatewayClient::class)`
- Tambah: `$this->app->bind(GatewayClientInterface::class, WahaGatewayClient::class)`

### 9. `config/wa-automation.php` (diperbarui)

Ditambahkan section `waha`. Section `baileys` dipertahankan.

### 10. `routes/web.php` (diperbarui)

Ditambahkan route:
```php
Route::post('/webhook/waha', [WebhookController::class, 'waha'])->name('webhook.waha');
```

---

## Model Data

### Format Payload Internal (tidak berubah)

`ProcessWebhookJob` menerima array dengan struktur berikut (sudah ada, tidak berubah):

```php
// Event message
[
    'event'     => 'message',
    'device_id' => 'uuid-gateway-device-id',  // = gateway_device_id di tabel devices
    'from'      => '628123456789',             // tanpa @c.us
    'message'   => 'teks pesan',
]

// Event session.restore_complete
[
    'event'     => 'session.restore_complete',
    'device_id' => 'uuid-gateway-device-id',
    'message'   => 'Session restoration completed',
    'stats'     => [],
    'timestamp' => '2024-01-01T00:00:00Z',
]

// Event device.manual_intervention
[
    'event'     => 'device.manual_intervention',
    'device_id' => 'uuid-gateway-device-id',
    'message'   => 'Device requires manual intervention',
    'status'    => 'FAILED',
]
```

### Format Payload WAHA (masuk dari webhook)

```php
// Event message
[
    'event'   => 'message',
    'session' => 'uuid-gateway-device-id',
    'payload' => [
        'from' => '628123456789@c.us',
        'body' => 'teks pesan',
    ],
    'me'      => [...],
]

// Event session.status
[
    'event'   => 'session.status',
    'session' => 'uuid-gateway-device-id',
    'payload' => [
        'status' => 'WORKING',  // atau 'FAILED', 'STOPPED', dll.
    ],
]
```

### Konfigurasi WAHA (`config/wa-automation.php`)

```php
'waha' => [
    'base_url'      => env('WAHA_BASE_URL', 'https://wa.konektivitas.com'),
    'api_key'       => env('WAHA_API_KEY'),           // wajib
    'webhook_url'   => env('WAHA_WEBHOOK_URL'),        // wajib
    'webhook_token' => env('WAHA_WEBHOOK_TOKEN'),      // wajib
    'timeout'       => 30,
    'max_retries'   => 3,
    'retry_backoff' => [1000, 2000, 4000],             // milliseconds
],
```

### Variabel Environment Baru (`.env.example`)

```
WAHA_BASE_URL=https://wa.konektivitas.com
WAHA_API_KEY=your-waha-api-key
WAHA_WEBHOOK_URL=https://your-app.com/webhook/waha
WAHA_WEBHOOK_TOKEN=your-webhook-token-secret
```

---

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: GatewayResponse menyimpan nilai dengan benar

*For any* kombinasi nilai `success` (bool), `status` (string), `messageId` (?string), dan `errorMessage` (?string), membuat `GatewayResponse` dan membaca kembali propertinya harus menghasilkan nilai yang identik dengan yang diberikan.

**Validates: Requirements 1.6**

---

### Property 2: Header X-Api-Key selalu disertakan

*For any* method pada `WahaGatewayClient` (`sendMessage`, `getQrCode`, `getConnectionStatus`, `disconnectDevice`, `restartInstance`) dan *for any* nilai parameter yang valid, setiap HTTP request yang dikirim harus menyertakan header `X-Api-Key` dengan nilai yang sesuai konfigurasi.

**Validates: Requirements 2.3**

---

### Property 3: sendMessage memformat request dengan benar

*For any* kombinasi `sessionName`, `to` (nomor telepon), dan `message`, `WahaGatewayClient::sendMessage` harus mengirim POST request ke `/api/sendText` dengan body yang mengandung `session = sessionName`, `chatId` dalam format `{to}@c.us` (tanpa duplikasi), dan `text = message`.

**Validates: Requirements 2.4, 9.1, 9.2, 9.3**

---

### Property 4: sendMessage sukses mengembalikan GatewayResponse yang benar

*For any* input valid ke `sendMessage`, ketika WAHA API mengembalikan HTTP 2xx, `WahaGatewayClient` harus mengembalikan `GatewayResponse` dengan `success = true` dan `status = 'sent'`.

**Validates: Requirements 2.5**

---

### Property 5: sendMessage gagal melempar GatewayException

*For any* HTTP error code (4xx atau 5xx) yang dikembalikan WAHA API untuk request `sendMessage`, `WahaGatewayClient` harus melempar `GatewayException`.

**Validates: Requirements 2.6**

---

### Property 6: getQrCode memformat URL request dengan benar

*For any* `sessionName`, `WahaGatewayClient::getQrCode` harus mengirim GET request ke URL yang mengandung `/{sessionName}/auth/qr` dengan query parameter `format=base64`.

**Validates: Requirements 2.7**

---

### Property 7: getQrCode mengembalikan nilai dari field value

*For any* string base64 yang dikembalikan WAHA API dalam field `value`, `WahaGatewayClient::getQrCode` harus mengembalikan string tersebut apa adanya.

**Validates: Requirements 2.8**

---

### Property 8: getConnectionStatus memetakan status non-WORKING ke 'disconnected'

*For any* string status yang bukan `'WORKING'` (misalnya `'STOPPED'`, `'FAILED'`, `'SCAN_QR_CODE'`, `'STARTING'`, atau string acak lainnya), `WahaGatewayClient::getConnectionStatus` harus mengembalikan string `'disconnected'`.

**Validates: Requirements 2.12**

---

### Property 9: disconnectDevice dan restartInstance memformat URL dengan benar

*For any* `sessionName`, `WahaGatewayClient::disconnectDevice` harus mengirim POST ke `/api/sessions/{sessionName}/stop`, dan `WahaGatewayClient::restartInstance` harus mengirim POST ke `/api/sessions/{sessionName}/restart`.

**Validates: Requirements 2.14, 2.16**

---

### Property 10: Payload webhook WAHA dinormalisasi dengan benar

*For any* payload webhook WAHA dengan event `message`, normalisasi harus menghasilkan `device_id = payload.session`, `from = payload.payload.from` tanpa suffix `@c.us`, dan `message = payload.payload.body`.

**Validates: Requirements 5.1, 5.2**

---

### Property 11: Mapping status session.status ke event internal

*For any* payload webhook WAHA dengan event `session.status`, normalisasi harus memetakan `status = 'WORKING'` ke event `session.restore_complete` dan `status = 'FAILED'` ke event `device.manual_intervention`. Status lain dipetakan ke event `session.status` asli.

**Validates: Requirements 5.3**

---

### Property 12: Validasi token webhook menolak semua token yang tidak cocok

*For any* nilai header `X-Webhook-Token` yang tidak sama dengan `wa-automation.waha.webhook_token`, endpoint `/webhook/waha` harus mengembalikan HTTP 401. Hanya token yang tepat yang menghasilkan HTTP 200.

**Validates: Requirements 4.2, 4.3**

---

### Property 13: Validasi payload webhook menolak payload tanpa field wajib

*For any* payload yang tidak memiliki field `event` atau `session`, endpoint `/webhook/waha` harus mengembalikan HTTP 400.

**Validates: Requirements 4.4**

---

### Property 14: Konversi nomor telepon ke chatId

*For any* nomor telepon (dengan atau tanpa prefix `+`, dengan atau tanpa suffix `@c.us`), `WahaGatewayClient` harus menghasilkan `chatId` dalam format `{nomor_tanpa_plus}@c.us` tanpa duplikasi suffix.

**Validates: Requirements 9.1, 9.2, 9.3**

---

## Penanganan Error

### Hierarki Error

Semua error gateway dilempar sebagai `GatewayException` (sudah ada) dengan pesan deskriptif dan `gatewayError` opsional berisi body respons HTTP.

```
GatewayException
├── sendMessage gagal (HTTP non-2xx)
├── getQrCode gagal (HTTP non-2xx)
├── getQrCode: session tidak mencapai SCAN_QR_CODE setelah polling
├── disconnectDevice gagal (HTTP non-2xx)
└── restartInstance gagal (HTTP non-2xx)
```

### Strategi Penanganan per Skenario

| Skenario | Perilaku |
|---|---|
| WAHA API tidak dapat dijangkau (network error) | `sendMessage` → retry 3x lalu `GatewayException`; `getConnectionStatus` → return `'error'` |
| HTTP 422 dengan body "exists"/"already" | Dianggap sukses (idempotent session creation) |
| HTTP 422 dengan body lain | `GatewayException` |
| Session tidak mencapai `SCAN_QR_CODE` setelah 5 polling | `GatewayException('Session not ready for QR code')` |
| Token webhook tidak valid | HTTP 401, log warning dengan IP |
| Payload webhook malformed | HTTP 400 |
| `device_id` tidak ditemukan di database | Log warning, hentikan pemrosesan tanpa exception |

### Logging

- `Log::warning` untuk: token webhook tidak valid, payload malformed, device tidak ditemukan.
- `Log::error` untuk: exception tidak tertangani di webhook controller, error HTTP dari WAHA.
- `Log::info` untuk: pesan berhasil dikirim, session berhasil dibuat.

---

## Strategi Testing

### Pendekatan Dual Testing

Testing menggunakan dua pendekatan komplementer:
1. **Unit/Feature tests** — skenario spesifik, edge case, integrasi antar komponen.
2. **Property-based tests** — properti universal yang harus berlaku untuk semua input valid.

Library PBT yang digunakan: **[eris/eris](https://github.com/giorgiosironi/eris)** (PHP property-based testing library).

Setiap property test dikonfigurasi dengan minimum **100 iterasi**.

### Cakupan Test per Komponen

#### `WahaGatewayClient` (Unit Tests + Property Tests)

**Property tests** (menggunakan HTTP mock `Http::fake()`):
- Property 2: Header X-Api-Key selalu ada di setiap request
- Property 3: Format request sendMessage (chatId, session, text)
- Property 4: GatewayResponse sukses dari sendMessage
- Property 5: GatewayException dari sendMessage gagal
- Property 6: Format URL getQrCode
- Property 7: Return value getQrCode dari field value
- Property 8: Mapping status non-WORKING ke 'disconnected'
- Property 9: Format URL disconnectDevice dan restartInstance
- Property 14: Konversi nomor telepon ke chatId

**Example tests**:
- `getConnectionStatus` dengan status `WORKING` → return `'connected'`
- `getConnectionStatus` dengan network exception → return `'error'`
- Retry 3x untuk sendMessage dengan network error sementara
- Idempotency: HTTP 422 "exists" tidak melempar exception
- Polling SCAN_QR_CODE: sukses setelah N polling
- Polling SCAN_QR_CODE: gagal setelah 5 polling → GatewayException

#### `WebhookController` (Feature Tests + Property Tests)

**Property tests**:
- Property 12: Token tidak valid → HTTP 401
- Property 13: Payload tanpa field wajib → HTTP 400
- Property 10: Normalisasi payload event message
- Property 11: Mapping event session.status

**Example tests**:
- Webhook valid → HTTP 200 + job dispatched
- Event `session.status` FAILED → field message ada
- Event tidak dikenal → job dispatched dengan event asli
- Endpoint `/webhook/baileys` masih berfungsi

#### `ProcessWebhookJob` (Feature Tests)

**Example tests**:
- Event `session.restore_complete` → SystemLog dibuat
- Event `device.manual_intervention` → device status = error + alert dibuat
- Event `message` → AutoReplyService dipanggil
- `device_id` tidak ditemukan → tidak ada exception
- `from` atau `message` kosong → tidak ada exception

#### `GatewayResponse` (Unit Tests + Property Tests)

**Property tests**:
- Property 1: Round-trip nilai properti

#### Integrasi `DeviceService` dan `SendMessageJob`

**Example tests**:
- `DeviceService` menggunakan `GatewayClientInterface` (bukan `BaileysGatewayClientInterface`)
- `SendMessageJob` menggunakan `GatewayClientInterface`
- DI container me-resolve `GatewayClientInterface` ke `WahaGatewayClient`

### Tag Format untuk Property Tests

Setiap property test diberi komentar tag:

```php
// Feature: waha-migration, Property 3: sendMessage memformat request dengan benar
```

### Catatan Backward Compatibility

- `BaileysGatewayClient` dan `BaileysGatewayClientInterface` **tidak dihapus** dari codebase — hanya tidak di-bind di container. Ini memastikan test yang sudah ada yang mungkin mereferensikan class tersebut tetap dapat dikompilasi.
- Semua 758 test yang ada harus tetap passing karena perubahan hanya pada binding DI container dan penambahan komponen baru.
