# Lending

## 1. Overview

Lending merupakan pencatatan hasil pembiayaan yang dihitung dari application yang telah diproses dalam sistem.

Lending tidak memiliki input operasional. Seluruh angka dihitung sistem dari data application. Tidak ada nilai yang diinput manual pada modul ini.

Lending mengelompokkan hasil berdasarkan Referral yang membawa debitur dan AO yang menangani application.

---

## 2. Definitions

| Istilah        | Definisi                                                        |
| -------------- | --------------------------------------------------------------- |
| Actual Lending | Application yang telah mencapai Go Live                          |
| Pipe Line      | Application yang belum mencapai Go Live                          |
| Unit           | Jumlah unit kendaraan pada application                           |
| Amount Finance | Nilai LTV pada application, diinput AO                           |

Ketentuan Go Live dijelaskan pada `application-tracking.md`.

---

## 3. Classification Rule

Setiap application diklasifikasikan berdasarkan status tahapan 11.

```text
Application
     ↓
Tahap 11 Golive & Payment
     ↓
┌─────────────────┬──────────────────┐
│ Selesai         │ Belum            │
↓                 ↓                  
Actual Lending    Pipe Line
```

Ketentuan:

* Sebuah application berada pada tepat satu klasifikasi.
* Application berpindah dari Pipe Line ke Actual Lending saat tahapan 11 ditandai Selesai.
* Apabila tahapan 11 dikembalikan menjadi Belum, application kembali ke Pipe Line dan Tanggal Go Live dikosongkan.

---

## 4. Measures

Setiap klasifikasi menghasilkan dua ukuran.

| Ukuran         | Perhitungan                                        |
| -------------- | -------------------------------------------------- |
| Unit           | Jumlah unit dari seluruh application pada kelompok |
| Amount Finance | Penjumlahan Amount Finance seluruh application pada kelompok |

Application yang belum memiliki Amount Finance dihitung sebagai 0 pada ukuran Amount Finance, namun tetap dihitung pada ukuran Unit.

---

## 5. Aggregation

Lending disajikan dalam dua pengelompokan.

### Per AO

```text
AO
├── Referral A  → Actual (Unit, A/F) | Pipe Line (Unit, A/F)
├── Referral B  → Actual (Unit, A/F) | Pipe Line (Unit, A/F)
└── Total       → Actual (Unit, A/F) | Pipe Line (Unit, A/F)
```

Menjawab pertanyaan: Referral mana saja yang menyumbang hasil pada seorang AO.

### Per Referral

```text
Referral
├── AO A  → Actual (Unit, A/F) | Pipe Line (Unit, A/F)
├── AO B  → Actual (Unit, A/F) | Pipe Line (Unit, A/F)
└── Total → Actual (Unit, A/F) | Pipe Line (Unit, A/F)
```

Menjawab pertanyaan: AO mana saja yang menangani debitur dari seorang Referral.

Kedua pengelompokan menggunakan data yang sama dan harus menghasilkan total keseluruhan yang identik.

---

## 6. Report Structure

Struktur tabel pada pengelompokan Per AO.

| No | Nama Referral | Actual Unit | Actual A/F | Pipe Line Unit | Pipe Line A/F |
| -- | ------------- | ----------- | ---------- | -------------- | ------------- |
| 1  | …             | …           | …          | …              | …             |
| 2  | …             | …           | …          | …              | …             |
|    | **TOTAL**     | …           | …          | …              | …             |

Struktur pada pengelompokan Per Referral mengikuti pola yang sama dengan kolom Nama Referral diganti Nama AO.

Ketentuan:

* Baris hanya muncul untuk Referral yang memiliki sedikitnya satu application.
* Baris TOTAL merupakan penjumlahan seluruh baris di atasnya.

---

## 7. Reporting Period

