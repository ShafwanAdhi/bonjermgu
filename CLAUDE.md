# CLAUDE.md

Knowledge base proyek. Baca ini sebelum menulis kode apa pun.

---

## Apa Ini

Website operasional pembiayaan multifinance. Alurnya: Referral melakukan simulasi kredit bersama calon debitur, hasilnya dikirim ke Account Officer di luar sistem, AO membuat application dan mencatat perkembangannya sampai Go Live.

Sistem ini **pencatat**, bukan pengotomatis. Sebagian besar aktivitas nyata terjadi di luar sistem. Yang dicatat hanya status dan hasil.

Tiga role: Admin, Referral, Account Officer.

---

## Stack

```text
PHP 8.3 · Laravel 11 · PostgreSQL 16
Blade + Livewire 3 + Alpine.js + Tailwind
Pest · Nginx + PHP-FPM · VPS
```

---

## Peta Dokumentasi

Baca dokumen yang relevan dengan tugasnya. Jangan menebak.

| Sedang mengerjakan            | Baca                                                                   |
| ----------------------------- | ---------------------------------------------------------------------- |
| Apa pun, pertama kali         | `docs/business.md`, `docs/actors.md`                                   |
| Perhitungan simulasi          | `docs/credit-simulation.md` + `docs/credit-simulation-test-vectors.md` |
| Parameter, seeder konfigurasi | `docs/credit-simulation-configuration.md`                              |
| Form dan status dokumen       | `docs/document-requirement.md`                                         |
| Tahapan application           | `docs/application-tracking.md`                                         |
| Laporan Lending               | `docs/lending.md`                                                      |
| Halaman, form, navigasi       | `docs/pages.md`                                                        |
| Migrasi, model, relasi        | `docs/data-model.md`                                                   |
| Keputusan teknis, pola kode   | `docs/architecture.md`                                                 |
| Alur antar peran              | `docs/workflow.md`                                                     |
| Seeder master data            | `docs/master-data-extraction.md`                                       |

Perhitungan simulasi **selalu** butuh dua dokumen sekaligus: spesifikasinya dan nilai acuannya.

---

## Aturan yang Tidak Bisa Ditawar

Dua belas larangan berikut berasal dari cacat nyata pada draft perhitungan dan dari ketentuan bisnis yang mudah terlanggar tanpa disadari.

1. **Tidak ada perhitungan finansial di JavaScript.** Satu implementasi saja, di server.
2. **Tidak ada angka perhitungan sebagai konstanta di kode.** Semua dari database, termasuk yang terlihat permanen seperti batas usia unit 16 tahun.
3. **Rate tenor kosong berarti tenor tidak tersedia**, bukan bunga 0 persen.
4. **Tenor yang tidak menghasilkan pembiayaan bernilai 0 pada seluruh komponen**, termasuk refund.
5. **Harga PHPM dan Harga OTR adalah dua nilai berbeda.** PHPM mentah untuk deviasi, ACP, dan seluruh Mode B. OTR yang dibulatkan untuk Net DP, LTV, Sum Insured, dan fiducia.
6. **Pembulatan hanya di titik yang ditentukan.** Tidak membulatkan di tengah rantai supaya terlihat rapi.
7. **Otorisasi ditegakkan pada query**, bukan hanya di controller.
8. **Kode Aplikasi adalah identifier, bukan kredensial.** Tidak ada endpoint yang mengembalikan data hanya berbekal kode.
9. **Identitas debitur hanya nama, NIK, tanggal lahir dan baru diminta saat mengunduh simulasi.** Identitas tidak menjadi syarat atau input perhitungan.
10. **Tidak ada unggahan berkas.** Sistem hanya mencatat status dokumen.
11. **Kata sandi disimpan sebagai hash.** Tidak pernah dalam bentuk terbaca, termasuk di log.
12. **Status dokumen merujuk kode requirement**, bukan posisi slot.

---

## Perhitungan Simulasi

Bagian paling kritis dari sistem ini. Kesalahan seribu rupiah pada angsuran tidak akan terlihat saat pemeriksaan manual, tapi berulang pada setiap kontrak.

### Letak

```text
app/Domain/Simulation/
```

Kelas di dalamnya adalah PHP murni. Dilarang menyentuh Eloquent, Facade, `config()`, `auth()`, atau `now()`. Konfigurasi masuk sebagai objek, tahun berjalan masuk sebagai parameter.

Alasan `now()` dilarang: pengujian kelayakan unit akan gagal saat pergantian tahun.

### Aritmetika

Gunakan `float` PHP. Jangan BCMath, jangan tipe desimal.

Nilai acuan dihasilkan aritmetika IEEE 754 double. Tipe desimal menghasilkan digit akhir berbeda, dan pada nilai yang tepat di batas pembulatan, `ROUNDUP` bisa melompat seribu rupiah penuh.

### Gerbang Wajib

```bash
php artisan test --filter=Simulation
```

250 nilai acuan. Harus hijau sebelum perubahan apa pun pada modul simulasi diterima. Perbandingan persis, tanpa toleransi.

Kalau suite ini merah, jangan lanjut mengerjakan hal lain.

---

## Konvensi

