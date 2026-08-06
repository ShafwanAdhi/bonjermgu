# Workflow

## 1. Overview

Workflow sistem berjalan dari konfigurasi Credit Simulation hingga Go Live & Payment dan pembaruan Lending.

```text
Admin
  ↓
Credit Simulation Configuration
  ↓
Referral
  ↓
Credit Simulation
  ↓
Customer Decision
  ↓
Print Simulation
  ↓
External Handoff to AO
  ↓
AO
  ↓
Credit Application
  ↓
Document Verification
  ↓
Application Tracking
  ↓
Go Live & Payment
  ↓
Lending
```

Sebagian aktivitas berlangsung di luar sistem. Sistem mencatat status dan hasil yang diperlukan, bukan menjalankan prosesnya.

---

## 2. Account Registration

Pembuatan akun berbeda antara Referral dan AO.

### Referral

```text
Calon Referral
      ↓
Isi Form Registrasi
      ↓
Akun Aktif
```

Referral mendaftar sendiri dan akun langsung aktif tanpa persetujuan Admin.

### AO

```text
Admin
  ↓
Buat Akun AO
  ↓
Akun Aktif
```

AO tidak dapat mendaftar sendiri.

Ketentuan atribut akun dan kata sandi berada pada `actors.md`.

---

## 3. Credit Simulation Configuration

Admin mengatur konfigurasi Credit Simulation yang akan digunakan dalam proses simulasi.

```text
Admin
  ↓
Configure Simulation
  ↓
Active Configuration
  ↓
Available for Credit Simulation
```

Konfigurasi mencakup seluruh parameter perhitungan: Product, Upping, master PHPM, tabel asuransi, biaya, ketentuan Down Payment, dan nilai default simulasi.

Perubahan konfigurasi berlaku pada simulasi berikutnya dan tidak mengubah PDF hasil simulasi yang telah diunduh.

Detail parameter dan perhitungan berada pada:

`credit-simulation-configuration.md`

---

## 4. Credit Simulation

Referral berinteraksi secara langsung dengan calon debitur untuk melakukan Credit Simulation.

```text
Referral
  ↓
Pilih Produk Pembiayaan
  ↓
Input Simulation Data
  ↓
System Processes Simulation
  ↓
Simulation Result
  ↓
Customer
```

### Produk yang Tersedia

```text
Dana Tunai
Pembiayaan Mobil Bekas
```

Pembiayaan Emas / Logam Mulia dan Pembiayaan Mobil Baru belum memiliki ketentuan perhitungan dan tidak ditampilkan.

### Mode Simulasi

Setiap produk memiliki dua mode.

```text
Mode A  Data Kendaraan            → Pencairan Maksimal + Angsuran
Mode B  Nominal yang Dikehendaki  → Angsuran
```

Kedua mode menghasilkan lima baris tenor.

Simulasi dijalankan sepenuhnya oleh sistem. Referral tidak dapat mengubah parameter perhitungan.

Detail input, output, dan print berada pada:

`credit-simulation.md`

---

## 5. Customer Decision

Calon debitur menentukan apakah akan melanjutkan pembiayaan berdasarkan hasil Credit Simulation.

### Does Not Continue

```text
Simulation Result
       ↓
Customer Does Not Continue
       ↓
Workflow Ends
```

### Agrees to Financing

```text
Simulation Result
       ↓
Customer Agrees
       ↓
Print Simulation
```

Hasil simulasi kemudian menjadi dasar untuk proses berikutnya.

Hasil simulasi bersifat estimasi dan tidak mengikat.

---

## 6. Referral to AO Handoff

Setelah calon debitur menyetujui pembiayaan, hasil Credit Simulation dikirimkan dari Referral kepada AO melalui jalur komunikasi di luar sistem.

```text
Referral
   ↓
Printed Simulation
   ↓
External Communication
   ↓
AO
```

Contoh media komunikasi:

* WhatsApp
* Telegram
* Media komunikasi pribadi lainnya

Proses handoff ini tidak dilakukan melalui sistem. Sistem tidak menyimpan hasil simulasi maupun kaitannya dengan application.

---

## 7. Credit Application

AO menerima informasi dari Referral dan memasukkan data application secara manual ke dalam sistem.

```text
Simulation Result
       ↓
AO
       ↓
Manual Input
       ↓
Credit Application
       ↓
Kode Aplikasi terbentuk
```

### Data yang Diinput AO

```text
Nama, NIK, dan Tanggal Lahir debitur
Referral yang membawa debitur
Type Debitur
Konfirmasi Sumber Penghasilan Lainnya
Amount Finance
```

Amount Finance diinput ulang oleh AO dan tidak diambil dari hasil simulasi, karena nilai final dapat berbeda dari estimasi.

### Efek Otomatis

```text
Type Debitur + Konfirmasi Sumber Penghasilan Lainnya
       ↓
Sistem menentukan Document Requirement yang berlaku
       ↓
Status awal seluruh requirement: Belum
```

Kode Aplikasi dibangkitkan sistem sebagai identifier. Kode tersebut bukan sarana autentikasi.

---

## 8. Document Handoff