Pemotongan periode menggunakan **Tanggal Go Live**. Di UI Admin, periode dipilih sebagai **bulan Go Live** (`YYYY-MM`) dan sistem mengubahnya menjadi rentang awal sampai akhir bulan untuk query.

| Klasifikasi    | Dasar Periode                          |
| -------------- | -------------------------------------- |
| Actual Lending | Tanggal Go Live berada dalam periode   |
| Pipe Line      | Tidak dipotong periode, bersifat posisi berjalan |

Pipe Line menggambarkan kondisi saat laporan dibuka, bukan kejadian dalam suatu rentang waktu. Karena itu Pipe Line tidak memiliki dasar periode.

Apabila bulan tidak dipilih, Actual Lending menampilkan seluruh application yang telah Go Live.

---

## 8. Identity Reference

Referral pada laporan Lending merujuk pada **akun Referral**, bukan kategori Referral.

```text
Akun Referral
├── Kategori
├── Sub-kategori
├── Instansi
└── Cabang
```

Keempat atribut di atas dapat digunakan sebagai penyaring tambahan, namun pengelompokan baris tetap pada level akun.

### Penyaring

| Penyaring         | Nilai                                          |
| ----------------- | ---------------------------------------------- |
| Bulan Go Live     | Berlaku pada Actual Lending saja               |
| Produk Pembiayaan | Dana Tunai, Pembiayaan Mobil Bekas, atau semua |
| Kategori Referral | Semua kategori atau satu kategori tertentu     |

Penyaring bersifat opsional dan dapat digabungkan. Baris TOTAL selalu mengikuti penyaring yang sedang aktif.

Pada UI Admin, perubahan filter berjalan asynchronous melalui Livewire. Tidak ada tombol Terapkan; tabel, baris TOTAL, loading state, dan query string diperbarui saat filter berubah. Tombol Bersihkan hanya muncul ketika ada filter aktif.

---

## 9. Access

| Role     | Akses         |
| -------- | ------------- |
| Admin    | Seluruh data  |
| AO       | Tidak memiliki akses |
| Referral | Tidak memiliki akses |

Lending merupakan laporan manajerial. Pembatasan akses merupakan authorization rule dan harus diterapkan pada backend.

---

## 10. Out of Scope

Aktivitas berikut berada di luar scope sistem:

* Monitoring EPD & AR
* Pemantauan tunggakan dan hari keterlambatan
* Data kontrak, angsuran berjalan, dan outstanding principal

Data tersebut berasal dari sistem inti perusahaan dan tidak dicatat maupun ditampilkan oleh sistem ini.

Ketentuan ini menjaga konsistensi dengan prinsip minimalisasi data debitur pada `business.md`.

---

## 11. Open Items

| No | Item                    | Keterangan                                                                                                   |
| -- | ----------------------- | ------------------------------------------------------------------------------------------------------------ |
| 1  | Jumlah unit             | Perlu ditetapkan apakah satu application selalu bernilai satu unit, atau dapat mencakup lebih dari satu kendaraan. |
| 2  | Application menggantung | Perlu ditetapkan perlakuan application yang lama tidak berkembang, agar tidak menumpuk pada Pipe Line.       |
| 3  | Akses AO dan Referral   | Perlu ditetapkan apakah AO dan Referral dapat melihat hasil miliknya sendiri.                                 |
| 4  | Report                  | `business.md` menyebut Report sebagai domain tersendiri. Perlu ditetapkan apakah Report merupakan modul terpisah atau merujuk pada laporan Lending ini. |

---

## 12. Related Documentation

| Document                  | Purpose                                       |
| ------------------------- | --------------------------------------------- |
| `business.md`             | Business context dan system scope             |
| `actors.md`               | Actors, responsibilities, dan access          |
| `workflow.md`             | End-to-end business workflow                  |
| `application-tracking.md` | Application Tracking dan Go Live              |
| `document-requirement.md` | Document requirements dan verification        |
| `credit-simulation.md`    | Credit Simulation                             |
