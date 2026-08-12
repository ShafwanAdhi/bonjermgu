# Pages

## 1. Overview

Dokumen ini mendefinisikan halaman yang tersedia, siapa yang dapat mengaksesnya, dan apa isinya.

Dokumen ini menetapkan **isi dan perilaku**, bukan tata letak visual. Susunan kolom, warna, dan jarak diserahkan pada implementasi selama isi dan ketentuannya terpenuhi.

Prinsip yang berlaku pada seluruh halaman:

- Perhitungan finansial tidak pernah terjadi di sisi klien.
- Setiap halaman selain kelompok Publik memerlukan autentikasi.
- Halaman tidak boleh menampilkan data yang tidak lolos pembatasan pada `actors.md`.

---

## 2. Peta Halaman

### Publik

| Route       | Halaman             | Akses |
| ----------- | ------------------- | ----- |
| `/`         | Landing             | Bebas |
| `/register` | Registrasi Referral | Bebas |
| `/login`    | Masuk               | Bebas |

### Referral

| Route                  | Halaman              | Akses    |
| ---------------------- | -------------------- | -------- |
| `/dashboard`           | Dashboard            | Referral |
| `/simulation`          | Simulasi Kredit      | Referral |
| `/simulation/print`    | Download Hasil Simulasi | Referral |
| `/applications`        | Daftar Aplikasi      | Referral |
| `/applications/{code}` | Detail Aplikasi      | Referral |
| `/profile`             | Profil               | Referral |

### Account Officer

| Route                  | Halaman                 | Akses |
| ---------------------- | ----------------------- | ----- |
| `/dashboard`           | Dashboard               | AO    |
| `/simulation/officer`  | Simulasi Kredit         | AO    |
| `/applications`        | Daftar Aplikasi         | AO    |
| `/applications/create` | Buat Credit Application | AO    |
| `/applications/{code}` | Detail Aplikasi         | AO    |
| `/profile`             | Profil                  | AO    |

### Admin

| Route                      | Halaman                    | Akses |
| -------------------------- | -------------------------- | ----- |
| `/dashboard`               | Dashboard                  | Admin |
| `/configuration`           | Modul Konfigurasi          | Admin |
| `/configuration/products`  | Product dan Upping         | Admin |
| `/configuration/insurance` | Konfigurasi Asuransi       | Admin |
| `/configuration/fees`      | Biaya dan Down Payment     | Admin |
| `/configuration/defaults`  | Nilai Default Simulasi     | Admin |
| `/configuration/simulation` | Uji Konfigurasi           | Admin |
| `/master`                  | Modul Master Data          | Admin |
| `/master/vehicles`         | Master Kendaraan           | Admin |
| `/master/referral`         | Master Referral            | Admin |
| `/master/lookups`          | Domisili dan Kelompok Usia | Admin |
| `/accounts`                | Modul Akun                 | Admin |
| `/accounts/profile`        | Profil Admin               | Admin |
| `/accounts/referrals`      | Akun Referral              | Admin |
| `/accounts/referrals/{id}/edit` | Ubah Profil Referral | Admin |
| `/accounts/officers`       | Akun AO                    | Admin |
| `/accounts/officers/create` | Buat Akun AO              | Admin |
| `/accounts/officers/{id}/edit` | Ubah Akun AO          | Admin |
| `/lending`                 | Lending                    | Admin |
| `/lending/ao`              | Lending Per AO             | Admin |
| `/lending/referrals`       | Lending Per Referral       | Admin |

Route `/dashboard`, `/applications`, dan `/profile` digunakan bersama oleh lebih dari satu role. Isinya berbeda mengikuti role yang sedang masuk.

---

## 3. Navigasi

### Referral

```text
Dashboard · Simulasi Kredit · Aplikasi · Profil
```

### Account Officer

```text
Dashboard · Simulasi Kredit · Aplikasi · Profil
```

### Admin

