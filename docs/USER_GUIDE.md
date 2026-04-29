# Panduan Pengguna Tenant Dashboard

Panduan lengkap untuk menggunakan WA Automation Dashboard sebagai Tenant.

## Daftar Isi

- [Memulai](#memulai)
  - [Registrasi Akun](#registrasi-akun)
  - [Login](#login)
  - [Navigasi Dashboard](#navigasi-dashboard)
- [Manajemen Device](#manajemen-device)
  - [Menambah Device Baru](#menambah-device-baru)
  - [Menghubungkan Device (Scan QR)](#menghubungkan-device-scan-qr)
  - [Memutus Koneksi Device](#memutus-koneksi-device)
  - [Menghapus Device](#menghapus-device)
- [Manajemen Template](#manajemen-template)
  - [Membuat Template Baru](#membuat-template-baru)
  - [Menggunakan Variabel Dinamis](#menggunakan-variabel-dinamis)
  - [Mengedit Template](#mengedit-template)
  - [Menghapus Template](#menghapus-template)
- [Broadcast Pesan](#broadcast-pesan)
  - [Membuat Broadcast Baru](#membuat-broadcast-baru)
  - [Upload Daftar Nomor (CSV)](#upload-daftar-nomor-csv)
  - [Memulai Pengiriman](#memulai-pengiriman)
  - [Memantau Progress](#memantau-progress)
  - [Membatalkan Broadcast](#membatalkan-broadcast)
- [Auto-Reply](#auto-reply)
  - [Membuat Keyword Rule](#membuat-keyword-rule)
  - [Mengatur Prioritas](#mengatur-prioritas)
  - [Menonaktifkan Auto-Reply](#menonaktifkan-auto-reply)
- [API Token](#api-token)
  - [Membuat API Token](#membuat-api-token)
  - [Menggunakan API Token](#menggunakan-api-token)
  - [Mencabut API Token](#mencabut-api-token)
- [Message Logs](#message-logs)
  - [Melihat Riwayat Pesan](#melihat-riwayat-pesan)
  - [Filter dan Pencarian](#filter-dan-pencarian)
  - [Export ke CSV](#export-ke-csv)
- [Subscription](#subscription)
  - [Melihat Status Langganan](#melihat-status-langganan)
  - [Memahami Kuota](#memahami-kuota)
  - [Upgrade Paket](#upgrade-paket)
- [Troubleshooting](#troubleshooting)

---

## Memulai

### Registrasi Akun

1. Buka halaman utama WA Automation
2. Klik tombol **Daftar** atau **Register**
3. Isi formulir pendaftaran:
   - **Nama**: Nama lengkap Anda
   - **Email**: Email aktif untuk login dan notifikasi
   - **Nomor Telepon**: Nomor WhatsApp Anda (opsional)
   - **Password**: Minimal 8 karakter
   - **Konfirmasi Password**: Ulangi password
4. Centang persetujuan syarat dan ketentuan
5. Klik **Daftar**

Setelah registrasi berhasil, Anda akan mendapatkan **masa trial gratis** untuk mencoba semua fitur.

### Login

1. Buka halaman login
2. Masukkan **Email** dan **Password**
3. (Opsional) Centang **Remember me** untuk tetap login
4. Klik **Login**

Jika lupa password:
1. Klik **Lupa Password?**
2. Masukkan email terdaftar
3. Cek inbox email untuk link reset password
4. Klik link dan buat password baru

### Navigasi Dashboard

Setelah login, Anda akan melihat Dashboard dengan menu sidebar:

| Menu | Fungsi |
|------|--------|
| **Dashboard** | Ringkasan statistik dan aktivitas terkini |
| **Devices** | Kelola nomor WhatsApp yang terhubung |
| **Templates** | Kelola template pesan |
| **Broadcasts** | Kirim pesan massal |
| **Message Logs** | Riwayat semua pesan terkirim |
| **Reminders** | Atur pengingat otomatis (Pro/Business) |
| **Keyword Rules** | Atur auto-reply berbasis kata kunci |
| **API Tokens** | Kelola token untuk integrasi API (Business) |
| **Subscription** | Lihat status langganan dan kuota |

---

## Manajemen Device

Device adalah nomor WhatsApp yang terhubung ke sistem untuk mengirim pesan.

### Menambah Device Baru

1. Klik menu **Devices** di sidebar
2. Klik tombol **+ Add Device**
3. Isi **Nama Device** (contoh: "CS Utama", "Marketing")
4. Klik **Buat Device**

> **Catatan:** Jumlah device yang dapat ditambahkan tergantung paket langganan:
> - Starter: 1 device
> - Pro: 1 device
> - Business: Unlimited device

### Menghubungkan Device (Scan QR)

Setelah device dibuat, Anda perlu menghubungkannya dengan WhatsApp:

1. Di halaman Devices, klik tombol **Connect** pada device yang ingin dihubungkan
2. Tunggu QR code muncul (maksimal 10 detik)
3. Buka **WhatsApp** di HP Anda
4. Ketuk **Menu (⋮)** → **Linked Devices** → **Link a Device**
5. Scan QR code yang tampil di layar
6. Tunggu hingga status berubah menjadi **Connected** ✅

**Tips:**
- QR code akan refresh otomatis setiap 30 detik
- Jika QR tidak muncul, pastikan Baileys Gateway sudah berjalan
- Scan dalam waktu 5 menit sebelum QR expired

### Memutus Koneksi Device

Untuk memutus koneksi device tanpa menghapusnya:

1. Di halaman Devices, temukan device yang ingin diputus
2. Klik tombol **Disconnect**
3. Konfirmasi dengan klik **Ya, Putuskan**

Device akan berstatus **Disconnected** dan tidak dapat mengirim pesan hingga dihubungkan kembali.

### Menghapus Device

1. Di halaman Devices, temukan device yang ingin dihapus
2. Klik tombol **Delete** (ikon tempat sampah)
3. Konfirmasi dengan klik **Ya, Hapus**

> **Peringatan:** Menghapus device akan:
> - Memutus koneksi WhatsApp
> - Menghapus semua data sesi
> - Data tidak dapat dikembalikan

---

## Manajemen Template

Template membantu Anda membuat pesan yang konsisten dan dapat digunakan berulang kali.

### Membuat Template Baru

1. Klik menu **Templates** di sidebar
2. Klik tombol **+ Create Template**
3. Isi formulir:
   - **Nama Template**: Nama untuk identifikasi (contoh: "Notifikasi Pembayaran")
   - **Tipe**: Pilih kategori template
     - **Notification**: Untuk notifikasi umum
     - **Promo**: Untuk pesan promosi
     - **Reminder**: Untuk pengingat
   - **Konten**: Isi pesan template (maksimal 4096 karakter)
4. Klik **Buat Template**

### Menggunakan Variabel Dinamis

Template mendukung variabel yang akan diganti dengan data aktual saat pengiriman:

**Format variabel:** `{nama_variabel}`

**Contoh template:**
```
Halo {nama},

Terima kasih telah berbelanja di {toko}.
Total pembelian Anda: Rp {total}

Pesanan akan dikirim pada {tanggal_kirim}.

Salam,
{toko}
```

**Variabel umum yang tersedia:**

| Variabel | Keterangan |
|----------|------------|
| `{nama}` | Nama penerima |
| `{nomor}` | Nomor telepon penerima |
| `{tanggal}` | Tanggal saat ini |
| `{waktu}` | Waktu saat ini |

**Variabel kustom:**
Anda dapat menggunakan variabel kustom sesuai kebutuhan. Pastikan data variabel tersedia saat pengiriman (melalui CSV atau API).

> **Catatan:** Jika variabel tidak memiliki nilai saat pengiriman, variabel akan diganti dengan string kosong.

### Mengedit Template

1. Di halaman Templates, temukan template yang ingin diedit
2. Klik tombol **Edit** (ikon pensil)
3. Ubah data yang diperlukan
4. Klik **Simpan Perubahan**

### Menghapus Template

1. Di halaman Templates, temukan template yang ingin dihapus
2. Klik tombol **Delete** (ikon tempat sampah)
3. Konfirmasi dengan klik **Ya, Hapus**

> **Catatan:** Template yang sedang digunakan oleh Reminder aktif tidak dapat dihapus. Nonaktifkan atau hapus Reminder terlebih dahulu.

---

## Broadcast Pesan

Broadcast memungkinkan Anda mengirim pesan ke banyak nomor sekaligus.

### Membuat Broadcast Baru

1. Klik menu **Broadcasts** di sidebar
2. Klik tombol **+ New Broadcast**
3. Isi formulir:
   - **Nama Broadcast**: Label untuk identifikasi
   - **Device**: Pilih device pengirim
   - **Template** (opsional): Pilih template atau tulis pesan langsung
   - **Pesan**: Isi pesan jika tidak menggunakan template
4. Pilih sumber daftar nomor:
   - **Upload CSV**: Upload file CSV berisi nomor tujuan
   - **Input Manual**: Ketik nomor satu per satu
5. Klik **Buat Broadcast**

### Upload Daftar Nomor (CSV)

**Format CSV yang diterima:**

```csv
nomor,nama,variabel_lain
6281234567890,Budi,nilai1
6281234567891,Ani,nilai2
6281234567892,Citra,nilai3
```

**Ketentuan:**
- Ukuran file maksimal: 5 MB
- Kolom pertama harus berisi nomor telepon
- Format nomor: awali dengan kode negara (62 untuk Indonesia)
- Kolom tambahan dapat digunakan sebagai variabel template

**Contoh format nomor yang benar:**
- ✅ `6281234567890`
- ✅ `628123456789`
- ❌ `081234567890` (tanpa kode negara)
- ❌ `+6281234567890` (dengan tanda +)

**Preview sebelum kirim:**
Setelah upload, sistem akan menampilkan:
- Jumlah nomor valid
- Jumlah nomor tidak valid (akan dilewati)
- Daftar nomor dengan error

### Memulai Pengiriman

Setelah broadcast dibuat:

1. Buka halaman detail broadcast
2. Review daftar penerima dan pesan
3. Perhatikan peringatan kuota jika ada
4. Klik tombol **Start Sending**
5. Konfirmasi untuk memulai pengiriman

**Peringatan Kuota:**
Jika jumlah penerima melebihi sisa kuota, sistem akan menampilkan:
- Jumlah pesan yang dapat terkirim
- Jumlah pesan yang akan dibatalkan

### Memantau Progress

Selama broadcast berjalan, Anda dapat memantau progress:

| Status | Keterangan |
|--------|------------|
| **Draft** | Broadcast dibuat, belum dimulai |
| **Queued** | Menunggu dalam antrian |
| **Running** | Sedang dalam proses pengiriman |
| **Completed** | Semua pesan telah diproses |
| **Cancelled** | Broadcast dibatalkan |
| **Failed** | Broadcast gagal |

**Progress bar menampilkan:**
- Total penerima
- Jumlah terkirim (hijau)
- Jumlah gagal (merah)
- Jumlah pending (abu-abu)

### Membatalkan Broadcast

Untuk membatalkan broadcast yang sedang berjalan:

1. Buka halaman detail broadcast
2. Klik tombol **Cancel Broadcast**
3. Konfirmasi pembatalan

> **Catatan:** Pesan yang sudah terkirim tidak dapat dibatalkan. Hanya pesan yang masih pending yang akan dibatalkan.

---

## Auto-Reply

Auto-reply membalas pesan masuk secara otomatis berdasarkan kata kunci.

### Membuat Keyword Rule

1. Klik menu **Keyword Rules** di sidebar
2. Klik tombol **+ Add Rule**
3. Isi formulir:
   - **Device**: Pilih device yang akan merespons
   - **Keyword**: Kata kunci pemicu (tidak case-sensitive)
   - **Reply**: Pesan balasan otomatis
   - **Priority**: Urutan prioritas (angka lebih tinggi = lebih diprioritaskan)
4. Klik **Buat Rule**

**Contoh:**

| Keyword | Reply | Priority |
|---------|-------|----------|
| `harga` | Untuk daftar harga, silakan kunjungi website kami di example.com/harga | 10 |
| `promo` | Promo bulan ini: Diskon 20% untuk semua produk! | 5 |
| `info` | Terima kasih telah menghubungi kami. Ada yang bisa kami bantu? | 1 |

### Mengatur Prioritas

Jika pesan masuk cocok dengan lebih dari satu keyword, sistem akan menggunakan rule dengan prioritas tertinggi.

**Contoh:**
- Pesan masuk: "info harga produk"
- Cocok dengan: "harga" (priority 10) dan "info" (priority 1)
- Balasan yang dikirim: Rule "harga" (priority lebih tinggi)

### Menonaktifkan Auto-Reply

Untuk menonaktifkan rule tanpa menghapusnya:

1. Di halaman Keyword Rules, temukan rule yang ingin dinonaktifkan
2. Klik toggle **Active** untuk menonaktifkan
3. Rule akan berstatus **Inactive** dan tidak akan merespons pesan

**Cooldown:**
Sistem membatasi 1 balasan per nomor per keyword dalam 60 menit untuk mencegah loop balasan.

---

## API Token

API Token memungkinkan integrasi dengan sistem eksternal. **Fitur ini hanya tersedia untuk paket Business.**

### Membuat API Token

1. Klik menu **API Tokens** di sidebar
2. Klik tombol **+ Create Token**
3. Masukkan **Nama Token** (untuk identifikasi, contoh: "Website Integration")
4. Klik **Generate Token**
5. **PENTING:** Salin token yang ditampilkan!

> ⚠️ **Peringatan:** Token hanya ditampilkan **SEKALI**. Setelah menutup dialog, token tidak dapat ditampilkan kembali. Simpan token di tempat yang aman.

### Menggunakan API Token

Gunakan token di header `Authorization` untuk setiap request API:

```bash
curl -X POST https://your-domain.com/api/v1/send-message \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "device_id": 1,
    "to": "6281234567890",
    "message": "Halo dari API!"
  }'
```

Dokumentasi API lengkap tersedia di [API Documentation](./api/README.md).

### Mencabut API Token

Jika token terkompromi atau tidak digunakan lagi:

1. Di halaman API Tokens, temukan token yang ingin dicabut
2. Klik tombol **Revoke**
3. Konfirmasi pencabutan

Token yang dicabut tidak dapat digunakan lagi dan tidak dapat dipulihkan.

---

## Message Logs

Message Logs mencatat semua aktivitas pengiriman pesan.

### Melihat Riwayat Pesan

1. Klik menu **Message Logs** di sidebar
2. Daftar pesan akan ditampilkan dengan informasi:
   - Nomor tujuan
   - Preview pesan
   - Status pengiriman
   - Waktu pengiriman
   - Device pengirim
   - Sumber (broadcast/trigger/reminder/api/auto_reply)

### Filter dan Pencarian

Gunakan filter untuk mempersempit hasil:

| Filter | Fungsi |
|--------|--------|
| **Tanggal** | Filter berdasarkan rentang tanggal |
| **Status** | Filter: Sent, Failed, Pending, Cancelled |
| **Device** | Filter berdasarkan device pengirim |
| **Nomor** | Cari berdasarkan nomor tujuan |
| **Sumber** | Filter berdasarkan sumber pengiriman |

### Export ke CSV

Untuk mengunduh data log:

1. Atur filter sesuai kebutuhan
2. Klik tombol **Export CSV**
3. File CSV akan diunduh dengan semua data sesuai filter

**Kolom dalam file export:**
- Nomor tujuan
- Isi pesan
- Status
- Waktu pengiriman
- Device
- Template (jika ada)
- Error message (jika gagal)

---

## Subscription

### Melihat Status Langganan

1. Klik menu **Subscription** di sidebar
2. Informasi yang ditampilkan:
   - Paket aktif
   - Tanggal mulai dan berakhir
   - Sisa kuota pesan
   - Fitur yang tersedia

### Memahami Kuota

| Paket | Kuota Pesan | Device | Fitur |
|-------|-------------|--------|-------|
| **Starter** | 100/bulan | 1 | Broadcast, Template |
| **Pro** | 1.000/bulan | 1 | + Reminder |
| **Business** | Unlimited | Unlimited | + API, Multi-device |

**Kuota akan:**
- Berkurang 1 untuk setiap pesan yang masuk antrian
- Reset ke nilai awal saat periode langganan diperbarui
- Menampilkan notifikasi saat mencapai 90%
- Memblokir pengiriman saat habis (0)

### Upgrade Paket

Untuk upgrade ke paket yang lebih tinggi:

1. Di halaman Subscription, lihat perbandingan paket
2. Klik **Upgrade** pada paket yang diinginkan
3. Hubungi admin melalui WhatsApp untuk proses pembayaran
4. Setelah pembayaran dikonfirmasi, paket akan diaktifkan

**Kontak untuk upgrade:**
- WhatsApp: [wa.me/6281529211963](https://wa.me/6281529211963)

---

## Troubleshooting

### QR Code Tidak Muncul

**Penyebab:** Baileys Gateway tidak berjalan atau tidak dapat dijangkau.

**Solusi:**
1. Pastikan Baileys Gateway sudah berjalan
2. Cek konfigurasi `BAILEYS_GATEWAY_URL` di `.env`
3. Refresh halaman dan coba lagi
4. Hubungi admin jika masalah berlanjut

### Device Terputus Tiba-tiba

**Penyebab:** WhatsApp memutus koneksi karena inaktivitas atau update.

**Solusi:**
1. Buka halaman Devices
2. Klik **Connect** pada device yang terputus
3. Scan QR code baru dengan WhatsApp

**Pencegahan:**
- Pastikan HP dengan WhatsApp tetap terhubung ke internet
- Jangan logout dari WhatsApp di HP

### Pesan Tidak Terkirim

**Cek langkah berikut:**

1. **Status Device**: Pastikan device berstatus **Connected**
2. **Kuota**: Pastikan kuota pesan masih tersedia
3. **Format Nomor**: Gunakan format internasional (628xxx)
4. **Queue Worker**: Pastikan queue worker berjalan
5. **Message Logs**: Cek detail error di Message Logs

**Status pesan di Message Logs:**

| Status | Keterangan | Solusi |
|--------|------------|--------|
| Pending | Menunggu dalam antrian | Tunggu proses queue |
| Retrying | Sedang dicoba ulang | Tunggu hingga 3x percobaan |
| Failed | Gagal permanen | Cek error message, perbaiki, retry manual |
| Cancelled | Dibatalkan | Cek kuota atau status subscription |

### Kuota Cepat Habis

**Tips menghemat kuota:**
1. Gunakan template untuk pesan berulang
2. Validasi nomor sebelum broadcast
3. Hindari mengirim ke nomor yang tidak aktif
4. Pertimbangkan upgrade ke paket lebih tinggi

### Auto-Reply Tidak Bekerja

**Cek langkah berikut:**

1. **Status Rule**: Pastikan rule berstatus **Active**
2. **Device**: Pastikan rule terhubung ke device yang benar
3. **Keyword**: Pastikan keyword sesuai (tidak case-sensitive)
4. **Cooldown**: Tunggu 60 menit jika sudah pernah reply ke nomor yang sama

### API Request Gagal

**Error 401 - Unauthorized:**
- Token tidak valid atau sudah dicabut
- Pastikan format header benar: `Authorization: Bearer YOUR_TOKEN`

**Error 403 - Forbidden:**
- Paket bukan Business
- Subscription expired

**Error 422 - Validation Error:**
- Cek format nomor (harus 628xxx)
- Cek device_id valid dan connected
- Cek parameter wajib lengkap

**Error 429 - Rate Limit:**
- Tunggu 1 menit sebelum request berikutnya
- Maksimal 60 request per menit per token

### Broadcast Lambat

**Penjelasan:**
Sistem sengaja mengirim pesan dengan jeda 5-10 detik antar pesan untuk mencegah pemblokiran nomor oleh WhatsApp.

**Estimasi waktu:**
- 100 pesan ≈ 8-17 menit
- 1.000 pesan ≈ 1.5-3 jam
- 10.000 pesan ≈ 14-28 jam

### Butuh Bantuan Lebih Lanjut?

Jika masalah tidak terselesaikan:

1. Cek [FAQ](#) di website
2. Hubungi support via WhatsApp: [wa.me/6281529211963](https://wa.me/6281529211963)
3. Sertakan informasi:
   - Screenshot error
   - Langkah yang sudah dicoba
   - ID tenant/email akun

---

## Tips & Best Practices

### Pengiriman Pesan

✅ **Do:**
- Validasi nomor sebelum broadcast
- Gunakan template untuk konsistensi
- Kirim di jam kerja (08:00-17:00)
- Monitor progress broadcast

❌ **Don't:**
- Kirim spam atau pesan tidak diinginkan
- Gunakan nomor tanpa izin penerima
- Kirim terlalu banyak pesan dalam waktu singkat
- Abaikan pesan gagal

### Keamanan

✅ **Do:**
- Simpan API token dengan aman
- Gunakan password yang kuat
- Logout dari perangkat publik
- Revoke token yang tidak digunakan

❌ **Don't:**
- Share API token
- Simpan token di repository publik
- Gunakan password yang sama dengan akun lain
- Biarkan sesi login terbuka

### Template

✅ **Do:**
- Gunakan variabel untuk personalisasi
- Buat template untuk setiap kebutuhan
- Review template sebelum broadcast
- Gunakan bahasa yang sopan

❌ **Don't:**
- Buat template terlalu panjang (>4096 karakter)
- Gunakan variabel yang tidak tersedia
- Hapus template yang masih digunakan reminder

---

*Dokumentasi ini terakhir diperbarui: April 2026*
