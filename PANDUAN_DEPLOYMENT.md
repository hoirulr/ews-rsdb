# Panduan Deployment EWS RSUD Depati Bahrin

Panduan deploy aplikasi ke tiga jenis lingkungan: **cPanel**, **aaPanel**, dan **Docker**. Pilih salah satu sesuai server yang tersedia.

> Untuk aaPanel tersedia juga panduan yang lebih rinci di [deployment_guide_aapanel.md](deployment_guide_aapanel.md), dan untuk Docker berbasis CasaOS di [docker_deployment_guide.md](docker_deployment_guide.md). Dokumen ini adalah ringkasan praktis ketiganya dengan konfigurasi yang sudah disesuaikan untuk aplikasi ini.

---

## Kebutuhan Aplikasi (berlaku semua platform)

| Komponen | Kebutuhan |
|----------|-----------|
| PHP | ≥ 8.3 (disarankan 8.4) |
| Ekstensi PHP | `pdo_mysql`, `mbstring`, `bcmath`, `intl`, `zip`, `gd`, `fileinfo`, `opcache`, `pcntl` |
| Database | MySQL 8.0+ / MariaDB 10.11+ |
| Node.js | 20 LTS — **hanya untuk build asset** (boleh build di komputer lokal) |
| Composer | v2 |

Aplikasi ini punya **4 proses runtime** yang semuanya harus berjalan:

1. **Web server** (Nginx/Apache + PHP-FPM) — halaman aplikasi
2. **Queue worker** — antrean `ews-alert` (broadcast alert, prioritas tinggi), `ews-log` (audit log), `default`
3. **Laravel Reverb** — server WebSocket untuk alert real-time ke dashboard IGD
4. **Scheduler** — `php artisan schedule:run` tiap menit

> [!NOTE]
> Jika Reverb tidak bisa dijalankan (misalnya shared hosting), aplikasi **tetap berfungsi**: dashboard IGD punya fallback polling database setiap 10 detik, sehingga alert tetap muncul dengan keterlambatan maksimal ±10 detik. Reverb hanya membuat alert muncul seketika.

### Template `.env` produksi

Nilai yang **wajib diubah** dari pengembangan lokal:

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda.tld
APP_LOCALE=id

DB_HOST=...          # cPanel/aaPanel: 127.0.0.1 | Docker: mysql
DB_DATABASE=ews_rsud_depatibahrin
DB_USERNAME=...      # JANGAN pakai root
DB_PASSWORD=...      # password kuat

# --- Reverb: sisi BACKEND (PHP mengirim event ke server Reverb) ---
REVERB_APP_ID=...    # ganti, jangan pakai nilai contoh
REVERB_APP_KEY=...   # acak, mis. hasil: openssl rand -hex 16
REVERB_APP_SECRET=...# acak
REVERB_HOST=127.0.0.1   # Docker: reverb
REVERB_PORT=8080
REVERB_SCHEME=http

# --- Reverb: sisi BROWSER (di-embed ke JavaScript saat build asset) ---
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST=domain-anda.tld
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https