```text
Dashboard · Konfigurasi · Master Data · Akun · Lending
```

Menu yang tidak dapat diakses sebuah role tidak ditampilkan. Menyembunyikan menu bukan pengganti pemeriksaan otorisasi di backend.

Pada Admin, `/configuration`, `/master`, `/accounts`, dan `/lending` adalah halaman module grid. Setiap tile membuka route subpage sendiri, bukan mengganti konten inline di bawah grid. Pada mobile, pola ini menggantikan navbar subpage horizontal agar halaman tidak penuh oleh tab yang harus discroll.

---

## 4. Landing

Halaman pemasaran untuk calon Referral.

Isi:

```text
Sapaan pembuka
Daftar jenis Referral yang dilayani
Uraian layanan pembiayaan
Tiga langkah: Registrasi, Isi Form Online, Ajukan
Tombol Registrasi dan Masuk
```

Halaman ini tidak memuat kalkulator, tidak memuat form pencarian aplikasi, dan tidak memuat data apa pun dari basis data operasional.

Draft memuat panel penelusuran aplikasi berbekal kode pada halaman depan. Panel tersebut **tidak diimplementasikan**. Ketentuannya pada `business.md` bagian 6.

---

## 5. Registrasi Referral

Form pendaftaran mandiri. Akun langsung aktif.

| Field         | Ketentuan                                           |
| ------------- | --------------------------------------------------- |
| Nama Lengkap  | Wajib                                               |
| Tanggal Lahir | Wajib                                               |
| Alamat Email  | Opsional, format email                              |
| No. Handphone | Opsional                                            |
| Nama User     | Wajib, unik                                         |
| Kata Sandi    | Wajib, minimal 8 karakter                           |
| Konfirmasi Kata Sandi | Wajib, harus sama dengan Kata Sandi         |
| Kategori      | Wajib, pilihan                                      |
| Sub-kategori  | Wajib, bergantung Kategori                          |
| Instansi      | Bergantung Sub-kategori, kosong bila tidak tersedia |
| Nama Cabang   | Opsional, teks bebas                                |

Referral menentukan kata sandinya sendiri saat pendaftaran. Kata sandi disimpan sebagai hash dan tidak ditampilkan ulang setelah pendaftaran berhasil.

Dropdown bertingkat memuat ulang pilihan dari server. Daftar instansi tidak dimuat seluruhnya ke klien.

---

## 6. Dashboard

### Referral

```text
Jumlah aplikasi yang dibawa
Jumlah aplikasi yang telah Go Live
Daftar lima aplikasi terbaru
Pintasan ke Simulasi Kredit
```

### Account Officer

```text
Jumlah aplikasi yang ditangani
Jumlah aplikasi yang belum Go Live
Daftar aplikasi dengan tahapan tertunda
Pintasan ke Buat Credit Application
```

### Admin

```text
Ringkasan Actual Lending dan Pipe Line
Jumlah akun Referral dan AO
Visualisasi komposisi lending, performa produk, dan trend Actual bulanan
```

Dashboard Admin memiliki filter waktu: 1 bulan, 3 bulan, 12 bulan, dan Semua. Nilai default adalah **Semua**. Grafik dashboard boleh memakai scroll animation untuk reveal konten, namun animasi ini dibatasi pada dashboard Admin dan tidak berlaku untuk page module/leaf Admin lain.

---

## 6b. Simulasi Kredit — Account Officer

Route `/simulation/officer`. Layar terpisah dari Simulasi Kredit Referral, dengan engine yang sama pada profil Account Officer.

Tiga perbedaan terhadap layar Referral:

| Aspek            | Referral                          | Account Officer                                            |
| ---------------- | --------------------------------- | ---------------------------------------------------------- |
| Sumber Product   | Kategori Referral yang login      | Kategori Referral dipilih manual pada bagian Asal Pengajuan |
| Parameter        | Ditetapkan Admin, tidak dapat diubah | Upping dan input asuransi dapat diubah per simulasi      |
| Keluaran         | Lima tenor, dapat diunduh sebagai PDF | Lima tenor beserta rincian perhitungan, tanpa unduhan   |

