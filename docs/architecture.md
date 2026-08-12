# Architecture

## 1. Overview

Dokumen ini memuat keputusan teknis beserta alasan dan konsekuensinya.

Setiap keputusan ditulis dengan format yang sama:

```text
Keputusan   apa yang dipilih
Alasan      mengapa dipilih
Konsekuensi apa yang harus diikuti implementasi
```

Dokumen ini mengikat. Implementasi yang menyimpang dari keputusan di sini harus mengubah dokumen ini terlebih dahulu.

---

## 2. Technology Stack

| Lapisan    | Pilihan                        |
| ---------- | ------------------------------ |
| Bahasa     | PHP 8.3                        |
| Framework  | Laravel 11                     |
| Database   | PostgreSQL 16                  |
| Frontend   | Blade + Livewire 3 + Alpine.js |
| Styling    | Tailwind CSS                   |
| Pengujian  | Pest                           |
| Web server | Nginx + PHP-FPM                |
| Deployment | VPS                            |

---

## 3. AD-01 — PostgreSQL sebagai Database

**Keputusan.** Menggunakan PostgreSQL, bukan MySQL.

**Alasan.**

- Tipe `numeric` menangani rate dan nilai uang tanpa perilaku pembulatan implisit.
- `CHECK constraint` didukung penuh, sehingga sebagian aturan bisnis dapat ditegakkan di level database.
- Partial unique index memudahkan penegakan keunikan bersyarat.
- Transaksi DDL dapat di-rollback, sehingga migrasi yang gagal tidak meninggalkan skema setengah jadi.

**Konsekuensi.**

- Migrasi boleh menggunakan fitur khas PostgreSQL. Portabilitas ke MySQL tidak menjadi syarat.
- Constraint bisnis yang dapat dinyatakan sebagai `CHECK` wajib ditulis sebagai `CHECK`, tidak cukup divalidasi di aplikasi.

---

## 4. AD-02 — Server-Rendered dengan Livewire

**Keputusan.** Menggunakan Blade dan Livewire. Tidak menggunakan SPA terpisah.

**Alasan.**

Alasan utamanya bukan preferensi, melainkan **perhitungan simulasi hanya boleh memiliki satu implementasi**.

Simulasi bersifat interaktif: Referral mengubah input dan hasil lima tenor berubah. Pada arsitektur SPA, godaan untuk menghitung di sisi klien sangat besar, dan hasilnya adalah dua implementasi rumus finansial yang harus dijaga tetap identik. Itu sumber kesalahan yang mahal.

Livewire membuat interaksi tetap terasa langsung sementara perhitungan tetap berjalan di server.

**Konsekuensi.**

- Tidak boleh ada perhitungan finansial dalam JavaScript. Tidak satu baris pun.
- JavaScript hanya untuk interaksi tampilan: dropdown bertingkat, toggle, format tampilan angka.
- Setiap perubahan input simulasi memicu request ke server.
- Laporan Lending Admin dapat memakai Livewire untuk filter asynchronous selama kalkulasi agregasi tetap berada di server dan query string tetap disinkronkan.

---

## 5. AD-03 — Domain Layer Terpisah untuk Perhitungan

**Keputusan.** Perhitungan simulasi berada di `app/Domain/Simulation`, berupa kelas PHP murni tanpa Eloquent.

```text
app/Domain/Simulation/
├── SimulationEngine.php
├── DanaTunaiCalculator.php
├── MobilBekasCalculator.php
├── Rate/FlatRateConverter.php
├── Insurance/InsuranceCalculator.php
├── Fee/FeeCalculator.php
├── Rounding.php
├── Input/SimulationInput.php
├── Input/SimulationConfig.php
└── Output/SimulationResult.php
```

**Alasan.**

- Perhitungan harus dapat diuji tanpa database. Suite pengujian menjalankan 250 nilai acuan, dan itu harus berjalan dalam hitungan detik.
- Memisahkan perhitungan dari akses data mencegah kebocoran query ke dalam loop tenor.

