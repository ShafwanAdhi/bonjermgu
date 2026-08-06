# Business

## 1. Overview

Sistem ini merupakan website operasional untuk mendukung proses pembiayaan pada perusahaan multifinance.

Sistem mencakup proses dari **Credit Simulation** hingga **Go Live & Payment** serta pembaruan data **Lending**.

Sistem tidak mengotomatisasi seluruh proses pembiayaan. Beberapa aktivitas berlangsung di luar sistem, sementara informasi atau perkembangan yang diperlukan dicatat dalam sistem.

Sistem memiliki tiga role utama:

* Admin
* Referral
* Account Officer (AO)

Detail tanggung jawab dan akses setiap role dijelaskan dalam `actors.md`.

---

## 2. Financing Products

Sistem mengenal empat produk pembiayaan. Dua di antaranya berada dalam scope implementasi saat ini.

| Code | Produk                         | Status         |
| ---- | ------------------------------ | -------------- |
| DTN  | Dana Tunai                     | Dalam scope    |
| UCF  | Pembiayaan Mobil Bekas         | Dalam scope    |
| LMF  | Pembiayaan Emas / Logam Mulia  | Belum ditetapkan |
| NCF  | Pembiayaan Mobil Baru          | Belum ditetapkan |

Produk berstatus belum ditetapkan tidak memiliki ketentuan perhitungan. Sistem tidak menampilkan simulasi untuk produk tersebut sampai ketentuannya ditetapkan.

---

## 3. System Scope

Scope utama sistem mencakup:

* Credit Simulation
* Credit Simulation Configuration
* Credit Application
* Document Verification
* Application Tracking
* Go Live & Payment
* Lending
* Report

Detail masing-masing domain dijelaskan pada dokumentasi berikut:

* `credit-simulation.md`
* `credit-simulation-configuration.md`
* `credit-simulation-test-vectors.md`
* `document-requirement.md`
* `application-tracking.md`
* `lending.md`

Urutan proses bisnis dijelaskan dalam `workflow.md`.

---

## 4. System Boundary

### Inside the System

Sistem menangani aktivitas dan data yang secara eksplisit menjadi bagian dari:

* Account Referral
* Account AO
* Credit Simulation
* Credit Simulation Configuration
* Print hasil Credit Simulation
* Credit Application
* Document Verification
* Application Tracking
* Go Live & Payment
* Lending
* Report
* Referral dan AO profile management

### Outside the System

Aktivitas berikut berlangsung di luar sistem:

* Interaksi langsung Referral dengan calon debitur.
* Pengiriman hasil simulasi dari Referral kepada AO.
* Pengiriman dokumen dari Referral kepada AO.
* Penyimpanan berkas dokumen debitur.
* Proses Credit Risk dan scoring.
* Monitoring EPD & AR, termasuk data kontrak, tunggakan, dan outstanding principal.
* Aktivitas operasional lain yang tidak secara eksplisit ditentukan sebagai bagian dari sistem.

Detail interaksi antara aktivitas internal dan eksternal dijelaskan dalam `workflow.md`.

---

## 5. Debtor Data

Sistem menerapkan prinsip minimalisasi pencatatan data debitur.

Data debitur yang disimpan dalam sistem hanya:

* Nama
* NIK
* Tanggal lahir

Data debitur lainnya tidak boleh ditambahkan tanpa kebutuhan yang telah ditentukan.

Prinsip ini juga berlaku pada dokumen. Sistem mencatat status kelengkapan dokumen tanpa menyimpan berkasnya. Ketentuannya dijelaskan pada `document-requirement.md`.

---

## 6. Access Principle

Seluruh akses terhadap data application memerlukan autentikasi.

| Data                  | Ketentuan Akses                        |
| --------------------- | -------------------------------------- |
| Application Tracking  | AO pemilik dapat mengubah, Referral pembawa dapat melihat |
| Document Verification | AO pemilik dapat mengubah, Referral pembawa dapat melihat |
| Lending               | Admin                                  |
| Configuration         | Admin                                  |

Kode Aplikasi berfungsi sebagai identifier, bukan sebagai sarana autentikasi. Sistem tidak menyediakan akses terhadap data application hanya berbekal Kode Aplikasi.

Seluruh pembatasan merupakan authorization rule dan harus diterapkan pada backend, bukan hanya pada tampilan.

---

## 7. Business Scope Boundary

Scope sistem berakhir pada:

```text
Credit Simulation
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

Proses post-lending di luar alur tersebut tidak termasuk dalam scope sistem.

---

## 8. Open Items

| No | Item          | Keterangan                                                                                     |
| -- | ------------- | ------------------------------------------------------------------------------------------------ |
| 1  | Report        | Perlu ditetapkan apakah Report merupakan modul terpisah atau merujuk pada laporan Lending.        |
| 2  | Produk LMF dan NCF | Perlu ditetapkan ketentuan perhitungan sebelum kedua produk dapat masuk implementasi.        |

---

## 9. Related Documentation

| Document                             | Purpose                                         |
| ------------------------------------ | ----------------------------------------------- |
| `actors.md`                          | Actors, responsibilities, dan access            |
| `workflow.md`                        | End-to-end business workflow                    |
| `credit-simulation.md`               | Credit Simulation requirements                  |
| `credit-simulation-configuration.md` | Credit Simulation configuration dan calculation |
| `credit-simulation-test-vectors.md`  | Nilai acuan pengujian perhitungan               |
| `document-requirement.md`            | Document requirements dan verification          |
| `application-tracking.md`            | Application Tracking                            |
| `lending.md`                         | Lending                                         |