# Password awal seeder (hanya perlu saat seeding pertama)
SEED_ADMIN_PASSWORD=...
SEED_FASKES_PASSWORD=...
```

> [!IMPORTANT]
> Dua hal yang paling sering salah:
> 1. `REVERB_HOST` (backend) dan `VITE_REVERB_HOST` (browser) **berbeda nilai**. Backend menunjuk ke proses Reverb secara langsung (`127.0.0.1:8080` atau `reverb:8080` di Docker); browser menunjuk ke **domain publik lewat HTTPS** yang di-proxy web server.
> 2. Nilai `VITE_*` **dibekukan ke file JavaScript saat `npm run build`**. Kalau nilai Reverb di `.env` berubah, asset harus di-build ulang.

---

## A. Deploy ke cPanel

> [!WARNING]
> cPanel **shared hosting** adalah pilihan paling terbatas: biasanya tidak bisa menjalankan proses daemon (Reverb & queue worker permanen). Gunakan hanya jika tidak ada pilihan lain, dan pastikan paket hosting menyediakan **Terminal/SSH** dan **PHP 8.3+**. Jika servernya VPS dengan WHM (akses root), ikuti pola aaPanel (Bagian B) untuk worker & Reverb.

### 1. Siapkan PHP dan database

1. **MultiPHP Manager** → set domain ke **PHP 8.3/8.4**.
2. **Select PHP Extensions** (jika ada) → aktifkan `pdo_mysql`, `mbstring`, `bcmath`, `intl`, `zip`, `gd`, `fileinfo`, `opcache`.
3. **MySQL Databases** → buat database, buat user, berikan **ALL PRIVILEGES**. Catat nama DB (biasanya berprefix `namacpanel_`).

### 2. Upload kode dengan document root ke `public/`

Jangan letakkan aplikasi di dalam `public_html` secara langsung — file `.env` bisa terekspos.

1. **Git Version Control** (menu cPanel) → clone repo ke `/home/USER/ews-rsdb`
   (alternatif: upload ZIP lalu ekstrak ke folder yang sama).
2. Arahkan document root domain/subdomain ke `/home/USER/ews-rsdb/public`:
   - Subdomain/addon domain: bisa langsung diatur saat membuat domain.
   - Domain utama (document root tidak bisa diubah di shared hosting): isi `public_html/.htaccess` dengan:

   ```apache
   RewriteEngine On
   RewriteRule ^(.*)$ /ews-rsdb-public/$1 [L]
   ```

   lalu buat symlink `ln -s /home/USER/ews-rsdb/public /home/USER/public_html/ews-rsdb-public` — **atau** cara paling sederhana: minta provider mengubah document root.

### 3. Install dependensi dan konfigurasi (via Terminal cPanel)

```bash
cd ~/ews-rsdb
composer install --no-dev --optimize-autoloader
cp .env.example .env
nano .env          # isi sesuai template di atas
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force        # hanya deploy pertama; set SEED_* dulu
php artisan config:cache && php artisan route:cache && php artisan view:cache
chmod -R 775 storage bootstrap/cache
```

**Asset frontend**: shared hosting biasanya tidak punya Node.js. Build di komputer lokal **setelah** `.env` lokal diisi nilai `VITE_REVERB_*` produksi:

```bash
npm ci && npm run build
```

lalu upload folder `public/build` ke server (File Manager / SFTP).

### 4. Scheduler dan queue worker via Cron Jobs

Buka **Cron Jobs** di cPanel, tambahkan (setiap menit / `* * * * *`):

```
/usr/local/bin/php /home/USER/ews-rsdb/artisan schedule:run >/dev/null 2>&1
```

```
/usr/local/bin/php /home/USER/ews-rsdb/artisan queue:work database --queue=ews-alert,ews-log,default --stop-when-empty --tries=3 --max-time=55 >/dev/null 2>&1
```

> Pola `--stop-when-empty` menjalankan worker sebentar tiap menit lalu berhenti — kompatibel dengan shared hosting, konsekuensinya alert WebSocket tertunda hingga ±1 menit. Karena Reverb kemungkinan tidak jalan di shared hosting (lihat langkah 5), yang dipakai dashboard adalah polling 10 detik, jadi keterlambatan efektif alert = eksekusi job berikutnya (≤1 menit).

### 5. Reverb di cPanel

- **Shared hosting**: umumnya **tidak bisa** (butuh proses jalan terus + reverse proxy WebSocket). Lewati — aplikasi tetap berjalan dengan fallback polling. Kosongkan konfigurasi Echo dengan tetap mengisi `VITE_REVERB_*` seperti template (koneksi gagal diam-diam, tidak mengganggu).
- **VPS + WHM (root)**: jalankan Reverb via systemd/supervisor seperti pola aaPanel di bawah, lalu tambahkan proxy `/app` di Apache *(Pre VirtualHost Include)*:

  ```apache
  ProxyPass /app ws://127.0.0.1:8080/app
  ProxyPassReverse /app ws://127.0.0.1:8080/app
  ```

---

## B. Deploy ke aaPanel

Ringkasan — detail lengkap (screenshot path menu, konfigurasi Nginx penuh) ada di [deployment_guide_aapanel.md](deployment_guide_aapanel.md).

### 1. Software via App Store

- **Nginx 1.24+**, **MySQL 8**, **PHP 8.3/8.4** + extension `fileinfo`, `opcache`, `bcmath` (+ pastikan `pdo_mysql`, `mbstring`, `intl`, `zip`, `gd`)
- **Supervisor Manager** (wajib — untuk queue worker & Reverb)
- Node.js 20 & Composer via SSH (lihat panduan rinci)

### 2. Website + kode

1. **Website → Add Site**: domain, root `/www/wwwroot/ews-rsdb`, PHP 8.3, buat database.
2. Clone repo ke folder tersebut, lalu:

```bash
cd /www/wwwroot/ews-rsdb
composer install --no-dev --optimize-autoloader
cp .env.example .env && nano .env      # template di atas
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force            # hanya deploy pertama
npm ci && npm run build                # build SETELAH .env terisi
php artisan config:cache && php artisan route:cache && php artisan view:cache
chown -R www:www /www/wwwroot/ews-rsdb
```

3. **Site config → Rewrite** pilih `laravel`, dan set **Site directory → Run dir** ke `/public`.

### 3. Nginx proxy untuk Reverb

Tambahkan di dalam blok `server` site (menu **Config**):

```nginx
location ~ ^/apps? {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Host $host;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_read_timeout 3600;
}
```

Pasang SSL (menu **SSL** → Let's Encrypt) supaya `wss://` berfungsi.