Bagian pada halaman:

```text
1 · Produk Pembiayaan
2 · Asal Pengajuan               Kategori dan Sub Kategori Referral
3 · Profil Debitur               Type Debitur dan Usia, tanpa identitas
4 · Data Kendaraan               termasuk Harga Taksasi atau Harga Pasar
5 · Asuransi                     coverage, varian rate, TJH, pengemudi, penumpang, perluasan
6 · Upping dan Pengurang Pencairan
7 · Dasar Simulasi               Mode A atau Mode B
```

Nilai pada bagian 5 dan 6 berlaku untuk satu simulasi saja. Tidak ada yang ditulis kembali ke Product maupun ke parameter Admin.

Halaman ini tidak meminta Nama, NIK, maupun Tanggal Lahir. Tidak ada dokumen yang dihasilkan, sehingga tidak ada alasan menyentuh identitas debitur.

Panel hasil menampilkan lima tenor beserta jejak penurunan angka per tenor, memakai `CalculationTrace` yang sama dengan Uji Konfigurasi Admin. AO membutuhkan angka LTV untuk mengisi Amount Finance pada Credit Application.

---

## 7. Simulasi Kredit

Halaman paling kompleks. Struktur berikut mengikat.

### Alur Halaman

```text
Pilih Produk Pembiayaan
        ↓
Isi Profil Perhitungan dan Data Kendaraan
        ↓
Pilih Dasar Simulasi
        ↓
Klik Hitung Simulasi
        ↓
Hasil Lima Tenor
        ↓
Isi Identitas Debitur jika ingin mengunduh
```

### Bagian 1 — Produk

Pilihan terbatas pada produk dalam scope:

```text
Dana Tunai
Pembiayaan Mobil Bekas
```

Produk lain tidak ditampilkan, termasuk dalam keadaan nonaktif.

### Bagian 2 — Profil Perhitungan

| Field            | Ketentuan                                          |
| ---------------- | -------------------------------------------------- |
| Domisili Debitur | Pilihan dari master domisili                       |
| Type Debitur     | Pilihan                                            |
| Usia Debitur     | Pilihan, hanya muncul bila Type Debitur Perorangan |

### Bagian 3 — Data Kendaraan

| Field           | Ketentuan                                   |
| --------------- | ------------------------------------------- |
| Penggunaan Unit | Passenger atau Commercial                   |
| Merk            | Bergantung Penggunaan Unit                  |
| Type Kendaraan  | Bergantung Merk                             |
| Model Kendaraan | Bergantung Type                             |
| Tahun Kendaraan | Bergantung Model, hanya tahun yang berharga |
| Type Angsuran   | ADDB atau ADDM                              |
| Asuransi        | Pilihan coverage                            |

Dropdown bertingkat dimuat dari server. Daftar 4.880 model tidak boleh dikirim seluruhnya ke klien.

Tahun kendaraan **hanya menampilkan tahun yang memiliki harga**. Ini mencegah pengguna memilih kombinasi yang pasti menghasilkan nol.

Field tambahan per produk:

| Produk | Field Tambahan                 |
| ------ | ------------------------------ |
| DTN    | Kebutuhan Dana, STNK atas nama |
| UCF    | Harga Pasar, STNK atas nama    |

### Bagian 4 — Dasar Simulasi

Dua metode perhitungan ditampilkan sebagai dua panel pada halaman yang sama. Istilah internal Mode A dan Mode B tidak ditampilkan kepada pengguna.

