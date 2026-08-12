# Email Configuration

Dokumen ini menjelaskan konfigurasi email untuk fitur reset kata sandi dan email sistem lain.

## Prinsip

- Jangan simpan credential SMTP di repository.
- `.env.example` hanya template; secret sebenarnya berada di `.env` server.
- `APP_URL` wajib benar karena link reset password dibuat dari route aplikasi.
- User hanya bisa menerima link reset jika profil role-nya memiliki `email`.
- Testing memakai `MAIL_MAILER=array` dari `phpunit.xml`, jadi test tidak mengirim email sungguhan.
- SMTP diberi timeout agar request reset password tidak menggantung terlalu lama ketika mail server bermasalah.

## Local Development

Default lokal aman:

```env
APP_URL=http://127.0.0.1:8088

MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@bonjemgu.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Dengan konfigurasi ini, email ditulis ke `storage/logs/laravel.log`.

Jika memakai Mailpit atau MailHog lokal:

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_TIMEOUT=10
MAIL_FROM_ADDRESS="noreply@bonjemgu.com"
MAIL_FROM_NAME="${APP_NAME}"
```

## Production SMTP

Gunakan SMTP provider yang kredibel dan pastikan domain pengirim sudah diverifikasi.

```env
APP_URL=https://bonjemgu.com

MAIL_MAILER=smtp
MAIL_HOST=smtp.provider.com
MAIL_PORT=587
MAIL_USERNAME=isi_username_smtp
MAIL_PASSWORD=isi_password_smtp
MAIL_ENCRYPTION=tls
MAIL_TIMEOUT=10
MAIL_EHLO_DOMAIN=bonjemgu.com
MAIL_FROM_ADDRESS="noreply@bonjemgu.com"
MAIL_FROM_NAME="Kebon Jeruk Multiguna"
```

Untuk port `465`, biasanya gunakan:

```env
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
```

Untuk port `587`, biasanya gunakan:

```env
MAIL_PORT=587
MAIL_ENCRYPTION=tls
```

## Gmail Workspace

Jika memakai Gmail/Google Workspace, gunakan App Password atau SMTP credential yang memang diizinkan oleh admin Workspace.

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=akun@domain.com
MAIL_PASSWORD=app_password_bukan_password_login
MAIL_ENCRYPTION=tls
MAIL_TIMEOUT=10
MAIL_EHLO_DOMAIN=domain.com
MAIL_FROM_ADDRESS="akun@domain.com"
MAIL_FROM_NAME="Kebon Jeruk Multiguna"
```

## Setelah Mengubah `.env`

Di lokal:

```bash
php artisan config:clear
```

Di production:

```bash
php artisan config:cache
```

Jika queue worker berjalan, restart worker setelah deployment:

```bash
php artisan queue:restart
```

## Checklist Verifikasi

1. `APP_URL` mengarah ke domain yang benar.
2. `MAIL_MAILER=smtp` di production.
3. `MAIL_FROM_ADDRESS` memakai domain yang boleh mengirim email.
4. SPF, DKIM, dan DMARC domain pengirim sudah dipasang di DNS.
5. Profil user yang diuji memiliki alamat email.
6. Klik `Lupa kata sandi?`, isi Nama User, lalu cek inbox atau log mail.
7. Link reset membuka halaman `Atur Kata Sandi Baru`.
