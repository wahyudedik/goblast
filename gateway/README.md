# GoBlast Baileys Gateway

Node.js WhatsApp gateway menggunakan library Baileys untuk GoBlast.

## Requirements

- Node.js 18+
- npm

## Setup

```bash
# 1. Masuk ke folder gateway
cd gateway

# 2. Install dependencies
npm install

# 3. Copy dan edit konfigurasi
cp .env.example .env
```

Edit file `.env`:
```env
PORT=3000
WEBHOOK_URL=http://goblast.test/api/webhooks/baileys
WEBHOOK_SECRET=your-webhook-secret-key-change-in-production
```

> **Penting:** `WEBHOOK_SECRET` harus sama dengan `BAILEYS_WEBHOOK_SECRET` di `.env` Laravel.

## Menjalankan Gateway

```bash
# Development (dengan auto-reload)
npm run dev

# Production
npm start
```

## API Endpoints

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/health` | Health check |
| GET | `/api/qr-code/:device_id` | Dapatkan QR code untuk koneksi |
| GET | `/api/device-status/:device_id` | Status koneksi device |
| POST | `/api/send-message` | Kirim pesan WhatsApp |
| POST | `/api/disconnect/:device_id` | Putuskan koneksi device |
| POST | `/api/restart-instance/:instance_id` | Restart koneksi device |
| GET | `/api/devices` | List semua device |

## Cara Kerja

1. Laravel membuat device baru → memanggil `/api/qr-code/:device_id`
2. Gateway membuat sesi Baileys baru dan mengembalikan QR code (base64)
3. User scan QR code dengan WhatsApp
4. Gateway mengirim webhook ke Laravel saat status berubah
5. Pesan masuk diteruskan ke Laravel via webhook

## Sessions

Sesi WhatsApp disimpan di folder `sessions/`. Setiap device punya subfolder sendiri. Jangan hapus folder ini kecuali ingin reset koneksi.

## Troubleshooting

**Gateway tidak bisa start:**
```bash
# Pastikan port 3000 tidak digunakan
netstat -ano | findstr :3000

# Ganti port di .env jika perlu
PORT=3001
```

**QR code tidak muncul:**
- Pastikan gateway sudah running
- Cek log gateway untuk error
- Coba refresh halaman device di GoBlast

**Pesan tidak terkirim:**
- Pastikan device status "connected"
- Cek log gateway
- Pastikan nomor format internasional (62xxx)