| Nilai internal | Label pengguna                                                | Input               | Keluaran               |
| -------------- | ------------------------------------------------------------- | ------------------- | ---------------------- |
| A              | Berdasarkan Nilai Kendaraan                                    | Tidak ada           | Pencairan dan Angsuran |
| B              | Berdasarkan Kebutuhan Dana (DTN) / Berdasarkan Total DP (UCF) | Nominal dikehendaki | Angsuran               |

Label input Mode B mengikuti produk: **Dana yang dibutuhkan** untuk DTN, **Total DP dikehendaki** untuk UCF.

### Bagian 5 — Hasil

Tabel lima baris tenor.

| Tenor    | Pencairan | Angsuran |
| -------- | --------- | -------- |
| 12 Bulan | …         | …        |
| 24 Bulan | …         | …        |
| 36 Bulan | …         | …        |
| 48 Bulan | …         | …        |
| 60 Bulan | …         | …        |

Judul kolom pencairan mengikuti produk dan mode:

| Produk | Mode | Judul Kolom        |
| ------ | ---- | ------------------ |
| DTN    | A    | Pencairan Maksimal |
| DTN    | B    | Pencairan          |
| UCF    | A    | Pencairan All In   |
| UCF    | B    | Total DP           |

Baris tenor yang bernilai nol tetap ditampilkan dengan nilai nol, tidak disembunyikan. Menyembunyikannya membuat pengguna mengira sistem gagal.

Keterangan wajib di bawah tabel:

```text
Nominal pembiayaan bersifat estimasi.
Besarnya pembiayaan berdasarkan hasil verifikasi profil debitur dan kondisi kendaraan.
```

### Ketentuan Perilaku

- Perubahan input membatalkan hasil aktif, tetapi tidak memicu perhitungan otomatis.
- Perhitungan hanya dijalankan di server setelah pengguna menekan **Hitung Simulasi**.
- Setelah perhitungan berhasil, halaman otomatis scroll ke bagian **Hasil Lima Tenor**.
- Tabel lima tenor tidak ditampilkan sebelum perhitungan berhasil.
- Tidak ada nilai antara yang ditampilkan. Rate, LTV, premi asuransi, dan biaya tidak muncul di layar Referral.
- Parameter konfigurasi tidak ditampilkan dan tidak dapat diubah dari halaman ini.

---

## 8. Download Hasil Simulasi

Halaman khusus review hasil simulasi, tanpa navigasi utama.

Nama, NIK, dan Tanggal Lahir calon debitur baru diminta ketika pengguna menekan aksi download pada hasil simulasi. Ketiga field divalidasi sebelum halaman review dibuka dan tidak memengaruhi hasil perhitungan.

Halaman ini menyediakan tombol **Download Simulasi Kredit**. Saat tombol dipilih, sistem membuat file PDF dari hasil/input simulasi aktif dan mengirimkannya sebagai unduhan.

Isi mengikuti `credit-simulation.md` bagian 14:

```text
Identitas calon debitur
Kode Referral pembuat simulasi
Jenis Pembiayaan
Data kendaraan
Type Angsuran dan pilihan Asuransi
Lima baris hasil tenor
Keterangan estimasi
Tanggal download
```

Halaman review dan PDF tidak menampilkan nilai antara maupun parameter konfigurasi.

---

## 9. Daftar Aplikasi

Halaman yang sama dengan isi berbeda per role.

| Kolom               | Referral | AO  |
| ------------------- | -------- | --- |
| Kode Aplikasi       | Ya       | Ya  |
| Nama Debitur        | Ya       | Ya  |
| Produk Pembiayaan   | Ya       | Ya  |
| Nama AO             | Ya       | —   |
| Nama Referral       | —        | Ya  |
| Kelengkapan Dokumen | Ya       | Ya  |
| Tahapan Selesai     | Ya       | Ya  |
| Status Go Live      | Ya       | Ya  |

Kelengkapan dokumen dan tahapan ditampilkan sebagai perbandingan, misalnya `5 / 7` dan `8 / 11`.

Penyaring: produk pembiayaan, status Go Live, pencarian berdasarkan Kode Aplikasi atau Nama Debitur.

