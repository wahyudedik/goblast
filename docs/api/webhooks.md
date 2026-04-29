# Webhook Documentation

## Overview

WA Automation menerima webhook dari Baileys Gateway untuk memproses pesan masuk dan memicu fitur auto-reply.

## Webhook Endpoint

```
POST /webhook/baileys
```

## Authentication

Webhook menggunakan **HMAC SHA-256 signature** untuk validasi. Signature dikirim melalui header `X-Baileys-Signature`.

### Signature Validation

```php
$signature = hash_hmac('sha256', $rawPayload, $webhookSecret);
```

Konfigurasi secret key melalui environment variable:

```env
BAILEYS_WEBHOOK_SECRET=your-webhook-secret-key
```

## Request Format

### Headers

| Header | Required | Description |
|--------|----------|-------------|
| `Content-Type` | Yes | `application/json` |
| `X-Baileys-Signature` | Yes | HMAC SHA-256 signature dari payload |

### Payload Structure

```json
{
  "event": "message.received",
  "device_id": "gateway-device-uuid",
  "from": "6281234567890",
  "message": "harga",
  "timestamp": 1714380000000
}
```

### Payload Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `event` | string | Yes | Tipe event. Saat ini hanya `message.received` yang didukung |
| `device_id` | string | Yes | ID device di Baileys Gateway |
| `from` | string | Yes | Nomor pengirim dalam format internasional |
| `message` | string | Yes | Isi pesan yang diterima |
| `timestamp` | integer | No | Unix timestamp dalam milliseconds |

## Response Codes

### Success Response

```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "success": true,
  "message": "Webhook processed"
}
```

### Error Responses

#### 401 Unauthorized - Invalid Signature

```http
HTTP/1.1 401 Unauthorized
Content-Type: application/json

{
  "error": "Invalid signature"
}
```

Penyebab:
- Header `X-Baileys-Signature` tidak ada
- Signature tidak cocok dengan payload
- Secret key tidak sesuai

#### 400 Bad Request - Malformed Payload

```http
HTTP/1.1 400 Bad Request
Content-Type: application/json

{
  "error": "Malformed payload"
}
```

Penyebab:
- Field wajib tidak ada (`event`, `device_id`, `from`, `message`)
- Field wajib kosong

#### 500 Internal Server Error

```http
HTTP/1.1 500 Internal Server Error
Content-Type: application/json

{
  "error": "Internal server error"
}
```

Penyebab:
- Error internal saat memproses webhook

## Processing Flow

1. **Signature Validation**: Sistem memvalidasi signature menggunakan HMAC SHA-256
2. **Payload Validation**: Sistem memvalidasi struktur payload
3. **Job Dispatch**: Webhook di-dispatch ke `ProcessWebhookJob` untuk diproses secara asinkron
4. **Auto-Reply Matching**: Sistem mencocokkan pesan dengan keyword rules yang aktif
5. **Reply Dispatch**: Jika ada keyword yang cocok, sistem mengirim balasan otomatis

## Auto-Reply Behavior

### Keyword Matching

- Pencocokan keyword bersifat **case-insensitive**
- Jika ada multiple keyword yang cocok, sistem menggunakan keyword dengan **priority tertinggi**
- Keyword harus **exact match** (bukan partial match)

### Cooldown

Untuk mencegah loop balasan, sistem menerapkan cooldown:
- **1 balasan per nomor per keyword per 60 menit**
- Cooldown dihitung berdasarkan kombinasi: `device_id` + `keyword_rule_id` + `from`

### Example Flow

```
1. Pesan masuk: "harga" dari 6281234567890
2. Sistem mencari keyword "harga" di keyword_rules untuk device tersebut
3. Jika ditemukan dan tidak dalam cooldown:
   - Sistem membuat MessageLog
   - Sistem dispatch SendMessageJob dengan balasan
   - Sistem mencatat cooldown selama 60 menit
4. Jika dalam cooldown:
   - Sistem mencatat pesan masuk tanpa mengirim balasan
```

## Baileys Gateway Configuration

Untuk mengkonfigurasi Baileys Gateway agar mengirim webhook ke WA Automation:

```javascript
// Baileys Gateway configuration
const webhookConfig = {
  url: 'https://your-domain.com/webhook/baileys',
  secret: 'your-webhook-secret-key',
  events: ['message.received']
};
```

## Security Recommendations

1. **Gunakan HTTPS**: Selalu gunakan HTTPS untuk endpoint webhook
2. **Secret Key yang Kuat**: Gunakan secret key minimal 32 karakter dengan kombinasi huruf, angka, dan simbol
3. **IP Whitelisting**: Jika memungkinkan, batasi akses webhook hanya dari IP Baileys Gateway
4. **Monitoring**: Monitor log untuk mendeteksi upaya akses tidak sah

## Testing Webhook

### Using cURL

```bash
# Generate signature
SECRET="your-webhook-secret-key"
PAYLOAD='{"event":"message.received","device_id":"test-device","from":"6281234567890","message":"harga","timestamp":1714380000000}'
SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" | cut -d' ' -f2)

# Send webhook
curl -X POST https://your-domain.com/webhook/baileys \
  -H "Content-Type: application/json" \
  -H "X-Baileys-Signature: $SIGNATURE" \
  -d "$PAYLOAD"
```

### Using PHP

```php
$secret = 'your-webhook-secret-key';
$payload = json_encode([
    'event' => 'message.received',
    'device_id' => 'test-device',
    'from' => '6281234567890',
    'message' => 'harga',
    'timestamp' => time() * 1000,
]);

$signature = hash_hmac('sha256', $payload, $secret);

$ch = curl_init('https://your-domain.com/webhook/baileys');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-Baileys-Signature: ' . $signature,
    ],
    CURLOPT_RETURNTRANSFER => true,
]);

$response = curl_exec($ch);
curl_close($ch);
```
