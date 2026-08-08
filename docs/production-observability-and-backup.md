# Production Observability & Offsite Backup

Dokumen ini melengkapi `docs/vps-runbook.md` untuk tiga hal production yang belum sepenuhnya otomatis:

1. monitoring uptime eksternal,
2. backup offsite,
3. error tracking dengan Sentry.

Gunakan dokumen ini sebagai checklist implementasi. Jangan commit secret seperti DSN private, access key, secret key, password database, atau isi `.env` production.

## 1. Monitoring Uptime Eksternal

Rekomendasi awal: UptimeRobot, Better Stack, atau HetrixTools. Untuk project ini, UptimeRobot sudah cukup karena sederhana dan punya HTTP monitoring.

Monitor yang disarankan:

| Nama Monitor | Type | Target | Tujuan |
| --- | --- | --- | --- |
| Bonjermgu Web | HTTP(s) | `https://bonjermgu.com/login` | Memastikan website dan Laravel route utama hidup |
| Bonjermgu Health | HTTP(s) | `https://bonjermgu.com/up` | Memastikan health route Laravel hidup |
| Bonjermgu SSL | SSL/Domain jika tersedia | `bonjermgu.com` | Memantau masa berlaku SSL/domain |

Setting awal:

- interval: 5 menit,
- timeout: 30 detik,
- alert contact: email utama,
- optional: Telegram/WhatsApp/Slack jika tersedia,
- alert jika status bukan `2xx` atau `3xx`.

Catatan:

- Jangan monitor `/dashboard` karena butuh login.
- `/login` cocok untuk cek real Laravel + session middleware.
- `/up` cocok untuk health ringan.

Checklist setelah dibuat:

```bash
curl -I https://bonjermgu.com/login
curl -I https://bonjermgu.com/up
```

Keduanya harus mengembalikan status sehat, idealnya `200 OK`.

## 2. Backup Offsite

Backup lokal di VPS sudah ada, tetapi backup lokal belum cukup. Jika VPS rusak total, backup ikut berisiko hilang. Backup offsite menyimpan salinan ke storage eksternal.

Rekomendasi storage:

1. Cloudflare R2,
2. AWS S3,
3. Backblaze B2,
4. Google Drive via rclone.

Untuk biaya rendah, Cloudflare R2 atau Backblaze B2 biasanya cocok.

### 2.1 Install rclone di VPS

Login ke VPS terlebih dahulu:

```powershell
ssh -i "$env:USERPROFILE\.ssh\bonjermgu-prod" -o IdentitiesOnly=yes deploy@103.89.4.104
```

Install rclone:

```bash
sudo apt update
sudo apt install -y rclone
rclone version
```

### 2.2 Buat remote rclone

Jalankan:

```bash
rclone config
```

Contoh nama remote:

```text
bonjermgu-offsite
```

Jika memakai Cloudflare R2:

- storage type: S3 compatible,
- provider: Cloudflare R2,
- access key: ambil dari dashboard R2,
- secret key: ambil dari dashboard R2,
- endpoint: `https://<account_id>.r2.cloudflarestorage.com`,
- ACL: private.

Pastikan config rclone privat:

```bash
chmod 700 ~/.config/rclone
chmod 600 ~/.config/rclone/rclone.conf
```

### 2.3 Test upload backup offsite

```bash
LATEST_BACKUP=$(ls -t /var/backups/bonjermgu/postgres/bonjermgu_prod_*.dump | head -n 1)
echo "$LATEST_BACKUP"

rclone copy "$LATEST_BACKUP" bonjermgu-offsite:bonjermgu/postgres --progress
rclone ls bonjermgu-offsite:bonjermgu/postgres
```

### 2.4 Tambahkan upload offsite ke script backup

Edit script:

```bash
nano ~/scripts/backup-bonjermgu-postgres.sh
```

Tambahkan setelah backup lokal berhasil:

```bash
if command -v rclone >/dev/null 2>&1; then
    echo "[$(date --iso-8601=seconds)] Uploading backup to offsite storage"
    rclone copy "$BACKUP_FILE" bonjermgu-offsite:bonjermgu/postgres --transfers 2 --checkers 4
    echo "[$(date --iso-8601=seconds)] Offsite upload completed"
fi
```

Jalankan test:

```bash
~/scripts/backup-bonjermgu-postgres.sh
rclone ls bonjermgu-offsite:bonjermgu/postgres | tail
```

### 2.5 Retention offsite

Jangan terlalu agresif menghapus backup offsite. Rekomendasi awal:

- harian: 14 hari,
- mingguan: 4 minggu,
- bulanan: 3 bulan.

Untuk awal, minimal simpan semua backup offsite selama 30 hari.

## 3. Error Tracking dengan Sentry

Sentry menangkap error Laravel production supaya tidak hanya tersembunyi di `storage/logs/laravel.log`.

### 3.1 Buat project Sentry

Di dashboard Sentry:

- platform: Laravel,
- project name: `bonjermgu-production`,
- environment: `production`,
- copy DSN.

### 3.2 Install package Sentry Laravel

Di lokal:

```powershell
cd D:\internship\web-mtf
composer require sentry/sentry-laravel
```

Publish config:

```powershell
php artisan sentry:publish --dsn=
```

Jangan isi DSN production di repo. DSN production dimasukkan di `.env` server.

### 3.3 Laravel 11 exception integration

Pastikan `bootstrap/app.php` mengirim exception ke Sentry.

Contoh pola yang biasanya diperlukan:

```php
->withExceptions(function (Exceptions $exceptions) {
    \Sentry\Laravel\Integration::handles($exceptions);
})
```

Jika package Sentry belum terinstall, jangan menambahkan kode ini dulu karena class `Sentry\Laravel\Integration` belum tersedia.

### 3.4 Set env Sentry di VPS

Login ke VPS terlebih dahulu:

```powershell
ssh -i "$env:USERPROFILE\.ssh\bonjermgu-prod" -o IdentitiesOnly=yes deploy@103.89.4.104
```

Edit `.env` production:

```bash
cd /var/www/bonjermgu/bonjermgu
nano .env
```

Isi:

```dotenv
SENTRY_LARAVEL_DSN=https://PUBLIC_KEY@ORG.ingest.sentry.io/PROJECT_ID
SENTRY_ENVIRONMENT=production
SENTRY_RELEASE=bonjermgu-production
SENTRY_TRACES_SAMPLE_RATE=0.0
```

Untuk awal, `SENTRY_TRACES_SAMPLE_RATE=0.0` dulu agar hanya error tracking, bukan performance tracing. Tracing bisa dinaikkan nanti jika dibutuhkan.

Rebuild cache dan reload PHP-FPM:

```bash
php artisan optimize:clear
php artisan config:cache
sudo systemctl reload php8.3-fpm
```

### 3.5 Test Sentry

Setelah package terpasang dan DSN production aktif:

```bash
php artisan sentry:test
```

Cek dashboard Sentry. Harus muncul test event.

## Urutan Eksekusi yang Disarankan

1. Buat monitor uptime eksternal dulu.
2. Setup rclone dan backup offsite.
3. Setup Sentry.
4. Jalankan deploy production.
5. Test:
   - Uptime monitor hijau,
   - backup muncul di offsite storage,
   - Sentry menerima test event.

## Referensi

- UptimeRobot monitor types: https://help.uptimerobot.com/en/articles/11358441-understanding-uptimerobot-monitor-types-a-guide-to-essential-services
- UptimeRobot first monitor: https://help.uptimerobot.com/en/articles/11358364-how-to-create-your-first-monitor-on-uptimerobot-quick-setup-guide
- Cloudflare R2 rclone: https://developers.cloudflare.com/r2/examples/rclone/
- Sentry Laravel package: https://packagist.org/packages/sentry/sentry-laravel
