# Error Codes Reference

Dokumentasi lengkap kode error dan pesan yang dikembalikan oleh WA Automation API.

## HTTP Status Codes

| Code | Name | Description |
|------|------|-------------|
| 200 | OK | Request berhasil |
| 202 | Accepted | Request diterima dan akan diproses secara asinkron |
| 400 | Bad Request | Request tidak valid atau payload malformed |
| 401 | Unauthorized | Autentikasi gagal |
| 403 | Forbidden | Akses ditolak |
| 404 | Not Found | Resource tidak ditemukan |
| 422 | Unprocessable Entity | Validasi gagal |
| 429 | Too Many Requests | Rate limit terlampaui |
| 500 | Internal Server Error | Error internal server |

## Error Categories

### Authentication Errors (401)

#### `Unauthorized`

**Response:**
```json
{
  "error": "Unauthorized",
  "message": "Token tidak valid atau tidak ditemukan"
}
```

**Penyebab:**
- Header `Authorization` tidak ada
- Format header salah (bukan `Bearer {token}`)
- Token tidak valid atau tidak ditemukan di database
- Token sudah di-revoke

**Solusi:**
- Pastikan header `Authorization: Bearer {your_token}` disertakan
- Periksa token masih aktif di Dashboard
- Generate token baru jika diperlukan

---

### Authorization Errors (403)

#### `Forbidden` - No Active Subscription

**Response:**
```json
{
  "error": "Forbidden",
  "message": "Tidak ada langganan aktif. Silakan berlangganan terlebih dahulu."
}
```

**Penyebab:**
- Tenant tidak memiliki subscription aktif
- Subscription sudah expired

**Solusi:**
- Perpanjang subscription melalui Dashboard
- Hubungi admin untuk aktivasi subscription

#### `Forbidden` - Feature Not Allowed

**Response:**
```json
{
  "error": "Forbidden",
  "message": "Fitur API hanya tersedia untuk paket Business."
}
```

**Penyebab:**
- Tenant menggunakan paket Starter atau Pro
- Fitur API tidak tersedia di paket saat ini

**Solusi:**
- Upgrade ke paket Business untuk mengakses fitur API

---

### Validation Errors (422)

#### `Validation failed` - Device Not Found

**Response:**
```json
{
  "error": "Validation failed",
  "errors": {
    "device_id": ["Device tidak ditemukan atau bukan milik tenant Anda."]
  }
}
```

**Penyebab:**
- `device_id` tidak ada di database
- Device bukan milik tenant yang mengakses API

**Solusi:**
- Periksa `device_id` yang benar di Dashboard
- Pastikan device terdaftar untuk tenant Anda

#### `Validation failed` - Device Not Connected

**Response:**
```json
{
  "error": "Validation failed",
  "errors": {
    "device_id": ["Device tidak dalam status terhubung. Status saat ini: disconnected"]
  }
}
```

**Penyebab:**
- Device tidak dalam status `connected`
- Device mungkin terputus atau belum di-scan QR code

**Solusi:**
- Periksa status device di Dashboard
- Reconnect device jika terputus
- Scan QR code untuk device baru

#### `Validation failed` - Invalid Phone Number

**Response:**
```json
{
  "error": "Validation failed",
  "errors": {
    "to": ["Format nomor telepon tidak valid. Gunakan format internasional (contoh: 6281234567890)."]
  }
}
```

**Penyebab:**
- Format nomor telepon tidak sesuai
- Nomor terlalu pendek atau terlalu panjang

**Solusi:**
- Gunakan format internasional tanpa tanda `+`
- Contoh benar: `6281234567890`
- Contoh salah: `081234567890`, `+6281234567890`

#### `Validation failed` - Template Not Found

**Response:**
```json
{
  "error": "Validation failed",
  "errors": {
    "template_id": ["Template tidak ditemukan atau bukan milik tenant Anda."]
  }
}
```

**Penyebab:**
- `template_id` tidak ada di database
- Template bukan milik tenant yang mengakses API

**Solusi:**
- Periksa `template_id` yang benar di Dashboard
- Buat template baru jika diperlukan

#### `Validation failed` - Message Required

**Response:**
```json
{
  "error": "Validation failed",
  "errors": {
    "message": ["Pesan wajib diisi jika template_id tidak disertakan."]
  }
}
```

**Penyebab:**
- Tidak ada `message` dan tidak ada `template_id`

**Solusi:**
- Sertakan `message` dalam request, atau
- Sertakan `template_id` untuk menggunakan template

#### `Validation failed` - Recipients Required

**Response:**
```json
{
  "error": "Validation failed",
  "errors": {
    "recipients": ["Daftar nomor tujuan wajib diisi."]
  }
}
```

**Penyebab:**
- Array `recipients` kosong atau tidak ada