**Konsekuensi.**

- Kelas di dalam `Domain/Simulation` tidak boleh menyentuh Eloquent, Facade, `config()`, `auth()`, maupun `now()`.
- Konfigurasi dimuat lebih dulu oleh repository, lalu diserahkan ke engine sebagai objek `SimulationConfig`.
- Tahun berjalan diserahkan sebagai parameter, bukan dibaca dari sistem. Tanpa ini, pengujian kelayakan unit akan gagal saat pergantian tahun.

---

## 6. AD-04 — Aritmetika Float dengan Pembulatan Terkendali

**Keputusan.** Perhitungan antara menggunakan `float` PHP. Pembulatan diterapkan hanya pada titik yang ditentukan `credit-simulation.md`. Hasil akhir disimpan sebagai integer rupiah.

**Alasan.**

Nilai acuan pada `credit-simulation-test-vectors.md` dihasilkan aritmetika IEEE 754 double. Flat rate seperti `0,10284584815780895` merupakan hasil langsung dari operasi double.

Menggunakan BCMath atau tipe desimal akan menghasilkan digit akhir yang berbeda. Perbedaan itu biasanya tidak terlihat, namun pada nilai yang tepat berada di batas pembulatan, `ROUNDUP` dapat melompat satu satuan penuh — selisih seribu rupiah pada angsuran.

PHP `float` adalah double, sehingga hasilnya identik dengan acuan.

**Konsekuensi.**

- Pembulatan hanya di titik yang ditentukan. Dilarang membulatkan "supaya rapi" di tengah rantai perhitungan.
- Fungsi pembulatan terpusat pada satu kelas `Rounding`, tidak ditulis ulang di tempat lain.
- `ROUNDUP` menggunakan epsilon untuk mencegah nilai yang secara matematis tepat kelipatan justru naik satu satuan akibat galat representasi.

```php
Rounding::down($value, 100);   // ke ratusan
Rounding::up($value, 1000);    // ke ribuan
```

- Nilai uang yang disimpan ke database sudah berupa integer rupiah. Tidak ada pecahan rupiah yang disimpan.

---

## 7. AD-05 — Nilai Uang sebagai BIGINT Rupiah

**Keputusan.** Kolom uang bertipe `bigint`, satuan rupiah penuh. Tidak ada sen. Rate disimpan sebagai `numeric(12,10)`.

**Alasan.**

- Rupiah tidak menggunakan pecahan dalam praktik pembiayaan ini. Seluruh keluaran sudah dibulatkan ke ratusan atau ribuan.
- Nilai terbesar yang mungkin muncul, Amount Finance kumulatif seluruh application, jauh di bawah batas `bigint`.
- `numeric(12,10)` menampung rate seperti `0,1447` maupun `0,144699` tanpa kehilangan digit.

**Konsekuensi.**

- Dilarang menggunakan `float` atau `double` untuk kolom database.
- Rate dibaca dari database lalu di-cast ke `float` untuk masuk ke engine.

---

## 8. AD-06 — Autentikasi Tunggal, Profil Terpisah

**Keputusan.** Satu tabel `users` untuk kredensial. Atribut khas role berada di tabel profil terpisah dengan relasi satu ke satu.

```text
users              kredensial dan role
├── admins         profil Admin
├── referrals      profil Referral, termasuk empat atribut identitas
└── account_officers  profil AO
```

**Alasan.**

Ketentuan pada `actors.md` adalah atribut antar role tidak boleh bercampur dalam satu tabel. Ketentuan itu terpenuhi: kolom kategori, sub-kategori, instansi, dan cabang hanya ada di `referrals`.

Sementara itu, memecah kredensial menjadi tiga tabel akan memaksa penggunaan tiga guard Laravel. Konsekuensinya tiga alur login, tiga konfigurasi session, dan pemeriksaan otorisasi yang bercabang di banyak tempat. Biayanya tinggi tanpa manfaat yang sepadan.