Referral hanya melihat aplikasi yang dibawanya. AO hanya melihat aplikasi miliknya. Pembatasan berasal dari global scope, bukan dari penyaring.

Tombol **Buat Aplikasi** hanya muncul bagi AO.

---

## 10. Buat Credit Application

Form manual oleh AO.

| Field                                 | Ketentuan                          |
| ------------------------------------- | ---------------------------------- |
| Produk Pembiayaan                     | Wajib, DTN atau UCF                |
| Nama Debitur                          | Wajib                              |
| NIK Debitur                           | Wajib                              |
| Tanggal Lahir Debitur                 | Wajib                              |
| Referral                              | Wajib, pencarian akun Referral     |
| Type Debitur                          | Wajib                              |
| Konfirmasi Sumber Penghasilan Lainnya | Wajib bila Type Debitur Perorangan |
| Amount Finance                        | Opsional saat pembuatan            |
| Jumlah Unit                           | Wajib, default 1                   |

Tidak ada field data debitur lain. Ketentuannya pada `business.md` bagian 5.

Setelah tersimpan, sistem membangkitkan Kode Aplikasi, membuat baris Document Requirement yang berlaku, dan membuat sebelas baris tahapan berstatus Belum.

Pemilihan Referral menggunakan pencarian, bukan dropdown penuh. Jumlah akun Referral tidak dibatasi.

---

## 11. Detail Aplikasi

Satu halaman dengan tiga bagian. AO dapat mengubah, Referral hanya melihat.

### Bagian Data

```text
Kode Aplikasi
Produk Pembiayaan
Nama, NIK, dan Tanggal Lahir debitur
Referral: kategori, sub-kategori, instansi, cabang
Nama AO
Type Debitur
Konfirmasi Sumber Penghasilan Lainnya
Amount Finance
Jumlah Unit
Tanggal Go Live, bila telah tercapai
```

AO dapat menyunting. Produk Pembiayaan terkunci setelah Go Live.

Mengubah Type Debitur atau Konfirmasi Sumber Penghasilan Lainnya memicu penyusunan ulang daftar dokumen. Peringatan wajib ditampilkan sebelum perubahan disimpan:

```text
Mengubah Type Debitur akan menyusun ulang daftar dokumen.
Dokumen yang tidak lagi berlaku akan dihapus statusnya.
```

### Bagian Dokumen

Daftar requirement yang berlaku, terurut, dikelompokkan menurut subjek.

| Kolom   | Isi                                                |
| ------- | -------------------------------------------------- |
| Dokumen | Nama requirement                                   |
| Subjek  | Pemohon, Pasangan, Komisaris, Direksi, Badan Usaha |
| Status  | Belum atau Lengkap                                 |

AO menandai status. Referral melihat tanpa kontrol.

Requirement yang tidak berlaku tidak ditampilkan sama sekali. Tidak ada baris berlabel "tidak berlaku".

Tidak ada tombol unggah berkas.

### Bagian Tracking

Sebelas tahapan, terurut nomor.

| Kolom   | Isi                |
| ------- | ------------------ |
| No      | 1 sampai 11        |
| Tahapan | Nama tahapan       |
| Status  | Belum atau Selesai |

AO dapat menandai tahapan mana pun tanpa urutan. Antarmuka tidak boleh menonaktifkan tahapan yang tahapan sebelumnya belum selesai.

Menandai tahapan 11 sebagai Selesai menampilkan konfirmasi, karena mengubah klasifikasi Lending:

```text
Menandai Golive & Payment akan mencatat Tanggal Go Live
dan memindahkan aplikasi ini ke Actual Lending.
```

---

## 12. Halaman Admin — Konfigurasi

Seluruh halaman konfigurasi berupa tabel yang dapat disunting.

### Product dan Upping