**Solusi:**
- Sertakan minimal satu nomor dalam array `recipients`

#### `Validation failed` - Too Many Recipients

**Response:**
```json
{
  "error": "Validation failed",
  "errors": {
    "recipients": ["Maksimal 10.000 nomor tujuan per broadcast."]
  }
}
```

**Penyebab:**
- Jumlah recipients melebihi 10.000

**Solusi:**
- Bagi broadcast menjadi beberapa batch
- Maksimal 10.000 nomor per request

#### `Quota exceeded` - Single Message

**Response:**
```json
{
  "error": "Quota exceeded",
  "message": "Kuota pesan telah habis. Sisa kuota: 0"
}
```

**Penyebab:**
- Kuota pesan tenant sudah habis

**Solusi:**
- Tunggu reset kuota di periode berikutnya
- Upgrade paket untuk kuota lebih besar
- Hubungi admin untuk top-up kuota

#### `Quota exceeded` - Bulk Message

**Response:**
```json
{
  "error": "Quota exceeded",
  "message": "Kuota pesan tidak mencukupi. Sisa kuota: 50, dibutuhkan: 100"
}
```

**Penyebab:**
- Kuota tidak mencukupi untuk semua recipients

**Solusi:**
- Kurangi jumlah recipients sesuai sisa kuota
- Tunggu reset kuota atau upgrade paket

---

### Not Found Errors (404)

#### `Not found` - Job Not Found

**Response:**
```json
{
  "error": "Not found",
  "message": "Job tidak ditemukan"
}
```

**Penyebab:**
- `job_id` tidak ada di database
- Job bukan milik tenant yang mengakses API

**Solusi:**
- Periksa `job_id` yang dikembalikan saat mengirim pesan
- Pastikan menggunakan `job_id` yang benar

---

### Rate Limit Errors (429)

#### `Too Many Requests`

**Response:**
```json
{
  "error": "Too Many Requests",
  "message": "Rate limit terlampaui. Silakan coba lagi dalam 30 detik."
}
```

**Headers:**
```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1714380060
Retry-After: 30
```

**Penyebab:**
- Melebihi 60 request per menit

**Solusi:**
- Tunggu sesuai nilai `Retry-After`
- Implementasi rate limiting di client
- Gunakan exponential backoff untuk retry

---

### Webhook Errors

#### `Invalid signature` (401)

**Response:**
```json
{
  "error": "Invalid signature"
}
```

**Penyebab:**
- Header `X-Baileys-Signature` tidak ada
- Signature tidak cocok dengan payload
- Secret key tidak sesuai

**Solusi:**
- Pastikan signature dihitung dengan benar
- Periksa secret key di konfigurasi

#### `Malformed payload` (400)

**Response:**
```json
{
  "error": "Malformed payload"
}
```

**Penyebab:**
- Field wajib tidak ada (`event`, `device_id`, `from`, `message`)
- Field wajib kosong

**Solusi:**
- Pastikan semua field wajib disertakan
- Periksa format JSON payload

---

## Error Handling Best Practices

### 1. Implement Retry Logic

```javascript
async function sendWithRetry(data, maxRetries = 3) {
  for (let i = 0; i < maxRetries; i++) {
    try {
      const response = await sendMessage(data);
      return response;
    } catch (error) {
      if (error.status === 429) {
        // Rate limited - wait and retry
        const retryAfter = error.headers['retry-after'] || 30;
        await sleep(retryAfter * 1000);
        continue;
      }
      if (error.status >= 500) {
        // Server error - exponential backoff
        await sleep(Math.pow(2, i) * 1000);
        continue;
      }
      // Client error - don't retry
      throw error;
    }
  }
  throw new Error('Max retries exceeded');
}
```

### 2. Handle Validation Errors

```javascript
try {
  const result = await sendMessage(data);
} catch (error) {
  if (error.status === 422) {
    const errors = error.body.errors;
    for (const [field, messages] of Object.entries(errors)) {
      console.error(`${field}: ${messages.join(', ')}`);
    }
  }
}
```

### 3. Check Quota Before Bulk Send

```javascript
// Get remaining quota from dashboard or track locally
const remainingQuota = await getQuota();
const recipients = [...];

if (recipients.length > remainingQuota) {
  console.warn(`Only ${remainingQuota} messages can be sent`);
  recipients = recipients.slice(0, remainingQuota);
}

await sendBulk({ recipients, ... });
```

### 4. Monitor Rate Limits

```javascript
function handleResponse(response) {
  const remaining = response.headers['x-ratelimit-remaining'];
  const reset = response.headers['x-ratelimit-reset'];
  
  if (remaining < 10) {
    console.warn(`Rate limit warning: ${remaining} requests remaining`);
    console.warn(`Reset at: ${new Date(reset * 1000)}`);
  }
  
  return response.json();
}
```