**Konsekuensi.**

- `users` hanya berisi: `username`, `password`, `role`, `is_active`.
- Tidak ada kolom di `users` yang hanya berlaku bagi sebagian role.
- Setiap baris `users` wajib memiliki tepat satu baris profil sesuai `role`.

---

## 9. AD-07 — Otorisasi pada Query, Bukan pada Controller

**Keputusan.** Kepemilikan application ditegakkan melalui global scope pada model, diperkuat Policy.

**Alasan.**

`actors.md` menyatakan pembatasan harus berada di lapisan data. Pemeriksaan di controller mudah terlewat: satu endpoint baru yang lupa memanggil `authorize()` langsung membuka seluruh data.

Global scope menutup celah itu dari arah sebaliknya. Query yang lupa difilter tetap tidak mengembalikan data milik orang lain.

**Konsekuensi.**

```php
// Application model
protected static function booted(): void
{
    static::addGlobalScope('visibility', function (Builder $q) {
        $user = auth()->user();
        if (!$user) { $q->whereRaw('1 = 0'); return; }

        match ($user->role) {
            Role::AccountOfficer => $q->where('account_officer_id', $user->accountOfficer->id),
            Role::Referral       => $q->where('referral_id', $user->referral->id),
            Role::Admin          => $q->whereRaw('1 = 0'),
        };
    });
}
```

- Admin tidak memiliki akses ke application. Scope-nya mengembalikan himpunan kosong, bukan seluruh data.
- Kemampuan mengubah dipisahkan dari kemampuan melihat melalui Policy. Referral lolos scope namun ditolak Policy pada aksi `update`.
- Dilarang menggunakan `withoutGlobalScope` di luar konteks laporan Lending.

---

## 10. AD-08 — Kode Aplikasi Bukan Kredensial

**Keputusan.** Kode Aplikasi berupa 6 karakter acak base62, unik, dengan index unik di database.

**Alasan.**

Draft menyediakan penelusuran hanya berbekal kode. Ketentuan pada `business.md` meniadakannya.

**Konsekuensi.**

- Tidak ada route yang mengembalikan data application tanpa autentikasi.
- Route menggunakan kode sebagai parameter, namun tetap melewati scope dan Policy.
- Pembangkitan kode menggunakan `random_bytes`, bukan `rand` atau `uniqid`.
- Tabrakan ditangani dengan mengulang pembangkitan, dijaga oleh unique constraint.

---

## 11. AD-09 — Katalog di Database, Aturan di Kode

**Keputusan.** Document Requirement disimpan sebagai tabel. Aturan keberlakuannya berupa kelas resolver.

**Alasan.**

Katalog adalah data: 26 baris dengan kode, nama, dan urutan. Wajar berada di database.

Keberlakuannya bukan data, melainkan percabangan bersyarat atas dua field. Memaksanya menjadi tabel aturan menghasilkan struktur yang sulit dibaca dan sulit diuji, sementara aturannya sendiri jarang berubah.

**Konsekuensi.**

```php
DocumentRequirementResolver::resolve(
    DebtorType $type,
    SpouseIncomeType $spouse,
): array   // mengembalikan daftar kode requirement
```

- Resolver berupa fungsi murni. Input sama menghasilkan output sama.
- Perubahan Type Debitur memicu rekonsiliasi: requirement yang tetap berlaku mempertahankan status, yang baru dibuat dengan status Belum, yang gugur dihapus.
- Rekonsiliasi berjalan dalam satu transaksi.

---

## 12. AD-10 — Konfigurasi di Database

**Keputusan.** Seluruh parameter perhitungan berada di tabel yang dapat diubah Admin. Tidak ada nilai perhitungan di `config/` maupun `.env`.

**Alasan.**

