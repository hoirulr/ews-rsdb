# Panduan Deployment EWS RSUD Depati Bahrin ke aaPanel

Panduan lengkap memindahkan aplikasi dari lokal (Laragon/Windows) ke production server Linux dengan aaPanel.

---

## Prasyarat Server

| Kebutuhan | Minimum |
|-----------|---------|
| OS | Ubuntu 22.04+ / Debian 12+ |
| RAM | 2 GB |
| Disk | 20 GB |
| aaPanel | Versi terbaru |

---

## Langkah 1: Install aaPanel

SSH ke server, lalu jalankan:

```bash
# Ubuntu/Debian
wget -O install.sh http://www.aapanel.com/script/install-ubuntu_6.0_en.sh && bash install.sh aapanel
```

Setelah selesai, catat URL login, username, dan password yang ditampilkan.

---

## Langkah 2: Install Software via aaPanel

Login ke aaPanel, lalu install melalui **App Store**:

### Wajib
| Software | Versi | Catatan |
|----------|-------|---------|
| **Nginx** | 1.24+ | Web server |
| **MySQL** | 8.0+ | Database |
| **PHP** | 8.3+ | Sesuai `composer.json` requirement `^8.3` |
| **phpMyAdmin** | Terbaru | Manajemen database (opsional) |

### PHP Extensions (wajib)
Buka **App Store → PHP 8.3 → Extensions**, install:
- `fileinfo`
- `opcache`
- `redis` *(opsional, jika nanti pakai Redis)*
- `bcmath`
- `mbstring` (biasanya sudah terinstall)
- `pdo_mysql` (biasanya sudah terinstall)

### Tools
| Software | Fungsi |
|----------|--------|
| **Supervisor Manager** | Menjalankan queue worker & Reverb sebagai daemon |

> [!IMPORTANT]
> Pastikan **Supervisor Manager** terinstall dari App Store → Tools. Tanpa ini, queue worker dan Reverb tidak bisa jalan otomatis.

---

## Langkah 3: Install Node.js & Composer

SSH ke server:

```bash
# Install Node.js 20 LTS (untuk build frontend)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Verifikasi
node -v   # v20.x.x
npm -v    # 10.x.x

# Composer biasanya sudah terinstall oleh aaPanel
# Jika belum:
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

---

## Langkah 4: Buat Website di aaPanel

1. Buka **aaPanel → Website → Add Site**
2. Isi:
   - **Domain**: `ews.domain-anda.com` (sesuaikan)
   - **Root Directory**: `/www/wwwroot/ews-rsdb`
   - **PHP Version**: PHP 8.3
   - **Database**: MySQL → nama database: `ews_rsud_depatibahrin`
   - Catat **username** dan **password** database yang dibuat

---

## Langkah 5: Upload Project

### Opsi A: Via Git (Direkomendasikan)

```bash
cd /www/wwwroot
git clone https://github.com/username/ews-rsdb.git ews-rsdb
```

### Opsi B: Via ZIP Upload
1. Di lokal, kecualikan folder yang tidak perlu:
   ```
   # Folder yang TIDAK perlu di-upload:
   - node_modules/
   - vendor/
   - .composer-cache/
   - .git/
   - storage/logs/*
   ```
2. Upload ZIP via **aaPanel → File Manager** ke `/www/wwwroot/ews-rsdb`
3. Extract di server

---

## Langkah 6: Install Dependencies di Server

SSH ke server:

```bash
cd /www/wwwroot/ews-rsdb

# Install PHP dependencies (tanpa dev packages)
composer install --no-dev --optimize-autoloader --no-interaction

# Install Node.js dependencies & build frontend
npm ci
npm run build
```

---

## Langkah 7: Konfigurasi Environment

```bash
cd /www/wwwroot/ews-rsdb

# Buat file .env
cp .env.example .env

# Generate app key
php artisan key:generate
```

Edit file `.env` (via aaPanel File Manager atau `nano .env`):

```env
APP_NAME="EWS RSUD Depati Bahrin"
APP_ENV=production
APP_KEY=              # sudah di-generate
APP_DEBUG=false
APP_URL=https://ews.domain-anda.com

APP_LOCALE=id
APP_FALLBACK_LOCALE=id

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

# === DATABASE ===
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ews_rsud_depatibahrin
DB_USERNAME=ews_rsud_depatibahrin    # sesuai yang dibuat aaPanel
DB_PASSWORD=password_dari_aapanel    # sesuai yang dibuat aaPanel

# === SESSION ===
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=null

# === BROADCAST & QUEUE ===
BROADCAST_CONNECTION=reverb
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database

# === REVERB (WebSocket) ===
REVERB_APP_ID=ews-rsud-production
REVERB_APP_KEY=GANTI_DENGAN_KEY_RANDOM_AMAN
REVERB_APP_SECRET=GANTI_DENGAN_SECRET_RANDOM_AMAN
REVERB_HOST=ews.domain-anda.com
REVERB_PORT=443
REVERB_SCHEME=https

# === VITE (Frontend Reverb) ===
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

> [!WARNING]
> **Generate Reverb key/secret yang aman!** Jangan pakai key default dari development.
> ```bash
> # Generate random key
> php -r "echo bin2hex(random_bytes(16)) . PHP_EOL;"
> # Generate random secret
> php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
> ```

---

## Langkah 8: Setup Database

```bash
cd /www/wwwroot/ews-rsdb

# Jalankan migrasi
php artisan migrate --force

# Jalankan seeder (role, permission, faskes, user awal)
php artisan db:seed --force

# Optimasi Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## Langkah 9: Set Permission File

```bash
cd /www/wwwroot/ews-rsdb

# Set ownership
chown -R www:www .

# Set permission directory
find . -type d -exec chmod 755 {} \;

# Set permission file
find . -type f -exec chmod 644 {} \;

# Storage & cache harus writable
chmod -R 775 storage bootstrap/cache
```

---

## Langkah 10: Konfigurasi Nginx

Buka **aaPanel → Website → ews.domain-anda.com → Configuration**.

Ganti isi konfigurasi Nginx menjadi:

```nginx
server {
    listen 80;
    listen 443 ssl;
    server_name ews.domain-anda.com;

    # SSL (akan otomatis diisi oleh aaPanel jika pakai Let's Encrypt)
    # ssl_certificate    /path/to/cert.pem;
    # ssl_certificate_key /path/to/key.pem;

    root /www/wwwroot/ews-rsdb/public;
    index index.php;

    charset utf-8;

    # Laravel routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP processing
    location ~ \.php$ {
        fastcgi_pass unix:/tmp/php-cgi-83.sock;  # sesuaikan versi PHP
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # === REVERB WebSocket Reverse Proxy ===
    location /app {
        proxy_pass             http://127.0.0.1:8080;
        proxy_http_version     1.1;
        proxy_set_header       Upgrade $http_upgrade;
        proxy_set_header       Connection "upgrade";
        proxy_set_header       Host $host;
        proxy_set_header       X-Real-IP $remote_addr;
        proxy_set_header       X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header       X-Forwarded-Proto $scheme;
        proxy_read_timeout     60s;
        proxy_send_timeout     60s;
    }

    # Reverb API endpoint
    location /apps {
        proxy_pass             http://127.0.0.1:8080;
        proxy_http_version     1.1;
        proxy_set_header       Host $host;
        proxy_set_header       X-Real-IP $remote_addr;
        proxy_set_header       X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header       X-Forwarded-Proto $scheme;
    }

    # Deny dotfiles
    location ~ /\.(?!well-known) {
        deny all;
    }

    # Cache static assets
    location ~* \.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    access_log /www/wwwlogs/ews-rsdb.log;
    error_log  /www/wwwlogs/ews-rsdb.error.log;
}
```

> [!IMPORTANT]
> Bagian `location /app` dan `location /apps` adalah **reverse proxy untuk Reverb WebSocket**. Tanpa ini, koneksi WebSocket dari browser tidak akan sampai ke Reverb yang berjalan di port 8080.

---

## Langkah 11: Pasang SSL (HTTPS)

Buka **aaPanel → Website → ews.domain-anda.com → SSL**:

1. Pilih **Let's Encrypt**
2. Centang domain
3. Klik **Apply**
4. Aktifkan **Force HTTPS**

> [!CAUTION]
> SSL **wajib** untuk production karena:
> - WebSocket Secure (WSS) membutuhkan HTTPS
> - `.env` sudah dikonfigurasi `REVERB_SCHEME=https` dan `REVERB_PORT=443`
> - Browser modern memblokir koneksi WS (non-secure) dari halaman HTTPS

---

## Langkah 12: Setup Supervisor (Queue Worker & Reverb)

Buka **aaPanel → App Store → Supervisor Manager → Add Daemon**

### Daemon 1: Queue Worker - Alert (Prioritas Tinggi)

| Field | Nilai |
|-------|-------|
| **Name** | `ews-alert-worker` |
| **Run User** | `www` |
| **Run Dir** | `/www/wwwroot/ews-rsdb` |
| **Command** | `php artisan queue:work database --queue=ews-alert --sleep=1 --tries=3 --timeout=30 --max-jobs=500 --max-time=3600` |
| **Processes** | `2` |

### Daemon 2: Queue Worker - Log

| Field | Nilai |
|-------|-------|
| **Name** | `ews-log-worker` |
| **Run User** | `www` |
| **Run Dir** | `/www/wwwroot/ews-rsdb` |
| **Command** | `php artisan queue:work database --queue=ews-log,default --sleep=3 --tries=5 --timeout=10 --max-jobs=1000 --max-time=3600` |
| **Processes** | `1` |

### Daemon 3: Reverb WebSocket Server

| Field | Nilai |
|-------|-------|
| **Name** | `ews-reverb` |
| **Run User** | `www` |
| **Run Dir** | `/www/wwwroot/ews-rsdb` |
| **Command** | `php artisan reverb:start --host=127.0.0.1 --port=8080` |
| **Processes** | `1` |

> [!NOTE]
> Reverb di-bind ke `127.0.0.1` saja (tidak ke `0.0.0.0`) karena traffic WebSocket sudah di-proxy melalui Nginx. Ini lebih aman.

Setelah menambahkan ketiga daemon, pastikan statusnya **Running** di Supervisor Manager.

---

## Langkah 13: Setup Cron Scheduler

Buka **aaPanel → Cron → Add Task**:

| Field | Nilai |
|-------|-------|
| **Type** | Shell Script |
| **Name** | `Laravel Scheduler` |
| **Period** | Every 1 Minute |
| **Script** | `cd /www/wwwroot/ews-rsdb && php artisan schedule:run >> /dev/null 2>&1` |

Scheduler ini menjalankan:
- `ews:monitor-failed` — setiap 5 menit, monitor broadcast yang gagal
- `queue:flush` — setiap hari jam 02:00, bersihkan failed jobs

---

## Langkah 14: Rebuild Frontend

Karena `.env` production berbeda (terutama `VITE_REVERB_*`), Anda **harus rebuild** di server:

```bash
cd /www/wwwroot/ews-rsdb
npm run build
```

> [!IMPORTANT]
> Setiap kali mengubah nilai `VITE_REVERB_HOST`, `VITE_REVERB_PORT`, atau `VITE_REVERB_SCHEME` di `.env`, Anda harus menjalankan `npm run build` ulang karena Vite meng-embed environment variable saat build time.

---

## Langkah 15: Verifikasi

### Checklist Final

```bash
cd /www/wwwroot/ews-rsdb

# 1. Cek aplikasi bisa diakses
curl -I https://ews.domain-anda.com

# 2. Cek migrasi sudah lengkap
php artisan migrate:status

# 3. Cek queue worker berjalan
php artisan queue:monitor ews-alert,ews-log,default

# 4. Test broadcast manual
php artisan tinker --execute '
  $a = App\Models\EwsAssessment::where("zona","merah")->first();
  if ($a) { broadcast(new App\Events\EwsAlertTriggered($a)); echo "OK"; }
  else { echo "Tidak ada assessment zona merah"; }
'
```

### Verifikasi di Browser

| Yang Dicek | Cara |
|------------|------|
| Website bisa diakses | Buka `https://ews.domain-anda.com` |
| Login berhasil | Login sebagai admin RSUD |
| Dashboard IGD | Buka dashboard, pastikan data tampil |
| WebSocket konek | Buka DevTools → Network → WS, cek ada koneksi ke `/app` |
| Alert & suara | Input rujukan zona merah dari faskes, cek alert muncul & suara berbunyi di dashboard IGD |

---

## Troubleshooting

### WebSocket tidak konek
```bash
# Cek Reverb berjalan
supervisorctl status ews-reverb

# Cek log Reverb
tail -f /var/log/supervisor/ews-reverb.log

# Test koneksi internal
curl -i http://127.0.0.1:8080
```

### Alert tidak muncul
```bash
# Cek queue worker berjalan
supervisorctl status ews-alert-worker

# Cek ada job pending di queue
php artisan tinker --execute 'echo DB::table("jobs")->count();'

# Cek log queue worker
tail -f /var/log/supervisor/ews-alert-worker.log
```

### Error 502 Bad Gateway
- PHP tidak jalan → restart PHP: `service php-fpm-83 restart`
- Socket path salah → cek path di Nginx config vs `phpfpm` socket

### Permission error
```bash
chown -R www:www /www/wwwroot/ews-rsdb
chmod -R 775 storage bootstrap/cache
```

### Setelah deploy update kode
```bash
cd /www/wwwroot/ews-rsdb

git pull origin main

composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Restart queue worker agar load kode terbaru
supervisorctl restart ews-alert-worker:*
supervisorctl restart ews-log-worker:*
```

---

## Ringkasan Arsitektur Production

```
Browser (HTTPS)
    │
    ├── HTTP Request ──→ Nginx ──→ PHP-FPM ──→ Laravel
    │
    └── WebSocket (WSS) ──→ Nginx (/app) ──→ Reverb (:8080)
                                                  ↑
                              Queue Worker ──→ broadcast()
                                   ↑
                              Database Queue (jobs table)
                                   ↑
                              EwsRujukanService::kirimRujukan()
```

| Proses | Dikelola Oleh | Auto-Restart |
|--------|--------------|--------------|
| Nginx | aaPanel | ✅ |
| PHP-FPM | aaPanel | ✅ |
| MySQL | aaPanel | ✅ |
| Queue Worker (alert) | Supervisor | ✅ |
| Queue Worker (log) | Supervisor | ✅ |
| Reverb | Supervisor | ✅ |
| Scheduler | Cron | ✅ |
