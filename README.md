# GoBlast - WhatsApp Automation Platform

Platform SaaS untuk automasi WhatsApp — broadcast pesan, template dinamis, auto-reply, dan reminder otomatis.

---

## 🚀 Quick Start

### 1. Clone & Install

```bash
git clone <repository-url>
cd goblast

composer install
npm install
```

### 2. Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` sesuaikan database dan konfigurasi lainnya.

### 3. Database

```bash
php artisan migrate
php artisan db:seed
```

### 4. Build Assets

```bash
npm run build
```

### 5. Jalankan Baileys Gateway

```bash
cd gateway
npm install
cp .env.example .env
# Edit gateway/.env — pastikan WEBHOOK_SECRET sama dengan .env Laravel
npm run dev
```

Gateway berjalan di `http://localhost:3000`.

### 6. Jalankan Queue Worker

```bash
php artisan queue:work
```

### 7. Akses Aplikasi

Jika menggunakan Laravel Herd: `http://goblast.test`

---

## 📋 Requirements

- PHP 8.4+
- MySQL 8.0+
- Node.js 18+
- Composer
- Laravel Herd (atau server PHP lainnya)

---

## ⚙️ Konfigurasi

### Laravel `.env`

```env
APP_NAME="GoBlast"
APP_URL=http://goblast.test

DB_CONNECTION=mysql
DB_DATABASE=goblast
DB_USERNAME=root
DB_PASSWORD=

# Baileys Gateway
BAILEYS_GATEWAY_URL=http://localhost:3000
BAILEYS_WEBHOOK_SECRET=your-webhook-secret-key-change-in-production

# Queue
QUEUE_CONNECTION=database
```

### Gateway `gateway/.env`

```env
PORT=3000
WEBHOOK_URL=http://goblast.test/api/webhooks/baileys
WEBHOOK_SECRET=your-webhook-secret-key-change-in-production
SESSION_PATH=./sessions
```

> **Penting:** `WEBHOOK_SECRET` di gateway harus sama persis dengan `BAILEYS_WEBHOOK_SECRET` di Laravel.

---

## 🔧 Troubleshooting

### ❌ "Failed to connect to gateway"

**Penyebab:** Baileys Gateway belum berjalan.

**Solusi:**

```bash
# 1. Masuk ke folder gateway
cd gateway

# 2. Install dependencies (jika belum)
npm install

# 3. Copy konfigurasi
cp .env.example .env

# 4. Jalankan gateway
npm run dev
```

Verifikasi gateway berjalan:
```bash
curl http://localhost:3000/health
# Response: {"status":"ok","uptime":...}
```

Pastikan konfigurasi `.env` Laravel sudah benar:
```env
BAILEYS_GATEWAY_URL=http://localhost:3000
```

---

### ❌ QR Code tidak muncul

1. Pastikan gateway sudah running (`npm run dev` di folder `gateway/`)
2. Refresh halaman device di GoBlast
3. Cek log gateway di terminal untuk error
4. Pastikan port 3000 tidak diblokir firewall

---

### ❌ Pesan tidak terkirim

1. Pastikan queue worker berjalan:
   ```bash
   php artisan queue:work
   ```
2. Cek status device — harus "Connected"
3. Pastikan nomor format internasional: `628xxxxxxxxxx`
4. Cek Message Logs untuk detail error
5. Cek log Laravel: `tail -f storage/logs/laravel.log`

---

### ❌ Device terputus setelah beberapa waktu

WhatsApp kadang memutus koneksi. Gateway akan otomatis reconnect. Jika tidak:

1. Buka halaman device di GoBlast
2. Klik "Disconnect" lalu "Connect" lagi
3. Scan QR code baru

---

### ❌ Queue tidak berjalan

```bash
# Cek status jobs
php artisan queue:monitor

# Restart queue worker
php artisan queue:restart

# Jalankan ulang
php artisan queue:work --sleep=3 --tries=3
```

---

## 📱 Cara Menggunakan

### Tambah Device WhatsApp

1. Login ke GoBlast
2. Klik **Devices** di sidebar
3. Klik **+ Add Device**
4. Isi nama device, klik **Buat Device**
5. Scan QR code yang muncul dengan WhatsApp di HP
6. Tunggu status berubah menjadi **Connected** ✅

### Buat Template Pesan

1. Klik **Templates** → **Create Template**
2. Isi nama, pilih tipe, tulis konten
3. Gunakan variabel: `{nama}`, `{perusahaan}`, dll
4. Klik **Buat Template**

### Kirim Broadcast

1. Klik **Broadcasts** → **New Broadcast**
2. Pilih device dan template (opsional)
3. Upload CSV atau input nomor manual
4. Klik **Buat Broadcast**
5. Di halaman detail, klik **Start Sending**

---

## 🔑 Default Login (setelah seeder)

| Role | Email | Password |
|------|-------|----------|
| Superadmin | `info@konektivitas.com` | `Wahyu123456789@` |
| Admin | `admin@demo.test` | `password` |
| Member | `member@demo.test` | `password` |

---

## 📚 API

Gunakan API Token untuk integrasi eksternal:

1. Buat token di menu **API Tokens**
2. Gunakan di header: `Authorization: Bearer YOUR_TOKEN`

Endpoint tersedia:
- `POST /api/v1/messages/send` — Kirim pesan
- `GET /api/v1/devices` — List devices
- `POST /api/v1/broadcasts` — Buat broadcast

---

## 🏗️ Arsitektur

```
GoBlast (Laravel)  ←→  Baileys Gateway (Node.js)  ←→  WhatsApp
       ↑                        ↑
   Browser                  Webhook
```

- **Laravel** mengelola tenant, subscription, template, broadcast
- **Baileys Gateway** menangani koneksi WhatsApp via Baileys library
- **Queue Worker** memproses pengiriman pesan secara async
- **Webhook** dari gateway ke Laravel untuk update status real-time

---

## 🔒 Security

- Ganti `BAILEYS_WEBHOOK_SECRET` dengan nilai random yang kuat di production
- Set `APP_DEBUG=false` di production
- Gunakan HTTPS di production
- Jangan commit file `.env` ke repository

---

## 📖 Dokumentasi

| Dokumen | Deskripsi |
|---------|-----------|
| [User Guide](docs/USER_GUIDE.md) | Panduan lengkap untuk pengguna Tenant Dashboard |
| [API Documentation](docs/api/README.md) | Dokumentasi API untuk integrasi eksternal |
| [Deployment Guide](docs/DEPLOYMENT.md) | Panduan deployment ke production |