`credit-simulation-configuration.md` menyatakan konfigurasi bersifat data. Nilai seperti biaya fiducia, garansi mesin, dan batas usia unit berubah mengikuti kebijakan perusahaan, bukan mengikuti rilis aplikasi.

**Konsekuensi.**

- Dilarang menuliskan angka perhitungan sebagai konstanta di kode. Termasuk yang terlihat permanen seperti batas usia 16 tahun.
- Konfigurasi dimuat sekali per simulasi, bukan per tenor. Lima tenor berbagi satu objek konfigurasi.
- Nilai awal diisi melalui seeder, bukan migrasi.

---

## 13. AD-11 — Lending Dihitung, Tidak Disimpan

**Keputusan.** Angka Lending dihitung melalui query agregasi saat laporan dibuka. Tidak ada tabel ringkasan.

**Alasan.**

- Volume data kecil. Agregasi atas puluhan ribu baris application bukan beban bagi PostgreSQL.
- Nilai tersimpan berisiko basi. Application dapat kembali dari Actual ke Pipe Line ketika tahap 11 dibatalkan, dan setiap mekanisme sinkronisasi menambah cara baru untuk salah.

**Konsekuensi.**

- Query Lending menggunakan `withoutGlobalScope`, karena Admin memang harus melihat seluruh data.
- Invariant wajib diuji: total pada pengelompokan per AO harus sama dengan total pada pengelompokan per Referral.
- UI Lending Admin memakai component Livewire untuk filter bulan Go Live, produk pembiayaan, dan kategori Referral tanpa full page reload atau tombol Terapkan.
- Bila kelak volume menjadi masalah, solusinya materialized view, bukan kolom ringkasan yang diperbarui manual.

---

## 14. AD-12 — Tanggal Go Live melalui Observer

**Keputusan.** Pengisian dan pengosongan Tanggal Go Live ditangani observer pada perubahan status tahap 11.

**Alasan.**

Tanggal Go Live adalah turunan dari status, bukan input. Menyerahkan pengisiannya ke controller berarti setiap jalur yang mengubah status harus mengingat untuk mengisinya.

**Konsekuensi.**

- Tanggal Go Live tidak pernah diisi langsung oleh controller maupun form.
- Zona waktu `Asia/Jakarta`. Tanggal Go Live bertipe `date`, bukan `timestamp`.
- Membatalkan tahap 11 mengosongkan tanggal. Menandainya kembali mengisi tanggal saat itu, bukan tanggal sebelumnya.

---

## 15. AD-13 — Test Vector sebagai Gerbang Wajib

**Keputusan.** Nilai pada `credit-simulation-test-vectors.md` diterjemahkan menjadi dataset pengujian. Suite ini wajib hijau sebelum perubahan apa pun pada modul simulasi diterima.

**Alasan.**

Perhitungan adalah inti sistem. Kesalahan seribu rupiah pada angsuran tidak akan terlihat saat pemeriksaan manual, namun berulang pada setiap kontrak.

**Konsekuensi.**

```text
tests/Unit/Simulation/
├── DanaTunaiTest.php
├── MobilBekasTest.php
├── FlatRateConverterTest.php
├── RoundingTest.php
└── Fixtures/test_vectors.php
```

- Perbandingan menggunakan kesamaan persis untuk seluruh nilai berpembulatan. Tidak ada toleransi.
- Nilai flat rate dibandingkan hingga 15 digit signifikan.
- Menambah skenario pada dokumen test vector berarti menambah dataset pengujian pada saat yang sama.

---

## 16. AD-14 — Tanpa Penyimpanan Berkas

**Keputusan.** Sistem tidak memiliki disk penyimpanan untuk dokumen debitur.

**Alasan.**

`document-requirement.md` menetapkan sistem hanya mencatat status. Ketiadaan berkas menghilangkan seluruh kelas risiko: unggahan berbahaya, kebocoran berkas, dan kewajiban retensi.

**Konsekuensi.**