Dokumen pelengkap debitur dikirimkan dari Referral kepada AO melalui jalur komunikasi di luar sistem.

```text
Referral
   ↓
Supporting Documents
   ↓
External Communication
   ↓
AO
```

Berkas dokumen tidak diunggah ke dalam sistem.

---

## 9. Document Verification

AO memeriksa dokumen yang diterima dan mencatat hasil verifikasi dalam sistem.

```text
AO
 ↓
Review Documents
 ↓
Verify Requirements
 ↓
Record Status
```

Sistem hanya menyimpan status setiap requirement:

```text
Belum    → dokumen berlaku namun belum terpenuhi
Lengkap  → dokumen telah diperiksa dan dinyatakan lengkap
```

Kelengkapan dokumen bukan syarat untuk memulai Application Tracking. Kedua proses berjalan independen.

Detail dokumen dan ketentuan verifikasi berada pada:

`document-requirement.md`

---

## 10. Application Tracking

Setelah application dibuat, perkembangannya dicatat melalui sebelas tahapan yang telah ditentukan.

Tahapan tersebut adalah:

```text
1. Verifikasi, Validasi dan kelengkapan data permohonan

2. Survey Domisili & Tempat usaha/Kantor

3. Cek fisik Kendaraan

4. Laporan Hasil Survey

5. Proses Aplikasi

6. Konfirmasi Debitur by phone & Scoring Credit

7. Permohonan Persetujuan Pembiayaan

8. Persetujuan Pembiayaan (PO)

9. Verifikasi, Validasi dan kelengkapan Jaminan (BPKB)

10. Konfirmasi Debitur by phone

11. Golive & Payment
```

Tahapan tidak harus diselesaikan berurutan. Sebagian aktivitas berjalan paralel di luar sistem, sehingga urutan penyelesaian tidak selalu sesuai nomor tahapan.

Hanya AO pemilik application yang dapat mengubah status tahapan.

Tahapan, status, dan ketentuan setiap tracking stage dijelaskan pada:

`application-tracking.md`

---

## 11. Credit Risk Process

Sebagian aktivitas Credit Risk dilakukan di luar sistem.

```text
Application
     ↓
AO
     ↓
External Credit Risk Process
     ↓
AO records relevant progress
     ↓
Application Tracking
```

Credit Risk Team melakukan proses Credit Risk dan scoring di luar sistem.

Sistem digunakan untuk mencatat progress atau hasil yang memang diperlukan dalam Application Tracking. Sistem tidak menyimpan hasil scoring, catatan survey, maupun dokumen persetujuan.

---

## 12. Referral Monitoring

Referral memantau perkembangan application yang dibawanya, berjalan paralel dengan aktivitas AO.

```text
AO memperbarui status
       ↓
Application
       ↓
Referral melihat status
```

Ketentuan:

* Referral hanya melihat application yang membawa identitas Referral miliknya.
* Referral melihat status dokumen dan status tracking.
* Referral tidak dapat mengubah status apa pun.
* Akses tetap memerlukan autentikasi.

Referral tidak lagi perlu menghubungi AO untuk menanyakan perkembangan, namun tetap tidak memiliki kendali atas prosesnya.

---

## 13. Go Live & Payment

Setelah proses Application Tracking mencapai tahap **Golive & Payment**, application mencapai tahap Go Live & Payment.

```text
Application Tracking
       ↓
Tahap 11 ditandai Selesai
       ↓
Sistem mencatat Tanggal Go Live
       ↓
Application dinyatakan Go Live
```

Tahap ini merupakan akhir dari proses application sebelum data Lending diperbarui.

Apabila tahap 11 dikembalikan menjadi Belum, Tanggal Go Live dikosongkan dan application kembali dihitung sebagai Pipe Line.

---

## 14. Lending

Data Lending dihitung sistem dari seluruh application.

```text
Application
     ↓
┌──────────────────┬──────────────────┐
│ Sudah Go Live    │ Belum Go Live    │
↓                  ↓
Actual Lending     Pipe Line
     ↓                  ↓
     └────────┬─────────┘
              ↓
   Dikelompokkan per AO dan per Referral
```

Lending tidak memiliki input operasional. Seluruh angka dihitung dari data application.

Detail data, perhitungan, dan ketentuan Lending berada pada:

`lending.md`

---

## 15. Out of Scope

Aktivitas berikut berada di luar alur sistem:

```text
Penyimpanan berkas dokumen debitur
Proses Credit Risk dan scoring
Monitoring EPD & AR
Proses post-lending
```

---

## 16. Related Documentation

| Document                             | Purpose                                         |
| ------------------------------------ | ----------------------------------------------- |
| `business.md`                        | Business context dan system scope               |
| `actors.md`                          | Actors, responsibilities, dan access            |
| `credit-simulation.md`               | Credit Simulation requirements                  |
| `credit-simulation-configuration.md` | Credit Simulation configuration dan calculation |
| `credit-simulation-test-vectors.md`  | Nilai acuan pengujian perhitungan               |
| `document-requirement.md`            | Document requirements dan verification          |
| `application-tracking.md`            | Application Tracking                            |
| `lending.md`                         | Lending                                         |
