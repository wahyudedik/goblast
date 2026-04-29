# Deployment Guide — aaPanel + VPS

Panduan step-by-step deploy Konektivitas (GoBlast) ke production menggunakan aaPanel di VPS.

---

## Persyaratan Server

| Komponen | Minimum |
|----------|---------|
| OS | Ubuntu 22.04 / 24.04 LTS |
| RAM | 2 GB (4 GB recommended) |
| CPU | 2 vCPU |
| Storage | 20 GB SSD |
| Node.js | 18+ |
| PHP | 8.4 |
| MySQL | 8.0 |

---

## Step 1: Install aaPanel

SSH ke server, lalu jalankan:

```bash
wget -O install.sh https://www.aapanel.com/script/install_7.0_en.sh && bash install.sh aapanel
```

Setelah selesai, catat URL panel, username, dan password yang muncul.

Login ke aaPanel via browser, lalu install **LNMP Stack**:
- Nginx (latest)
- MySQL 8.0
- PHP 8.4
- phpMyAdmin

---

## Step 2: Konfigurasi PHP

Di aaPanel → **App Store** → **PHP 8.4** → **Settings** → **Install Extensions**:

Install extension berikut:
- `fileinfo`
- `redis` (opsional, untuk cache/queue)
- `opcache`
- `bcmath`
- `mbstring`
- `xml`
- `curl`
- `zip`
- `gd`
- `intl`
- `pcntl` (penting untuk queue worker)

Lalu di tab **Disabled Functions**, hapus fungsi berikut dari daftar disabled:
- `putenv`
- `proc_open`
- `pcntl_signal`
- `pcntl_alarm`
- `pcntl_async_signals`

---

## Step 3: Buat Database

Di aaPanel → **Database** → **Add Database**:

| Field | Value |
|-------|-------|
| Database Name | `konektivitas` |
| Username | `konektivitas` |
| Password | (generate password kuat) |
| Encoding | `utf8mb4` |

Catat credentials ini untuk `.env`.

---

## Step 4: Buat Website

Di aaPanel → **Website** → **Add Site**:

| Field | Value |
|-------|-------|
| Domain | `konektivitas.com` |
| PHP Version | PHP 8.4 |
| Database | Tidak perlu (sudah dibuat) |

Setelah dibuat, klik domain → **SSL** → **Let's Encrypt** → centang domain → **Apply**.

---

## Step 5: Upload Project

### Opsi A: Git Clone (Recommended)

SSH ke server:

```bash
cd /www/wwwroot/konektivitas.com
rm -rf ./* ./.*  # Hapus file default aaPanel

git clone <repository-url> .
```

### Opsi B: Upload ZIP

Upload file project via aaPanel File Manager ke `/www/wwwroot/konektivitas.com/`, lalu extract.

---

## Step 6: Install Dependencies

```bash
cd /www/wwwroot/konektivitas.com

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node.js dependencies & build assets
npm install
npm run build

# Install gateway dependencies
cd gateway
npm install --production
cd ..
```

---

## Step 7: Konfigurasi Laravel

```bash
cd /www/wwwroot/konektivitas.com

# Copy environment file
cp .env.example .env

# Generate app key
php artisan key:generate
```

Edit `.env`:

```env
APP_NAME="Konektivitas"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://konektivitas.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=konektivitas
DB_USERNAME=konektivitas
DB_PASSWORD=PASSWORD_DARI_STEP_3

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

# Baileys Gateway
BAILEYS_GATEWAY_URL=http://127.0.0.1:3000
BAILEYS_WEBHOOK_SECRET=GENERATE_RANDOM_STRING_64_CHAR

# Mail (gunakan SMTP provider seperti Mailtrap, Mailgun, dll)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="info@konektivitas.com"
MAIL_FROM_NAME="Konektivitas"
```

Generate webhook secret yang kuat:

```bash
php artisan tinker --execute "echo bin2hex(random_bytes(32));"
```

Gunakan output-nya untuk `BAILEYS_WEBHOOK_SECRET`.

---

## Step 8: Setup Database & Seeder

```bash
cd /www/wwwroot/konektivitas.com

php artisan migrate --force
php artisan db:seed --force
```

---

## Step 9: Optimize Laravel

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize
```

---

## Step 10: Set Permissions

```bash
cd /www/wwwroot/konektivitas.com

# Set ownership
chown -R www:www .

# Set directory permissions
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;

# Writable directories
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

---

## Step 11: Konfigurasi Nginx

Di aaPanel → **Website** → klik domain → **Config** (Nginx config).

Ganti isi config dengan:

```nginx
server {
    listen 80;
    listen 443 ssl http2;
    server_name konektivitas.com www.konektivitas.com;

    root /www/wwwroot/konektivitas.com/public;
    index index.php;

    # SSL (dikelola aaPanel)
    # ssl_certificate    /path/to/cert.pem;
    # ssl_certificate_key /path/to/key.pem;

    # Redirect HTTP to HTTPS
    if ($scheme = http) {
        return 301 https://$host$request_uri;
    }

    # Redirect www to non-www
    if ($host = www.konektivitas.com) {
        return 301 https://konektivitas.com$request_uri;
    }

    # Laravel
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP
    location ~ \.php$ {
        fastcgi_pass unix:/tmp/php-cgi-84.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    # Proxy Baileys Gateway WebSocket (untuk QR code scan)
    location /gateway/ {
        proxy_pass http://127.0.0.1:3000/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_read_timeout 86400;
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }

    # Cache static assets
    location ~* \.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    access_log /www/wwwlogs/konektivitas.com.log;
    error_log /www/wwwlogs/konektivitas.com.error.log;
}
```

