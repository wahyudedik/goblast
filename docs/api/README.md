# WA Automation API Documentation

Dokumentasi lengkap untuk API publik WA Automation.

## Table of Contents

- [Overview](#overview)
- [Authentication](#authentication)
- [Rate Limiting](#rate-limiting)
- [Endpoints](#endpoints)
- [Error Handling](#error-handling)
- [Webhooks](#webhooks)
- [Code Examples](#code-examples)

## Overview

WA Automation API memungkinkan integrasi pengiriman pesan WhatsApp dengan sistem eksternal. API ini tersedia untuk tenant dengan paket **Business**.

### Base URL

```
https://your-domain.com/api/v1
```

### API Version

Versi API saat ini: **v1**

Semua endpoint menggunakan prefix `/api/v1/`.

## Authentication

### API Token

Semua request ke API harus menyertakan API Token dalam header `Authorization`:

```http
Authorization: Bearer {your_api_token}
```

### Mendapatkan API Token

1. Login ke Dashboard WA Automation
2. Navigasi ke menu **API Tokens**
3. Klik **Create Token**
4. Masukkan nama token (untuk identifikasi)
5. **Salin token yang ditampilkan** - token hanya ditampilkan sekali!

### Token Security

- Token disimpan dalam bentuk hash (SHA-256) di database
- Token tidak dapat ditampilkan kembali setelah pembuatan
- Revoke token yang tidak digunakan atau terkompromi
- Jangan share token atau commit ke repository

## Rate Limiting

API dibatasi untuk mencegah penyalahgunaan:

| Limit | Value |
|-------|-------|
| Requests per minute | 60 |
| Per | API Token |

### Rate Limit Headers

Setiap response menyertakan header rate limit:

```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 55
X-RateLimit-Reset: 1714380060
```

### Handling Rate Limits

Jika rate limit terlampaui, API mengembalikan HTTP 429:

```json
{
  "error": "Too Many Requests",
  "message": "Rate limit terlampaui. Silakan coba lagi dalam 30 detik."
}
```

Gunakan header `Retry-After` untuk mengetahui waktu tunggu.

## Endpoints

### POST /v1/send-message

Mengirim pesan tunggal ke satu nomor tujuan.

**Request:**

```bash
curl -X POST https://your-domain.com/api/v1/send-message \
  -H "Authorization: Bearer {your_api_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "device_id": 1,
    "to": "6281234567890",
    "message": "Halo, ini pesan test dari WA Automation."
  }'
```

**Response (202 Accepted):**

```json
{
  "success": true,
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "queued",
  "message": "Pesan telah dimasukkan ke antrian"
}
```

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `device_id` | integer | Yes | ID device yang akan digunakan |
| `to` | string | Yes | Nomor tujuan (format: 6281234567890) |
| `message` | string | Conditional | Isi pesan (wajib jika tanpa template_id) |
| `template_id` | integer | No | ID template yang akan digunakan |

---

### POST /v1/send-bulk

Mengirim pesan ke banyak nomor sekaligus (broadcast).

**Request:**

```bash
curl -X POST https://your-domain.com/api/v1/send-bulk \
  -H "Authorization: Bearer {your_api_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "device_id": 1,
    "recipients": ["6281234567890", "6281234567891", "6281234567892"],
    "message": "Halo, ini pesan broadcast dari WA Automation."
  }'
```

**Response (202 Accepted):**

```json
{
  "success": true,
  "broadcast_id": 123,
  "total_recipients": 3,
  "status": "queued",
  "message": "Broadcast telah dimasukkan ke antrian"
}
```

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `device_id` | integer | Yes | ID device yang akan digunakan |
| `recipients` | array | Yes | Daftar nomor tujuan (max 10.000) |
| `message` | string | Conditional | Isi pesan (wajib jika tanpa template_id) |
| `template_id` | integer | No | ID template yang akan digunakan |

---

### GET /v1/message-status/{job_id}

Memeriksa status pengiriman pesan.

**Request:**

```bash
curl -X GET https://your-domain.com/api/v1/message-status/550e8400-e29b-41d4-a716-446655440000 \
  -H "Authorization: Bearer {your_api_token}"
```

**Response (200 OK):**

```json
{
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "sent",
  "recipient": "6281234567890",
  "sent_at": "2026-04-29T10:30:00+07:00",
  "failed_at": null,
  "error_message": null,
  "attempts": 1,
  "message": "Pesan berhasil terkirim"
}
```

**Status Values:**

| Status | Description |
|--------|-------------|
| `pending` | Pesan sedang menunggu dalam antrian |
| `sent` | Pesan berhasil terkirim |
| `failed` | Pesan gagal terkirim setelah semua percobaan |
| `cancelled` | Pesan dibatalkan (kuota habis/subscription expired) |
| `retrying` | Pesan sedang dicoba ulang |

## Error Handling

### HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 202 | Accepted (pesan masuk antrian) |
| 400 | Bad Request (payload tidak valid) |
| 401 | Unauthorized (token tidak valid) |
| 403 | Forbidden (fitur tidak tersedia) |
| 404 | Not Found (resource tidak ditemukan) |
| 422 | Unprocessable Entity (validasi gagal) |
| 429 | Too Many Requests (rate limit) |
| 500 | Internal Server Error |

### Error Response Format

**Validation Error (422):**

```json
{
  "error": "Validation failed",
  "errors": {
    "to": ["Format nomor telepon tidak valid. Gunakan format internasional (contoh: 6281234567890)."],
    "device_id": ["Device tidak ditemukan."]
  }
}
```

**Quota Exceeded (422):**

```json
{
  "error": "Quota exceeded",
  "message": "Kuota pesan telah habis. Sisa kuota: 0"
}
```

**Unauthorized (401):**

```json
{
  "error": "Unauthorized",
  "message": "Token tidak valid atau tidak ditemukan"
}
```

**Forbidden (403):**

```json
{
  "error": "Forbidden",
  "message": "Fitur API hanya tersedia untuk paket Business."
}
```

### Common Error Scenarios

| Scenario | HTTP Code | Error |
|----------|-----------|-------|
| Token tidak ada/invalid | 401 | Unauthorized |
| Paket bukan Business | 403 | Forbidden |
| Subscription expired | 403 | Forbidden |
| Device tidak ditemukan | 422 | Validation failed |
| Device tidak connected | 422 | Validation failed |
| Format nomor salah | 422 | Validation failed |
| Kuota habis | 422 | Quota exceeded |
| Job tidak ditemukan | 404 | Not found |

## Webhooks

WA Automation menerima webhook dari Baileys Gateway untuk memproses pesan masuk.

Lihat dokumentasi lengkap di [webhooks.md](./webhooks.md).

## Code Examples

### PHP (Guzzle)

```php
<?php

use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => 'https://your-domain.com/api/v1/',
    'headers' => [
        'Authorization' => 'Bearer ' . $apiToken,
        'Content-Type' => 'application/json',
    ],
]);

// Send single message
$response = $client->post('send-message', [
    'json' => [
        'device_id' => 1,
        'to' => '6281234567890',
        'message' => 'Halo dari PHP!',
    ],
]);

$result = json_decode($response->getBody(), true);
echo "Job ID: " . $result['job_id'];

// Check status
$statusResponse = $client->get('message-status/' . $result['job_id']);
$status = json_decode($statusResponse->getBody(), true);
echo "Status: " . $status['status'];
```

### JavaScript (Fetch)

```javascript
const API_BASE = 'https://your-domain.com/api/v1';
const API_TOKEN = 'your_api_token';

// Send single message
async function sendMessage(deviceId, to, message) {
  const response = await fetch(`${API_BASE}/send-message`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${API_TOKEN}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      device_id: deviceId,
      to: to,
      message: message,
    }),
  });

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Failed to send message');
  }

  return response.json();
}

// Check status
async function checkStatus(jobId) {
  const response = await fetch(`${API_BASE}/message-status/${jobId}`, {
    headers: {
      'Authorization': `Bearer ${API_TOKEN}`,
    },
  });

  return response.json();
}

// Usage
const result = await sendMessage(1, '6281234567890', 'Halo dari JavaScript!');
console.log('Job ID:', result.job_id);

// Poll for status
const status = await checkStatus(result.job_id);
console.log('Status:', status.status);
```

### Python (Requests)

```python
import requests

API_BASE = 'https://your-domain.com/api/v1'
API_TOKEN = 'your_api_token'

headers = {
    'Authorization': f'Bearer {API_TOKEN}',
    'Content-Type': 'application/json',
}

# Send single message
def send_message(device_id, to, message):
    response = requests.post(
        f'{API_BASE}/send-message',
        headers=headers,
        json={
            'device_id': device_id,
            'to': to,
            'message': message,
        }
    )
    response.raise_for_status()
    return response.json()

# Send bulk messages
def send_bulk(device_id, recipients, message):
    response = requests.post(
        f'{API_BASE}/send-bulk',
        headers=headers,
        json={
            'device_id': device_id,
            'recipients': recipients,
            'message': message,
        }
    )
    response.raise_for_status()
    return response.json()

# Check status
def check_status(job_id):
    response = requests.get(
        f'{API_BASE}/message-status/{job_id}',
        headers=headers
    )
    response.raise_for_status()
    return response.json()

# Usage
result = send_message(1, '6281234567890', 'Halo dari Python!')
print(f"Job ID: {result['job_id']}")

status = check_status(result['job_id'])
print(f"Status: {status['status']}")
```

### cURL

```bash
# Set variables
API_BASE="https://your-domain.com/api/v1"
API_TOKEN="your_api_token"

# Send single message
curl -X POST "$API_BASE/send-message" \
  -H "Authorization: Bearer $API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "device_id": 1,
    "to": "6281234567890",
    "message": "Halo dari cURL!"
  }'

# Send bulk messages
curl -X POST "$API_BASE/send-bulk" \
  -H "Authorization: Bearer $API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "device_id": 1,
    "recipients": ["6281234567890", "6281234567891"],
    "message": "Halo dari cURL broadcast!"
  }'

# Check message status
curl -X GET "$API_BASE/message-status/550e8400-e29b-41d4-a716-446655440000" \
  -H "Authorization: Bearer $API_TOKEN"
```

## OpenAPI Specification

Dokumentasi API dalam format OpenAPI 3.1 tersedia di [openapi.yaml](./openapi.yaml).

Anda dapat menggunakan file ini dengan:
- [Swagger UI](https://swagger.io/tools/swagger-ui/)
- [Redoc](https://redocly.github.io/redoc/)
- [Postman](https://www.postman.com/)
- [Insomnia](https://insomnia.rest/)

## Support

Untuk bantuan atau pertanyaan, hubungi:
- WhatsApp: [wa.me/6281529211963](https://wa.me/6281529211963)