- Tidak ada route unggah berkas.
- Tidak ada kolom path, nama berkas, maupun tautan berkas.
- Bila kelak dibutuhkan, itu perubahan scope yang harus melewati `business.md`.

---

## 17. AD-15 — Kata Sandi Akun

**Keputusan.** Referral menentukan kata sandinya sendiri saat pendaftaran. Kata sandi awal AO dibuat acak oleh sistem. Semua kata sandi disimpan sebagai hash bcrypt dan tidak berasal dari NIK atau nomor identitas pribadi.

**Alasan.**

Akun tidak lagi membutuhkan NIK. Referral tidak menunggu password dari sistem, sementara Admin tetap dapat membuat akun AO dengan password awal yang tampil sekali saat akun dibuat.

**Konsekuensi.**

Mitigasi berikut wajib ada:

- Kata sandi disimpan sebagai hash. Tidak pernah dalam bentuk terbaca, termasuk di log.
- Pembatasan percobaan login: lima kali per menit per kombinasi username dan alamat IP.
- HTTPS dipaksakan. Cookie session `secure` dan `http_only`.
- Session diputar ulang setelah login.
- Percobaan login gagal dicatat beserta alamat IP.

Nilai `0000` diperlakukan sebagai parameter konfigurasi, sehingga polanya dapat diubah tanpa mengubah kode.

---

## 18. AD-16 — Penamaan

**Keputusan.** Identifier kode berbahasa Inggris. Teks antarmuka berbahasa Indonesia. Istilah domain dipertahankan apa adanya.

**Alasan.**

Laravel dan ekosistemnya berbahasa Inggris. Mencampur bahasa pada nama tabel dan kelas menghasilkan konstruksi seperti `getPencairanMaksimal` yang tidak nyaman dibaca dari sisi mana pun.

Sebaliknya, istilah domain seperti PHPM, ADDB, ADDM, ACP, dan TJH tidak memiliki padanan dan tidak boleh diterjemahkan.

**Konsekuensi.**

| Konteks               | Bahasa                                       |
| --------------------- | -------------------------------------------- |
| Nama tabel dan kolom  | Inggris, snake_case                          |
| Nama kelas dan method | Inggris, sesuai konvensi Laravel             |
| Label antarmuka       | Indonesia                                    |
| Pesan validasi        | Indonesia                                    |
| Istilah domain        | Apa adanya: `phpm_price`, `acp_rate`, `addb` |
| Komentar kode         | Inggris                                      |

---

## 19. AD-17 — Audit Perubahan Konfigurasi Admin

**Keputusan.** Setiap create, update, dan delete pada konfigurasi simulasi serta master data Admin dicatat pada `admin_change_logs`.

**Alasan.**

- Halaman konfigurasi wajib menunjukkan kapan nilai terakhir berubah dan siapa pelakunya.
- Timestamp pada record utama tidak cukup untuk record yang telah dihapus.
- Snapshot sebelum dan sesudah perubahan diperlukan untuk menelusuri perubahan parameter yang memengaruhi simulasi berikutnya.

**Konsekuensi.**

- Log menyimpan actor, waktu, tabel dan ID subject, action, serta nilai sebelum/sesudah.
- Log menyimpan `audit_module` agar ringkasan perubahan terakhir terpisah per module, terutama ketika beberapa module berbagi tabel yang sama.
- Audit ditulis dalam transaksi yang sama dengan perubahan. Perubahan yang gagal validasi dan di-rollback tidak meninggalkan audit palsu.
- Kode pembaca audit harus aman ketika kolom `audit_module` belum tersedia, tetapi setelah migration berjalan pencarian perubahan terakhir wajib dibatasi ke module terkait.
- Seeder tidak menghasilkan audit karena tidak dijalankan sebagai Admin terautentikasi.
- Penghapusan log audit tidak tersedia melalui antarmuka CRUD.

---

## 20. Struktur Aplikasi