| Konteks          | Bahasa                                 |
| ---------------- | -------------------------------------- |
| Tabel dan kolom  | Inggris, snake_case, jamak untuk tabel |
| Kelas dan method | Inggris, konvensi Laravel              |
| Label antarmuka  | Indonesia                              |
| Pesan validasi   | Indonesia                              |
| Komentar kode    | Inggris                                |
| Istilah domain   | Apa adanya                             |

Istilah yang **tidak diterjemahkan**: PHPM, ADDB, ADDM, ACP, TJH, LTV, Casco, TLO, Provisi, Fiducia.

### Nilai Uang

`bigint`, satuan rupiah penuh, tanpa sen. Rate sebagai `numeric(12,10)`, di-cast ke `float` saat masuk engine.

Dilarang menggunakan `float` atau `double` untuk kolom database.

---

## Istilah Domain

| Istilah        | Arti                                                                |
| -------------- | ------------------------------------------------------------------- |
| PHPM           | Master harga kendaraan, tersegmentasi penggunaan unit dan per tahun |
| Harga OTR      | Harga PHPM setelah dibulatkan ke bawah ke ratusan                   |
| ADDB           | Angsuran di belakang, dibayar akhir periode                         |
| ADDM           | Angsuran di muka, dibayar awal periode                              |
| LTV            | Nilai pembiayaan, harga dikurangi Net DP                            |
| Net DP         | Uang muka bersih, persentasenya bergantung produk dan profil        |
| Deviasi        | Selisih ketika harga input melebihi harga PHPM                      |
| Casco          | Pertanggungan utama kendaraan                                       |
| TLO            | Total Loss Only                                                     |
| Loading        | Penambahan premi berdasarkan usia kendaraan                         |
| ACP            | Asuransi jiwa debitur                                               |
| TJH            | Tanggung Jawab Hukum pihak ketiga                                   |
| Upping         | Penambahan di atas parameter dasar produk                           |
| Actual Lending | Application yang sudah Go Live                                      |
| Pipe Line      | Application yang belum Go Live                                      |
| Amount Finance | Nilai LTV pada application, diinput manual AO                       |

---

## Otorisasi

Ringkasnya:

| Role     | Simulasi              | Application | Dokumen & Tracking   | Lending | Konfigurasi |
| -------- | --------------------- | ----------- | -------------------- | ------- | ----------- |
| Admin    | Uji Konfigurasi saja  | Tidak       | Tidak                | Ya      | Ya          |
| Referral | Ya, dapat diunduh     | Tidak       | Lihat saja, miliknya | Tidak   | Tidak       |
| AO       | Ya, tanpa unduhan     | Miliknya    | Ubah, miliknya       | Tidak   | Tidak       |

Tiga peran menjalankan engine yang sama lewat tiga layar berbeda: `/simulation`
untuk Referral, `/simulation/officer` untuk AO, `/configuration/simulation`
untuk Admin. Hanya layar Referral yang menghasilkan PDF, sehingga hanya di sana
identitas debitur diminta.

Yang mengejutkan dan sering salah: **Admin tidak punya akses ke data application.** Global scope Admin mengembalikan himpunan kosong, bukan seluruh data. Satu-satunya pengecualian adalah query agregasi Lending.

Referral lolos global scope tapi ditolak Policy pada aksi `update`. Melihat boleh, mengubah tidak.

---

## Perintah

```bash
php artisan test                      # seluruh suite
php artisan test --filter=Simulation  # gerbang wajib
php artisan migrate:fresh --seed      # reset database lokal
./vendor/bin/pint                     # format kode
npm run dev                           # build aset saat pengembangan
```

---

## Selesai Berarti

Sebelum menyatakan sebuah tugas selesai:

- [ ] `php artisan test` hijau seluruhnya
- [ ] Untuk perubahan simulasi: 250 nilai acuan cocok persis
- [ ] Tidak ada angka perhitungan yang ditulis di kode
- [ ] Otorisasi diuji dari sisi yang seharusnya ditolak, bukan hanya yang diizinkan
- [ ] Constraint yang bisa dinyatakan di database sudah ada di migrasi
- [ ] `./vendor/bin/pint` sudah dijalankan

---

## Kalau Dokumentasi Tidak Menjawab

Setiap dokumen punya bagian **Open Items** menjelang akhir. Cek di sana dulu — kemungkinan besar pertanyaannya memang belum diputuskan.

Kalau memang belum ada jawabannya: **tanyakan, jangan tebak**. Terutama untuk apa pun yang menyangkut uang, otorisasi, atau data debitur.

Menebak rumus finansial adalah cara tercepat menghasilkan sistem yang terlihat benar dan diam-diam salah.

---

## Struktur Repo

```text
docs/                       dokumentasi bisnis dan teknis
app/
├── Domain/
│   ├── Simulation/         perhitungan, tanpa Eloquent
│   ├── Application/        resolver dokumen, transisi tracking
│   └── Lending/            query agregasi
├── Models/
├── Policies/
├── Livewire/
├── Repositories/           pemuat konfigurasi
└── Support/
database/
├── migrations/
└── seeders/
tests/
├── Unit/Simulation/        test vector
└── Feature/                otorisasi dan alur
```