### 4. Supervisor (App Store → Supervisor Manager → Add Daemon)

| Nama | Run Dir | Command | Proses |
|------|---------|---------|--------|
| `ews-alert-worker` | `/www/wwwroot/ews-rsdb` | `php artisan queue:work database --queue=ews-alert --sleep=1 --tries=3 --timeout=30 --max-jobs=500 --max-time=3600` | 2 |
| `ews-log-worker` | `/www/wwwroot/ews-rsdb` | `php artisan queue:work database --queue=ews-log,default --sleep=3 --tries=5 --timeout=60 --max-jobs=1000 --max-time=3600` | 1 |
| `ews-reverb` | `/www/wwwroot/ews-rsdb` | `php artisan reverb:start --host=127.0.0.1 --port=8080` | 1 |

### 5. Scheduler (menu **Cron**, tiap 1 menit)

```
cd /www/wwwroot/ews-rsdb && php artisan schedule:run >/dev/null 2>&1
```

---

## C. Deploy dengan Docker

File pendukung sudah tersedia di repo:

- [docker-compose.yml](docker-compose.yml) — 7 service: `app` (PHP-FPM), `web` (Nginx), `mysql`, `reverb`, `queue-alert`, `queue-log`, `scheduler`
- [deploy/docker/Dockerfile](deploy/docker/Dockerfile) — image PHP 8.4 + semua ekstensi yang dibutuhkan
- [deploy/docker/nginx.conf](deploy/docker/nginx.conf) — sudah termasuk proxy WebSocket Reverb

### 1. Siapkan kode dan `.env`

```bash
git clone https://github.com/hoirulr/ews-rsdb.git && cd ews-rsdb
cp .env.example .env
nano .env
```

Isi sesuai template umum di atas, dengan nilai khusus Docker:

```ini
DB_HOST=mysql
DB_USERNAME=ews
DB_PASSWORD=passwordKuatAnda        # dipakai juga oleh container mysql
DB_ROOT_PASSWORD=passwordRootMysql  # khusus container mysql

REVERB_HOST=reverb                  # backend menunjuk nama service
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_HOST=domain-anda.tld    # browser menunjuk domain publik
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
```

### 2. Build asset frontend (container Node sekali pakai)

```bash
docker run --rm -v "$PWD":/app -w /app node:20-alpine sh -c "npm ci && npm run build"
```

### 3. Jalankan

```bash
docker compose build app
docker compose up -d
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force   # hanya deploy pertama
docker compose exec app php artisan config:cache
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
docker compose restart reverb queue-alert queue-log scheduler
```

Aplikasi tersedia di port **80** host. Untuk HTTPS, letakkan reverse proxy (Nginx host / Caddy / Traefik / Cloudflare Tunnel) di depan port 80 — proxy tersebut juga harus meneruskan header `Upgrade`/`Connection` agar WebSocket `/app` berfungsi.

### Perintah operasional

```bash
docker compose ps                      # status semua service
docker compose logs -f queue-alert     # log worker alert
docker compose logs -f reverb          # log server WebSocket
docker compose exec app php artisan ews:monitor-failed   # cek job gagal
```

---

## Checklist Verifikasi (semua platform)

| # | Uji | Hasil yang benar |
|---|-----|------------------|
| 1 | Buka `/register` | 404 (registrasi ditutup) |
| 2 | Login akun nonaktif | Ditolak |
| 3 | Buka `/up` | HTTP 200 (health check) |
| 4 | Kirim rujukan uji **zona kuning** dari akun puskesmas | Alert muncul di dashboard IGD (seketika jika Reverb jalan; ≤10 detik via polling) |
| 5 | Konsol browser di dashboard IGD | Tidak ada error WebSocket berulang (jika Reverb dipakai) |
| 6 | `php artisan migrate:status` | Semua migration `Ran` |
| 7 | Tabel `ews_broadcast_failures` | Kosong setelah uji alert |

---

## Update Rilis

**cPanel / aaPanel:**

```bash
cd <folder-aplikasi>
php artisan down --retry=60
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart          # aaPanel: supervisorctl restart all
php artisan up
```

(cPanel: upload ulang `public/build` jika ada perubahan asset. aaPanel: `npm run build` di server.)

**Docker:**

```bash
git pull origin main
docker run --rm -v "$PWD":/app -w /app node:20-alpine sh -c "npm ci && npm run build"
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose exec app php artisan migrate --force
docker compose exec app php artisan config:cache
docker compose restart reverb queue-alert queue-log scheduler
```

> Selalu backup database sebelum `migrate --force`:
> `mysqldump -u USER -p ews_rsud_depatibahrin > backup-$(date +%F).sql`
> (Docker: `docker compose exec mysql mysqldump -u root -p ews_rsud_depatibahrin > backup.sql`)