> **Catatan:** Jangan hapus baris SSL yang di-generate aaPanel. Sesuaikan path `fastcgi_pass` dengan versi PHP yang terinstall.

Restart Nginx setelah edit config.

---

## Step 12: Setup Baileys Gateway

### Konfigurasi Gateway

```bash
cd /www/wwwroot/konektivitas.com/gateway
cp .env.example .env
```

Edit `gateway/.env`:

```env
PORT=3000
HOST=127.0.0.1

WEBHOOK_URL=https://konektivitas.com/webhook/baileys
WEBHOOK_SECRET=SAMA_DENGAN_BAILEYS_WEBHOOK_SECRET_DI_LARAVEL

SESSION_PATH=./sessions
LOG_LEVEL=info

MESSAGE_DELAY_MIN=3000
MESSAGE_DELAY_MAX=7000
```

### Jalankan dengan PM2

Install PM2 secara global:

```bash
npm install -g pm2
```

Start gateway:

```bash
cd /www/wwwroot/konektivitas.com/gateway
pm2 start src/index.js --name "konektivitas-gateway"
pm2 save
pm2 startup
```

Verifikasi:

```bash
pm2 status
curl http://127.0.0.1:3000/health
```

---

## Step 13: Setup Queue Worker dengan Supervisor

### Install Supervisor

```bash
apt install supervisor -y
```

### Buat Config

```bash
nano /etc/supervisor/conf.d/konektivitas-worker.conf
```

Isi:

```ini
[program:konektivitas-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /www/wwwroot/konektivitas.com/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www
numprocs=2
redirect_stderr=true
stdout_logfile=/www/wwwroot/konektivitas.com/storage/logs/worker.log
stopwaitsecs=3600
```

### Aktifkan

```bash
supervisorctl reread
supervisorctl update
supervisorctl start konektivitas-worker:*
```

Verifikasi:

```bash
supervisorctl status
```

---

## Step 14: Setup Cron Job (Scheduler)

Di aaPanel → **Cron** → **Add Cron Job**:

| Field | Value |
|-------|-------|
| Type | Shell Script |
| Name | Laravel Scheduler |
| Cycle | Every 1 minute |
| Script | `cd /www/wwwroot/konektivitas.com && php artisan schedule:run >> /dev/null 2>&1` |

Atau via SSH:

```bash
crontab -e
```

Tambahkan:

```
* * * * * cd /www/wwwroot/konektivitas.com && php artisan schedule:run >> /dev/null 2>&1
```

Scheduler menjalankan:
- Reminder processing (setiap menit)
- Broadcast dispatch (setiap menit)
- Device health check (setiap menit)
- Alert check (setiap 5 menit)
- Subscription expiry check (harian 08:00 WIB)
- Trial expiry check (harian 08:00 WIB)
- Log cleanup (mingguan)

---

## Step 15: Verifikasi Deployment

### Checklist

```bash
cd /www/wwwroot/konektivitas.com

# 1. Cek Laravel
php artisan about

# 2. Cek database connection
php artisan db:monitor

# 3. Cek queue
php artisan queue:monitor

# 4. Cek gateway
curl http://127.0.0.1:3000/health

# 5. Cek scheduler
php artisan schedule:list

# 6. Cek supervisor
supervisorctl status

# 7. Cek PM2
pm2 status
```

### Test di Browser

1. Buka `https://konektivitas.com` — landing page harus muncul
2. Login dengan superadmin: `info@konektivitas.com` / `Wahyu123456789@`
3. Cek admin dashboard di `/admin`
4. Login dengan demo tenant: `admin@demo.test` / `password`
5. Coba tambah device dan scan QR code

---

## Maintenance

### Update Aplikasi

```bash
cd /www/wwwroot/konektivitas.com

# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader
npm install && npm run build

# Run migrations
php artisan migrate --force

# Clear & rebuild cache
php artisan optimize:clear
php artisan optimize

# Restart services
supervisorctl restart konektivitas-worker:*
pm2 restart konektivitas-gateway
```

### Monitoring

```bash
# Laravel logs
tail -f /www/wwwroot/konektivitas.com/storage/logs/laravel.log

# Queue worker logs
tail -f /www/wwwroot/konektivitas.com/storage/logs/worker.log

# Gateway logs
pm2 logs konektivitas-gateway

# Nginx logs
tail -f /www/wwwlogs/konektivitas.com.error.log
```

### Backup Database

Di aaPanel → **Database** → klik database → **Backup**.

Atau via CLI:

```bash
mysqldump -u konektivitas -p konektivitas > /www/backup/konektivitas_$(date +%Y%m%d).sql
```

---

## Troubleshooting Production

### 502 Bad Gateway

```bash
# Cek PHP-FPM
systemctl status php84-fpm
systemctl restart php84-fpm
```

### Queue Stuck

```bash
# Restart queue workers
supervisorctl restart konektivitas-worker:*

# Atau flush failed jobs
php artisan queue:flush
```

### Gateway Down

```bash
pm2 restart konektivitas-gateway
pm2 logs konektivitas-gateway --lines 50
```

### Permission Issues

```bash
chown -R www:www /www/wwwroot/konektivitas.com/storage
chmod -R 775 /www/wwwroot/konektivitas.com/storage
```

### Clear All Cache

```bash
cd /www/wwwroot/konektivitas.com
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