Daftar Product beserta rate lima tenor, DP, admin minimal, admin maksimal, provisi, dan empat nilai upping.

Rate tenor yang dikosongkan berarti tenor tidak tersedia. Antarmuka harus membedakan **kosong** dan **nol** secara jelas, karena keduanya berbeda arti.

### Konfigurasi Asuransi

Empat tabel terpisah:

```text
Casco dan TLO menurut band harga, penggunaan unit, dan coverage
Loading menurut usia kendaraan
Perluasan
ACP: rate dasar per tenor dan upping per kelompok usia
TJH: lapisan dan rate
```

### Biaya dan Down Payment

```text
Fiducia berjenjang
Sum Insured per tahun
Ketentuan Net DP per produk
Persentase refund
```

### Nilai Default Simulasi

Nilai tunggal: batas usia maksimal unit, garansi mesin, wilayah asuransi aktif, varian rate, dan seluruh default pada `credit-simulation-configuration.md` bagian 12.

### Ketentuan Bersama

- Perubahan berlaku pada simulasi berikutnya.
- Setiap halaman konfigurasi menampilkan waktu perubahan terakhir dan pelakunya.
- Catatan perubahan terakhir dipisahkan per module. Misalnya perubahan `simulation_settings` dari `Biaya dan Down Payment` tidak boleh tampil sebagai perubahan terakhir pada `Nilai Default Simulasi`, walaupun keduanya memakai tabel yang sama.
- Nilai persentase ditampilkan sebagai persen dan disimpan sebagai pecahan.

---

## 13. Halaman Admin — Master Data

### Master Kendaraan

Empat level bertingkat: Penggunaan Unit, Merk, Type, Model, lalu harga per tahun.

Volume besar: 4.880 model dan 26.791 baris harga. Ketentuan:

- Wajib menggunakan pencarian dan halaman bertingkat. Dilarang memuat seluruh daftar sekaligus.
- Penyuntingan harga dilakukan per model, menampilkan seluruh tahun model tersebut dalam satu layar.
- Klasifikasi asal unit disunting pada level merk.

### Master Referral

Kategori, sub-kategori, dan instansi. Kategori memuat segment dan tier yang membentuk nama Product.

Menghapus kategori yang sedang dipakai akun Referral harus ditolak.

### Domisili dan Kelompok Usia

Daftar sederhana dengan urutan tampilan.

---

## 14. Halaman Admin — Akun

### Akun Referral

Daftar seluruh Referral. Admin dapat melihat dan menyunting profil.

Admin **tidak** dapat melihat aplikasi milik Referral. Page edit profil Referral berada pada route sendiri (`/accounts/referrals/{id}/edit`) dan form edit tidak ditampilkan inline di bawah tabel.

### Akun AO

Daftar AO, dengan kemampuan membuat akun baru. Page buat akun AO dan page ubah profil AO berada pada route sendiri, bukan form inline di bawah tabel.

Kata sandi awal dibuat otomatis dan ditampilkan sekali setelah akun dibuat. Tampilan kata sandi awal berupa popup sederhana, bukan card inline. Popup harus menyediakan tombol ikon salin untuk menyalin kata sandi, feedback setelah tersalin, dan kontrol untuk menutup popup. Setelah ditutup atau terjadi interaksi Livewire berikutnya, kata sandi tidak dapat dimunculkan kembali.

---

## 15. Halaman Admin — Lending

Dua pengelompokan, dipilih melalui module Lending.

```text
Per AO        baris berisi Referral
Per Referral  baris berisi AO
```

Kolom pada kedua tab:

| Kolom          | Isi                          |
| -------------- | ---------------------------- |
| Nama           | Referral atau AO             |
| Actual Unit    | Jumlah unit Go Live          |
| Actual A/F     | Amount Finance Go Live       |
| Pipe Line Unit | Jumlah unit belum Go Live    |
| Pipe Line A/F  | Amount Finance belum Go Live |

