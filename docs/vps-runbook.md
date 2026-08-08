# VPS Runbook Bonjermgu

Dokumen ini berisi command operasional untuk VPS production Bonjermgu.

Server:

- IP: `103.89.4.104`
- Domain: `bonjermgu.com`
- OS: Ubuntu 24.04 LTS
- App path: `/var/www/bonjermgu/bonjermgu`
- User SSH: `deploy`
- Stack: Nginx, PHP 8.3-FPM, PostgreSQL 16, Laravel 11

## Login ke VPS

Selalu login ke VPS terlebih dahulu sebelum menjalankan command server.

```powershell
ssh -i "$env:USERPROFILE\.ssh\bonjermgu-prod" -o IdentitiesOnly=yes deploy@103.89.4.104
```

Prompt server harus terlihat seperti:

```bash
deploy@bonjermguprod:~$
```

## Deploy Update

Di laptop lokal:

```powershell
cd D:\internship\web-mtf

git status
git add .
git commit -m "pesan perubahan"
git push origin main
```

Login ke VPS, lalu:

```bash
~/scripts/deploy-bonjermgu.sh
```

Script deploy menjalankan:

- maintenance mode
- `git pull origin main`
- `composer install --no-dev --optimize-autoloader`
- `npm ci`
- `npm run build`
- `php artisan migrate --force`
- Laravel cache rebuild
- permission fix untuk `storage` dan `bootstrap/cache`
- reload PHP-FPM
- smoke test `/login`

## Health Check

Login ke VPS, lalu:

```bash
~/scripts/health-bonjermgu.sh
```

Cek manual tambahan:

```bash
curl -I https://bonjermgu.com/login
sudo ufw status verbose
sudo fail2ban-client status
free -h
df -h
```

Kondisi sehat:

- `/login` mengembalikan `200 OK`
- UFW `active`
- Nginx, PHP-FPM, PostgreSQL, Fail2ban `active`
- disk root tidak mendekati penuh
- swap tidak terpakai terus-menerus dalam jumlah besar

## Backup Database

Backup otomatis berjalan setiap hari jam `02:15 WIB`.

Cek cron:

```bash
crontab -l
```

Cek backup terbaru:

```bash
ls -lh /var/backups/bonjermgu/postgres | tail
```

Jalankan backup manual:

```bash
~/scripts/backup-bonjermgu-postgres.sh
```

File backup harus memiliki permission privat:

```bash
chmod 600 /var/backups/bonjermgu/postgres/*.dump
chmod 700 /var/backups/bonjermgu/postgres
```

## Restore Drill

Gunakan database sementara. Jangan restore langsung ke `bonjermgu_prod`.

```bash
LATEST_BACKUP=$(ls -t /var/backups/bonjermgu/postgres/bonjermgu_prod_*.dump | head -n 1)
echo "$LATEST_BACKUP"

sudo -u postgres dropdb --if-exists bonjermgu_restore_test
sudo -u postgres createdb -O bonjermgu_user bonjermgu_restore_test

pg_restore -h 127.0.0.1 -U bonjermgu_user -d bonjermgu_restore_test "$LATEST_BACKUP"

psql -h 127.0.0.1 -U bonjermgu_user -d bonjermgu_restore_test -c "SELECT COUNT(*) AS users FROM users;"
psql -h 127.0.0.1 -U bonjermgu_user -d bonjermgu_restore_test -c "SELECT COUNT(*) AS vehicle_prices FROM vehicle_prices;"
psql -h 127.0.0.1 -U bonjermgu_user -d bonjermgu_restore_test -c "SELECT COUNT(*) AS migrations FROM migrations;"

sudo -u postgres dropdb bonjermgu_restore_test
```

## Service Commands

```bash
sudo systemctl status nginx --no-pager
sudo systemctl status php8.3-fpm --no-pager
sudo systemctl status postgresql --no-pager
sudo systemctl status fail2ban --no-pager
```

Reload setelah perubahan config:

```bash
sudo nginx -t
sudo systemctl reload nginx

sudo php-fpm8.3 -t
sudo systemctl reload php8.3-fpm
```

Restart PostgreSQL hanya saat diperlukan, misalnya perubahan `shared_preload_libraries`:

```bash
sudo systemctl restart postgresql
```

## Log

Laravel:

```bash
ls -lh /var/www/bonjermgu/bonjermgu/storage/logs
tail -n 100 /var/www/bonjermgu/bonjermgu/storage/logs/laravel.log
```

Nginx:

```bash
sudo tail -n 100 /var/log/nginx/access.log
sudo tail -n 100 /var/log/nginx/error.log
```

PHP-FPM slowlog:

```bash
sudo tail -n 100 /var/log/php8.3-fpm.slow.log
```

System journal:

```bash
journalctl -p warning -n 100 --no-pager
```

## PostgreSQL Observability

Top query berdasarkan total waktu:

```bash
sudo -u postgres psql -d bonjermgu_prod -c "
SELECT
    calls,
    round(total_exec_time::numeric, 2) AS total_ms,
    round(mean_exec_time::numeric, 2) AS mean_ms,
    rows,
    left(query, 120) AS query
FROM pg_stat_statements
ORDER BY total_exec_time DESC
LIMIT 10;
"
```

Koneksi aktif:

```bash
sudo -u postgres psql -d bonjermgu_prod -c "
SELECT state, count(*)
FROM pg_stat_activity
WHERE datname = 'bonjermgu_prod'
GROUP BY state
ORDER BY state;
"
```

## Security Checklist

```bash
sudo sshd -T | grep -E 'permitrootlogin|passwordauthentication|pubkeyauthentication|maxauthtries|x11forwarding|allowusers'
sudo ufw status verbose
sudo fail2ban-client status
```

Kondisi ideal:

- `permitrootlogin no`
- `passwordauthentication no`
- `pubkeyauthentication yes`
- `maxauthtries 3`
- `x11forwarding no`
- `allowusers deploy`
- UFW `active`
- Fail2ban jail `sshd`, `nginx-http-auth`, `nginx-botsearch` aktif

## Disk Usage

```bash
df -h
du -sh /var/www/bonjermgu/bonjermgu /var/backups/bonjermgu /var/log
sudo ncdu /
```

Keluar dari `ncdu` dengan:

```text
q
```

## Monitoring Tools

```bash
btop
iostat -xz 1 2
sar -u 1 3
```

Keluar dari `btop` dengan:

```text
q
```

## Setelah Reboot

```bash
uptime
~/scripts/health-bonjermgu.sh
curl -I https://bonjermgu.com/login
sudo ufw status verbose
```

Semua service utama harus `active`, website harus `200 OK`, dan UFW harus `active`.

## Catatan Penting

- Jangan menjalankan command server dari PowerShell lokal setelah keluar dari SSH.
- Jangan menjalankan `npm audit fix --force` langsung di production.
- Karena OPcache `validate_timestamps=0`, setiap deploy wajib reload PHP-FPM.
- Jangan kirim password database, password admin, private key, atau isi `.env` ke chat.
- Backup yang belum pernah diuji restore belum dianggap backup yang utuh.
