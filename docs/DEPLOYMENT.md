# GoBlast Deployment Guide

Panduan lengkap untuk deploy GoBlast WhatsApp Automation Platform ke production environment.

---

## Table of Contents

1. [System Requirements](#system-requirements)
2. [Server Preparation](#server-preparation)
3. [Laravel Application Setup](#laravel-application-setup)
4. [Database Setup](#database-setup)
5. [Queue Worker Setup](#queue-worker-setup)
6. [Scheduler Setup](#scheduler-setup)
7. [Baileys Gateway Setup](#baileys-gateway-setup)
8. [SSL/TLS Configuration](#ssltls-configuration)
9. [Nginx Configuration](#nginx-configuration)
10. [Process Management](#process-management)
11. [Monitoring & Logging](#monitoring--logging)
12. [Security Hardening](#security-hardening)
13. [Backup & Recovery](#backup--recovery)
14. [Troubleshooting](#troubleshooting)

---

## System Requirements

### Minimum Hardware

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| CPU | 2 cores | 4+ cores |
| RAM | 4 GB | 8+ GB |
| Storage | 40 GB SSD | 100+ GB SSD |
| Network | 100 Mbps | 1 Gbps |

### Software Requirements

| Software | Version | Purpose |
|----------|---------|---------|
| Ubuntu | 22.04 LTS / 24.04 LTS | Operating System |
| PHP | 8.4+ | Laravel Runtime |
| MySQL | 8.0+ | Database |
| Node.js | 18+ | Baileys Gateway |
| Nginx | 1.18+ | Web Server |
| Composer | 2.x | PHP Dependencies |
| npm | 9+ | Node.js Dependencies |
| Supervisor | 4.x | Process Management |
| Certbot | Latest | SSL Certificates |

---

## Server Preparation

### 1. Update System

```bash
sudo apt update && sudo apt upgrade -y
```

### 2. Install Required Packages

```bash
# Essential tools
sudo apt install -y curl wget git unzip software-properties-common

# Add PHP repository
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.4 and extensions
sudo apt install -y php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring \
    php8.4-xml php8.4-curl php8.4-zip php8.4-bcmath php8.4-gd \
    php8.4-intl php8.4-redis php8.4-opcache

# Install MySQL
sudo apt install -y mysql-server

# Install Nginx
sudo apt install -y nginx

# Install Node.js 20 LTS
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Supervisor
sudo apt install -y supervisor

# Install Certbot for SSL
sudo apt install -y certbot python3-certbot-nginx
```

### 3. Create Application User

```bash
# Create dedicated user for the application
sudo useradd -m -s /bin/bash goblast
sudo usermod -aG www-data goblast

# Create application directory
sudo mkdir -p /var/www/goblast
sudo chown goblast:www-data /var/www/goblast
```

---

## Laravel Application Setup

### 1. Clone Repository

```bash
sudo -u goblast -i
cd /var/www/goblast

git clone <repository-url> .
```

### 2. Install Dependencies

```bash
# PHP dependencies (production)
composer install --no-dev --optimize-autoloader

# Node.js dependencies and build assets
npm ci
npm run build
```

### 3. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` dengan konfigurasi production:

```env
# Application
APP_NAME="GoBlast"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Timezone
APP_TIMEZONE=Asia/Jakarta

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=goblast
DB_USERNAME=goblast_user
DB_PASSWORD=your_secure_password_here

# Session & Cache
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Mail (SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.yourmailprovider.com
MAIL_PORT=587
MAIL_USERNAME=your_smtp_username
MAIL_PASSWORD=your_smtp_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"

# Baileys Gateway
BAILEYS_GATEWAY_URL=http://127.0.0.1:3000
BAILEYS_WEBHOOK_SECRET=generate_a_strong_random_secret_here

# WA Automation Settings
WA_AUTOMATION_TRIAL_DURATION_DAYS=14
WA_AUTOMATION_LOG_RETENTION_DAYS=90
WA_AUTOMATION_SYSTEM_LOG_RETENTION_DAYS=180
WA_AUTOMATION_DEFAULT_RATE_LIMIT_PER_HOUR=200
WA_AUTOMATION_DEFAULT_DELAY_MIN_SECONDS=5
WA_AUTOMATION_DEFAULT_DELAY_MAX_SECONDS=10
```

### 4. Generate Webhook Secret

```bash
# Generate a secure random secret
openssl rand -hex 32
```

Gunakan output ini untuk `BAILEYS_WEBHOOK_SECRET` di Laravel dan `WEBHOOK_SECRET` di Gateway.

### 5. Set Permissions

```bash
# Storage and cache directories
sudo chown -R goblast:www-data /var/www/goblast/storage
sudo chown -R goblast:www-data /var/www/goblast/bootstrap/cache

sudo chmod -R 775 /var/www/goblast/storage
sudo chmod -R 775 /var/www/goblast/bootstrap/cache
```

### 6. Optimize for Production

```bash
# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Create storage link
php artisan storage:link
```

---

## Database Setup

### 1. Secure MySQL Installation

```bash
sudo mysql_secure_installation
```

### 2. Create Database and User

```bash
sudo mysql -u root -p
```

```sql
-- Create database
CREATE DATABASE goblast CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user
CREATE USER 'goblast_user'@'localhost' IDENTIFIED BY 'your_secure_password_here';

-- Grant privileges
GRANT ALL PRIVILEGES ON goblast.* TO 'goblast_user'@'localhost';
FLUSH PRIVILEGES;

EXIT;
```

### 3. Run Migrations

```bash
cd /var/www/goblast
php artisan migrate --force
```

### 4. Seed Initial Data

```bash
# Seed default plans and system configs
php artisan db:seed --class=PlanSeeder --force
php artisan db:seed --class=SystemConfigSeeder --force

# Optional: Create superadmin user
php artisan tinker --execute "
\App\Models\User::create([
    'name' => 'Super Admin',
    'email' => 'admin@yourdomain.com',
    'password' => bcrypt('your_secure_password'),
    'role' => 'superadmin',
    'email_verified_at' => now(),
]);
"
```

### 5. MySQL Performance Tuning

Edit `/etc/mysql/mysql.conf.d/mysqld.cnf`:

```ini
[mysqld]
# InnoDB Settings
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT

# Query Cache (disabled in MySQL 8.0+)
# Use application-level caching instead

# Connection Settings
max_connections = 200
wait_timeout = 600
interactive_timeout = 600

# Logging
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
```

Restart MySQL:

```bash
sudo systemctl restart mysql
```

---

## Queue Worker Setup

GoBlast menggunakan Laravel Queue untuk memproses pengiriman pesan secara asinkron.

### 1. Create Supervisor Configuration

```bash
sudo nano /etc/supervisor/conf.d/goblast-worker.conf
```

```ini
[program:goblast-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/goblast/artisan queue:work database --sleep=3 --tries=3 --max-time=3600 --memory=128
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=goblast
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/goblast/storage/logs/worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=3600
```

**Penjelasan parameter:**
- `numprocs=4`: Jalankan 4 worker processes (sesuaikan dengan CPU cores)
- `--sleep=3`: Tunggu 3 detik jika tidak ada job
- `--tries=3`: Retry job maksimal 3 kali
- `--max-time=3600`: Restart worker setiap 1 jam untuk mencegah memory leak
- `--memory=128`: Restart jika memory melebihi 128MB

### 2. Start Queue Workers

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start goblast-worker:*
```

### 3. Monitor Queue Workers

```bash
# Check status
sudo supervisorctl status goblast-worker:*

# View logs
tail -f /var/www/goblast/storage/logs/worker.log

# Restart workers (after code deployment)
sudo supervisorctl restart goblast-worker:*
```

### 4. Queue Monitoring Dashboard

Untuk monitoring queue via web, gunakan Laravel Horizon (opsional) atau cek via artisan:

```bash
# Monitor queue status
php artisan queue:monitor database:default

# List failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

---

## Scheduler Setup

Laravel Scheduler menjalankan tugas terjadwal seperti reminder, cleanup, dan health check.

### Scheduled Tasks

| Command | Schedule | Description |
|---------|----------|-------------|
| `reminder:process` | Every minute | Process scheduled reminders |
| `device:health-check` | Every minute | Check device connection status |
| `broadcast:dispatch-scheduled` | Every minute | Dispatch scheduled broadcasts |
| `alert:check` | Every 5 minutes | Check system alerts |
| `subscription:check-expiry` | Daily 08:00 WIB | Check subscription expiry |
| `trial:check-expiry` | Daily 08:00 WIB | Check trial expiry |
| `log:cleanup` | Weekly Sunday 02:00 WIB | Cleanup old logs |

### 1. Setup Cron Job

```bash
sudo crontab -u goblast -e
```

Add this line:

```cron
* * * * * cd /var/www/goblast && php artisan schedule:run >> /dev/null 2>&1
```

### 2. Verify Scheduler

```bash
# List scheduled tasks
php artisan schedule:list

# Run scheduler manually (for testing)
php artisan schedule:run

# Check scheduler logs
tail -f /var/www/goblast/storage/logs/laravel.log
```

---

## Baileys Gateway Setup

Baileys Gateway adalah service Node.js yang menangani koneksi WhatsApp.

### 1. Setup Gateway Directory

```bash
cd /var/www/goblast/gateway
npm ci --production
```

### 2. Configure Gateway Environment

```bash
cp .env.example .env
nano .env
```

```env
# Gateway Server
PORT=3000
HOST=127.0.0.1

# Laravel Webhook
WEBHOOK_URL=https://yourdomain.com/api/webhooks/baileys
WEBHOOK_SECRET=same_secret_as_laravel_baileys_webhook_secret

# Session Storage
SESSION_PATH=./sessions

# Logging
LOG_LEVEL=info

# Message Delay (ms)
MESSAGE_DELAY_MIN=3000
MESSAGE_DELAY_MAX=7000
```

> **PENTING:** `WEBHOOK_SECRET` harus sama persis dengan `BAILEYS_WEBHOOK_SECRET` di Laravel `.env`

### 3. Create Supervisor Configuration

```bash
sudo nano /etc/supervisor/conf.d/goblast-gateway.conf
```

```ini
[program:goblast-gateway]
command=/usr/bin/node /var/www/goblast/gateway/src/index.js
directory=/var/www/goblast/gateway
autostart=true
autorestart=true
user=goblast
redirect_stderr=true
stdout_logfile=/var/www/goblast/storage/logs/gateway.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
environment=NODE_ENV="production"
```

### 4. Start Gateway

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start goblast-gateway
```

### 5. Verify Gateway

```bash
# Check status
sudo supervisorctl status goblast-gateway

# Test health endpoint
curl http://127.0.0.1:3000/health

# View logs
tail -f /var/www/goblast/storage/logs/gateway.log
```

### 6. Session Persistence

Sessions WhatsApp disimpan di `/var/www/goblast/gateway/sessions/`. Pastikan folder ini:
- Di-backup secara regular
- Tidak dihapus saat deployment
- Memiliki permission yang benar

```bash
sudo chown -R goblast:goblast /var/www/goblast/gateway/sessions
sudo chmod -R 755 /var/www/goblast/gateway/sessions
```

---

## SSL/TLS Configuration

### 1. Obtain SSL Certificate with Certbot

```bash
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

### 2. Auto-Renewal

Certbot automatically sets up renewal. Verify:

```bash
sudo certbot renew --dry-run
```

### 3. SSL Configuration Best Practices

Certbot akan mengkonfigurasi Nginx secara otomatis. Untuk keamanan tambahan, edit `/etc/nginx/sites-available/goblast`:

```nginx
# SSL Configuration
ssl_protocols TLSv1.2 TLSv1.3;
ssl_prefer_server_ciphers on;
ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;
ssl_session_cache shared:SSL:10m;
ssl_session_timeout 1d;
ssl_session_tickets off;

# HSTS
add_header Strict-Transport-Security "max-age=63072000" always;
```

---

## Nginx Configuration

### 1. Create Nginx Site Configuration

```bash
sudo nano /etc/nginx/sites-available/goblast
```

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;

    root /var/www/goblast/public;
    index index.php;

    # SSL certificates (managed by Certbot)
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied any;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/json application/xml;

    # Client body size (for CSV uploads)
    client_max_body_size 10M;

    # Logging
    access_log /var/log/nginx/goblast_access.log;
    error_log /var/log/nginx/goblast_error.log;

    # Laravel routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Deny access to sensitive files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~ ^/(\.env|composer\.(json|lock)|package(-lock)?\.json|artisan) {
        deny all;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

### 2. Enable Site

```bash
sudo ln -s /etc/nginx/sites-available/goblast /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

### 3. PHP-FPM Configuration

Edit `/etc/php/8.4/fpm/pool.d/www.conf`:

```ini
[www]
user = goblast
group = www-data
listen = /var/run/php/php8.4-fpm.sock
listen.owner = www-data
listen.group = www-data

pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

php_admin_value[memory_limit] = 256M
php_admin_value[upload_max_filesize] = 10M
php_admin_value[post_max_size] = 10M
php_admin_value[max_execution_time] = 60
```

Restart PHP-FPM:

```bash
sudo systemctl restart php8.4-fpm
```

---

## Process Management

### Supervisor Commands

```bash
# View all processes
sudo supervisorctl status

# Start all GoBlast processes
sudo supervisorctl start goblast-worker:*
sudo supervisorctl start goblast-gateway

# Stop all GoBlast processes
sudo supervisorctl stop goblast-worker:*
sudo supervisorctl stop goblast-gateway

# Restart after deployment
sudo supervisorctl restart goblast-worker:*
sudo supervisorctl restart goblast-gateway

# Reload configuration
sudo supervisorctl reread
sudo supervisorctl update
```

### Systemd Services Status

```bash
# Check all services
sudo systemctl status nginx
sudo systemctl status php8.4-fpm
sudo systemctl status mysql
sudo systemctl status supervisor
```

---

## Monitoring & Logging

### 1. Application Logs

```bash
# Laravel logs
tail -f /var/www/goblast/storage/logs/laravel.log

# Queue worker logs
tail -f /var/www/goblast/storage/logs/worker.log

# Gateway logs
tail -f /var/www/goblast/storage/logs/gateway.log
```

### 2. System Logs

```bash
# Nginx access logs
tail -f /var/log/nginx/goblast_access.log

# Nginx error logs
tail -f /var/log/nginx/goblast_error.log

# MySQL slow query logs
tail -f /var/log/mysql/slow.log

# Supervisor logs
tail -f /var/log/supervisor/supervisord.log
```

### 3. Log Rotation

Create `/etc/logrotate.d/goblast`:

```
/var/www/goblast/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 goblast www-data
    sharedscripts
    postrotate
        /usr/bin/supervisorctl restart goblast-worker:* > /dev/null 2>&1 || true
    endscript
}
```

### 4. Health Monitoring

#### Internal Health Checks

GoBlast memiliki built-in health checks:

```bash
# Device health check (runs every minute via scheduler)
php artisan device:health-check

# Alert check (runs every 5 minutes via scheduler)
php artisan alert:check
```

#### External Monitoring (Recommended)

Setup monitoring dengan tools seperti:

- **UptimeRobot** atau **Pingdom**: Monitor URL availability
- **Netdata** atau **Prometheus + Grafana**: System metrics
- **Sentry** atau **Bugsnag**: Error tracking

#### Simple Health Check Endpoint

Buat endpoint untuk external monitoring:

```bash
# Test Laravel health
curl -s https://yourdomain.com/up

# Test Gateway health
curl -s http://127.0.0.1:3000/health
```

### 5. Alert Notifications

GoBlast mengirim alert ke Superadmin untuk:
- Gateway down (tidak merespons > 5 menit)
- Quota usage > 90%
- Failed jobs spike (> 50 dalam 1 jam)
- Subscription expiring

Pastikan konfigurasi email sudah benar di `.env`.

---

## Security Hardening

### 1. Firewall Configuration

```bash
# Install UFW
sudo apt install -y ufw

# Default policies
sudo ufw default deny incoming
sudo ufw default allow outgoing

# Allow SSH
sudo ufw allow ssh

# Allow HTTP/HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Enable firewall
sudo ufw enable
```

> **Note:** Gateway port 3000 tidak perlu dibuka karena hanya diakses dari localhost.

### 2. Fail2Ban

```bash
sudo apt install -y fail2ban

# Create jail configuration
sudo nano /etc/fail2ban/jail.local
```

```ini
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5

[sshd]
enabled = true

[nginx-http-auth]
enabled = true

[nginx-limit-req]
enabled = true
```

```bash
sudo systemctl restart fail2ban
```

### 3. Application Security Checklist

- [ ] `APP_DEBUG=false` di production
- [ ] `APP_ENV=production`
- [ ] Strong `APP_KEY` (generated)
- [ ] Strong `BAILEYS_WEBHOOK_SECRET`
- [ ] Strong database password
- [ ] HTTPS enabled
- [ ] File permissions correct (755 for directories, 644 for files)
- [ ] `.env` file not accessible via web
- [ ] Sensitive routes protected

### 4. Database Security

```sql
-- Remove anonymous users
DELETE FROM mysql.user WHERE User='';

-- Remove remote root login
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');

-- Remove test database
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';

FLUSH PRIVILEGES;
```

---

## Backup & Recovery

### 1. Database Backup

Create backup script `/var/www/goblast/scripts/backup-db.sh`:

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/goblast"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="goblast"
DB_USER="goblast_user"
DB_PASS="your_password"

mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Keep only last 7 days
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +7 -delete

echo "Database backup completed: db_$DATE.sql.gz"
```

```bash
chmod +x /var/www/goblast/scripts/backup-db.sh
```

### 2. Session Backup

WhatsApp sessions sangat penting. Backup secara regular:

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/goblast"
DATE=$(date +%Y%m%d_%H%M%S)

# Backup sessions
tar -czf $BACKUP_DIR/sessions_$DATE.tar.gz -C /var/www/goblast/gateway sessions/

# Keep only last 7 days
find $BACKUP_DIR -name "sessions_*.tar.gz" -mtime +7 -delete
```

### 3. Automated Backup Cron

```bash
sudo crontab -e
```

```cron
# Database backup daily at 02:00
0 2 * * * /var/www/goblast/scripts/backup-db.sh >> /var/log/goblast-backup.log 2>&1

# Session backup daily at 02:30
30 2 * * * /var/www/goblast/scripts/backup-sessions.sh >> /var/log/goblast-backup.log 2>&1
```

### 4. Recovery Procedure

```bash
# Restore database
gunzip < /var/backups/goblast/db_YYYYMMDD_HHMMSS.sql.gz | mysql -u goblast_user -p goblast

# Restore sessions
tar -xzf /var/backups/goblast/sessions_YYYYMMDD_HHMMSS.tar.gz -C /var/www/goblast/gateway/

# Restart services
sudo supervisorctl restart goblast-gateway
```

---

## Troubleshooting

### Common Issues

#### 1. 502 Bad Gateway

```bash
# Check PHP-FPM status
sudo systemctl status php8.4-fpm

# Check PHP-FPM logs
sudo tail -f /var/log/php8.4-fpm.log

# Restart PHP-FPM
sudo systemctl restart php8.4-fpm
```

#### 2. Queue Jobs Not Processing

```bash
# Check worker status
sudo supervisorctl status goblast-worker:*

# Check failed jobs
php artisan queue:failed

# Restart workers
sudo supervisorctl restart goblast-worker:*

# Clear queue (CAUTION: removes all pending jobs)
php artisan queue:clear
```

#### 3. Gateway Connection Failed

```bash
# Check gateway status
sudo supervisorctl status goblast-gateway

# Check gateway logs
tail -f /var/www/goblast/storage/logs/gateway.log

# Test gateway health
curl http://127.0.0.1:3000/health

# Restart gateway
sudo supervisorctl restart goblast-gateway
```

#### 4. WhatsApp Device Disconnected

1. Check gateway logs untuk error
2. Buka halaman Devices di GoBlast
3. Klik "Disconnect" lalu "Connect" lagi
4. Scan QR code baru

#### 5. Permission Denied Errors

```bash
# Fix storage permissions
sudo chown -R goblast:www-data /var/www/goblast/storage
sudo chmod -R 775 /var/www/goblast/storage

# Fix bootstrap/cache permissions
sudo chown -R goblast:www-data /var/www/goblast/bootstrap/cache
sudo chmod -R 775 /var/www/goblast/bootstrap/cache
```

#### 6. Memory Issues

```bash
# Check memory usage
free -h

# Check PHP memory limit
php -i | grep memory_limit

# Increase PHP memory in php.ini
sudo nano /etc/php/8.4/fpm/php.ini
# Set: memory_limit = 256M

sudo systemctl restart php8.4-fpm
```

### Useful Commands

```bash
# Clear all Laravel caches
php artisan optimize:clear

# Rebuild caches
php artisan optimize

# Check Laravel status
php artisan about

# Check routes
php artisan route:list

# Check scheduled tasks
php artisan schedule:list

# Monitor queue in real-time
php artisan queue:monitor database:default
```

---

## Deployment Checklist

### Pre-Deployment

- [ ] Backup database
- [ ] Backup WhatsApp sessions
- [ ] Notify users of maintenance (if needed)

### Deployment Steps

```bash
# 1. Pull latest code
cd /var/www/goblast
git pull origin main

# 2. Install dependencies
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# 3. Run migrations
php artisan migrate --force

# 4. Clear and rebuild caches
php artisan optimize:clear
php artisan optimize

# 5. Restart queue workers
sudo supervisorctl restart goblast-worker:*

# 6. Restart gateway (if gateway code changed)
sudo supervisorctl restart goblast-gateway

# 7. Restart PHP-FPM (optional, for opcache)
sudo systemctl restart php8.4-fpm
```

### Post-Deployment

- [ ] Verify application is accessible
- [ ] Check queue workers are running
- [ ] Check gateway is connected
- [ ] Monitor logs for errors
- [ ] Test critical functionality (send test message)

---

## Support

Untuk bantuan lebih lanjut:
- Dokumentasi API: `/docs/api/README.md`
- Troubleshooting: Lihat section di atas
- Logs: `/var/www/goblast/storage/logs/`

---

*Last updated: April 2026*
