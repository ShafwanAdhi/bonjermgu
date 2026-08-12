<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Atur Ulang Kata Sandi</title>
</head>
<body style="margin:0;background:#f6f4ef;color:#1f2430;font-family:Inter,Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f4ef;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border:1px solid #e6e1d8;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="padding:28px 28px 0;">
                            <div style="font-size:13px;font-weight:600;letter-spacing:.02em;color:#516056;">Kebon Jeruk Multiguna</div>
                            <h1 style="margin:18px 0 10px;font-size:28px;line-height:1.2;font-weight:600;color:#1f2430;">Atur ulang kata sandi Anda</h1>
                            <p style="margin:0;color:#5d6572;font-size:15px;line-height:1.7;">
                                Halo {{ $user->displayName() }}, kami menerima permintaan untuk membuat kata sandi baru pada akun Anda.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:26px 28px 8px;">
                            <a href="{{ $resetUrl }}"
                               style="display:inline-block;border-radius:10px;background:#173a2f;color:#ffffff;text-decoration:none;font-size:14px;font-weight:600;padding:14px 20px;">
                                Atur Kata Sandi Baru
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 28px 28px;">
                            <p style="margin:0 0 14px;color:#5d6572;font-size:13px;line-height:1.7;">
                                Link ini berlaku selama {{ $expiresInMinutes }} menit. Abaikan email ini jika Anda tidak meminta pengaturan ulang kata sandi.
                            </p>
                            <div style="border-top:1px solid #e6e1d8;padding-top:16px;color:#8a919c;font-size:12px;line-height:1.6;">
                                Jika tombol tidak bisa dibuka, salin link berikut ke browser:<br>
                                <span style="word-break:break-all;color:#5d6572;">{{ $resetUrl }}</span>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