```text
app/
├── Domain/
│   ├── Simulation/          perhitungan, tanpa Eloquent
│   ├── Application/         resolver dokumen, transisi tracking
│   └── Lending/             query agregasi
├── Models/
├── Policies/
├── Livewire/
│   ├── Simulation/
│   ├── Application/
│   └── Lending/
├── Repositories/            pemuat konfigurasi
└── Support/

database/
├── migrations/
└── seeders/
    ├── ProductSeeder.php
    ├── InsuranceSeeder.php
    ├── DocumentRequirementSeeder.php
    └── TrackingStageSeeder.php

tests/
├── Unit/Simulation/         test vector
└── Feature/                 otorisasi, alur
```

---

## 21. Deployment

### Lingkungan

| Komponen   | Versi                 |
| ---------- | --------------------- |
| PHP        | 8.3, FPM              |
| PostgreSQL | 16                    |
| Nginx      | stabil                |
| Node       | hanya saat build aset |

### Ketentuan

- `APP_DEBUG=false` di produksi. Tanpa pengecualian.
- `APP_TIMEZONE=Asia/Jakarta`.
- HTTPS dipaksakan di level Nginx.
- Migrasi dijalankan dalam mode maintenance.
- Basis data dicadangkan harian dan pemulihannya diuji secara berkala. Cadangan yang belum pernah diuji bukan cadangan.
- Queue belum diperlukan. Tidak ada proses latar pada scope saat ini.

---

## 22. Yang Tidak Boleh Dilakukan

Daftar ini berasal dari cacat yang ditemukan pada draft perhitungan dan dari ketentuan bisnis yang mudah terlanggar tanpa disadari.

| No  | Larangan                                                                                  |
| --- | ----------------------------------------------------------------------------------------- |
| 1   | Menghitung nilai finansial di JavaScript                                                  |
| 2   | Menuliskan angka perhitungan sebagai konstanta di kode                                    |
| 3   | Memperlakukan rate tenor kosong sebagai 0 persen                                          |
| 4   | Membiarkan komponen tetap terhitung ketika tenor dinyatakan tidak menghasilkan pembiayaan |
| 5   | Mencampur Harga PHPM dan Harga OTR                                                        |
| 6   | Membulatkan di luar titik yang ditentukan                                                 |
| 7   | Mengandalkan pemeriksaan otorisasi di controller saja                                     |
| 8   | Mengembalikan data application hanya berbekal Kode Aplikasi                               |
| 9   | Menyimpan data debitur di luar nama, NIK, dan tanggal lahir                               |
| 10  | Menambahkan unggahan berkas                                                               |
| 11  | Menyimpan kata sandi dalam bentuk terbaca                                                 |
| 12  | Menyimpan status dokumen sebagai slot posisional                                          |

---

## 23. Open Items

| No  | Item                    | Keterangan                                                                                                      |
| --- | ----------------------- | --------------------------------------------------------------------------------------------------------------- |
| 1   | Riwayat perubahan       | Tabel log perubahan status disarankan sebagai cadangan audit, namun belum ditetapkan wajib.                     |
| 2   | Produk pada application | Application belum memiliki field produk pembiayaan pada dokumentasi bisnis, padahal dibutuhkan untuk pelaporan. |
| 3   | Multi Admin             | Jumlah akun Admin dan cara pembuatannya belum ditetapkan.                                                       |

---

## 24. Related Documentation

| Document                             | Purpose                              |
| ------------------------------------ | ------------------------------------ |
| `data-model.md`                      | Entitas, relasi, dan constraint      |
| `business.md`                        | Business context dan system scope    |
| `actors.md`                          | Actors, responsibilities, dan access |
| `credit-simulation.md`               | Perhitungan simulasi                 |
| `credit-simulation-configuration.md` | Parameter perhitungan                |
| `credit-simulation-test-vectors.md`  | Nilai acuan pengujian                |