Baris TOTAL wajib ada dan mengikuti penyaring aktif.

Penyaring: bulan Go Live, produk pembiayaan, dan kategori Referral. Filter diperbarui secara asynchronous dengan Livewire; tidak ada tombol Terapkan. Query string tetap disinkronkan agar URL laporan dapat dibagikan. Tombol Bersihkan hanya muncul saat ada filter aktif dan berjalan asynchronous.

---

## 16. Komponen Berulang

| Komponen            | Ketentuan                                            |
| ------------------- | ---------------------------------------------------- |
| Status dokumen      | Silang untuk Belum, ceklis untuk Lengkap             |
| Status tahapan      | Silang untuk Belum, ceklis untuk Selesai             |
| Tabel tenor         | Selalu lima baris, urut 12 sampai 60                 |
| Dropdown bertingkat | Dimuat dari server, tidak dimuat seluruhnya ke klien |
| Pencarian akun      | Digunakan menggantikan dropdown penuh                |

---

## 17. Ketentuan Tampilan

| Jenis      | Format                                |
| ---------- | ------------------------------------- |
| Uang       | `Rp 92.131.800`, tanpa desimal        |
| Persentase | Satu angka desimal, misalnya `18,5%`  |
| Tanggal    | `03 Agustus 2026`                     |
| Tenor      | `12 Bulan`                            |
| Nilai nol  | Ditampilkan `Rp 0`, bukan dikosongkan |

Pemisah ribuan menggunakan titik dan pemisah desimal menggunakan koma, mengikuti kebiasaan Indonesia.

Angka yang ditampilkan adalah hasil akhir dari server. Antarmuka tidak melakukan pembulatan sendiri.

---

## 18. State Halaman

| State           | Perilaku                                                                            |
| --------------- | ----------------------------------------------------------------------------------- |
| Kosong          | Menjelaskan penyebab dan langkah berikutnya, bukan sekadar "tidak ada data"         |
| Sedang memuat   | Menandai bagian yang sedang dihitung, bukan menutup seluruh halaman                 |
| Gagal hitung    | Menyebut penyebab, misalnya harga tidak tersedia untuk tahun terpilih               |
| Tidak berwenang | Halaman 403, tanpa membocorkan keberadaan data                                      |
| Tidak ditemukan | Halaman 404, tidak dibedakan dari kasus tidak berwenang untuk data milik orang lain |

Baris terakhir penting: bagi AO yang membuka aplikasi milik AO lain, sistem menampilkan tidak ditemukan. Membedakan 403 dan 404 membocorkan keberadaan aplikasi tersebut.

---

## 19. Open Items

| No  | Item                     | Keterangan                                                                                                                              |
| --- | ------------------------ | --------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Riwayat simulasi         | Hasil simulasi tidak disimpan, sehingga Referral tidak dapat membuka kembali simulasi sebelumnya. Perlu ditetapkan apakah ini diterima. |
| 2   | Halaman Report           | Bergantung penetapan Report pada `business.md` bagian 8.                                                                                |
| 3   | Tampilan mobile          | Perlu ditetapkan apakah Referral menggunakan telepon genggam di lapangan, karena halaman simulasi padat isian.                          |

---

## 20. Related Documentation

| Document                  | Purpose                                 |
| ------------------------- | --------------------------------------- |
| `business.md`             | Business context dan prinsip akses      |
| `actors.md`               | Actors, responsibilities, dan access    |
| `workflow.md`             | End-to-end business workflow            |
| `credit-simulation.md`    | Input, perhitungan, dan output simulasi |
| `document-requirement.md` | Katalog dan aturan keberlakuan dokumen  |
| `application-tracking.md` | Struktur application dan tahapan        |
| `lending.md`              | Agregasi dan penyaring Lending          |
| `architecture.md`         | Keputusan teknis                        |
| `session-changes-2026-08-11.md` | Ringkasan perubahan penting sesi 2026-08-11 |
