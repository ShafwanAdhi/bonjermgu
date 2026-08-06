# Document Requirement

## 1. Overview

Dokumen ini mendefinisikan dokumen pelengkap yang dibutuhkan setiap permohonan pembiayaan, beserta ketentuan verifikasinya.

Daftar dokumen bersifat **kondisional**. Dokumen yang wajib dilengkapi ditentukan oleh Type Debitur dan kondisi penghasilan pasangan.

Proses verifikasi dilakukan oleh AO yang bertanggung jawab atas application terkait.

---

## 2. Document Handling Principle

Dokumen fisik maupun salinan digital **tidak diunggah ke dalam sistem**.

```text
Referral
   ↓
Dokumen Debitur
   ↓
Jalur komunikasi di luar sistem
   ↓
AO
   ↓
AO memeriksa dokumen
   ↓
Sistem mencatat status verifikasi
```

Sistem hanya menyimpan **status** setiap dokumen. Sistem tidak menyimpan berkas, nama berkas, maupun tautan berkas.

Konsekuensi ketentuan ini:

* Sistem tidak memerlukan penyimpanan berkas.
* Status dokumen merupakan pernyataan AO, bukan hasil pemeriksaan otomatis.
* Perubahan status harus dapat ditelusuri kepada AO yang melakukannya.

---

## 3. Data Model

Sistem **tidak menggunakan slot posisional**. Setiap dokumen memiliki identitas sendiri.

```text
Document Requirement          (master, beridentitas)
        ↓
Applicability Rule            (menentukan requirement mana yang berlaku)
        ↓
Application Document Status   (status per application per requirement)
```

### Document Requirement

Master dokumen. Setiap baris memiliki kode unik yang tidak berubah.

| Attribute | Keterangan                                             |
| --------- | ------------------------------------------------------ |
| Code      | Kode unik, stabil, tidak boleh berubah                 |
| Nama      | Nama dokumen yang ditampilkan                          |
| Subjek    | Pemohon / Pasangan / Komisaris / Direksi / Badan Usaha |
| Urutan    | Urutan tampilan                                        |

### Application Document Status

Status disimpan sebagai baris terpisah, satu baris per requirement per application.

| Attribute   | Keterangan                |
| ----------- | ------------------------- |
| Application | Application terkait       |
| Requirement | Kode Document Requirement |
| Status      | Belum / Lengkap           |

Baris hanya dibuat untuk requirement yang **berlaku** pada profil debitur terkait. Requirement yang tidak berlaku tidak memiliki baris sama sekali.

Karena status terikat pada kode requirement dan bukan pada posisi, status tidak dapat tertukar antar dokumen.

---

## 4. Status

| Status  | Arti                                           | Tampilan |
| ------- | ---------------------------------------------- | -------- |
| Belum   | Dokumen berlaku namun belum terpenuhi          | Silang   |
| Lengkap | Dokumen telah diperiksa dan dinyatakan lengkap | Ceklis   |

Status awal setiap requirement yang berlaku adalah **Belum**.

Kondisi "tidak berlaku" tidak disimpan sebagai status. Ketiadaan baris sudah menyatakan bahwa requirement tersebut tidak berlaku.

---

## 5. Document Determinants

Requirement yang berlaku ditentukan oleh dua field pada Credit Application.

### Type Debitur

```text
Perorangan (Non Wiraswasta)
Perorangan (Wiraswasta)
Badan Hukum Usaha
```

### Konfirmasi Sumber Penghasilan Lainnya

Field ini hanya berlaku apabila Type Debitur adalah Perorangan.

```text
Pasangan Bekerja dan memiliki penghasilan
Pasangan memiliki usaha lainnya dan memiliki penghasilan
Pasangan adalah profesional dan memiliki penghasilan
Tidak Ada
```

Apabila Type Debitur adalah Badan Hukum Usaha, field ini tidak berlaku dan tidak ada requirement pasangan yang dibuat.

---

## 6. Document Requirement Catalogue

### Kelompok Perorangan

| Code        | Nama                                           | Subjek   | Urutan |
| ----------- | ---------------------------------------------- | -------- | ------ |
| PMH-KTP     | KTP Pemohon                                    | Pemohon  | 1      |
| PSG-KTP     | KTP Pasangan                                   | Pasangan | 2      |
| PMH-NPWP    | NPWP Pemohon                                   | Pemohon  | 3      |
| PMH-KK      | Kartu Keluarga                                 | Pemohon  | 4      |
| PMH-RUMAH   | Bukti Kepemilikan Rumah (SPPT PBB / AJB / SHM) | Pemohon  | 5      |
| PMH-SLIP    | Slip Gaji Carbonized                           | Pemohon  | 6      |
| PMH-RKR     | Rek Koran 3 bulan terakhir                     | Pemohon  | 7      |
| PMH-USAHA   | Legalitas Usaha (NIB / SKDU)                   | Pemohon  | 8      |
| PMH-FAKTUR  | Bon / Faktur Penjualan                         | Pemohon  | 9      |
| PMH-PROFESI | Surat Ijin Profesi                             | Pemohon  | 10     |

### Kelompok Badan Hukum Usaha

| Code         | Nama                                      | Subjek      | Urutan |
| ------------ | ----------------------------------------- | ----------- | ------ |
| KOM-KTP      | KTP Komisaris                             | Komisaris   | 1      |
| DIR-KTP      | KTP Direksi                               | Direksi     | 2      |
| DIR-NPWP     | NPWP Direksi                              | Direksi     | 3      |
| BDN-NPWP     | NPWP Usaha                                | Badan Usaha | 4      |
| BDN-AKTA-DIR | Akte Pendirian                            | Badan Usaha | 5      |
| BDN-AKTA-UBH | Akte Perubahan                            | Badan Usaha | 6      |
| BDN-SKKUM    | SK Kemenkumham                            | Badan Usaha | 7      |
| BDN-NIB      | Legalitas Usaha (NIB & Ijin Usaha khusus) | Badan Usaha | 8      |
| BDN-LAPKEU   | Laporan Keuangan                          | Badan Usaha | 9      |
| BDN-RKR      | Rek Koran 3 bulan terakhir                | Badan Usaha | 10     |
| BDN-SPK      | SPK / MOU                                 | Badan Usaha | 11     |

### Kelompok Pasangan

| Code        | Nama                         | Subjek   | Urutan |
| ----------- | ---------------------------- | -------- | ------ |
| PSG-RKR     | Rek Koran 3 bulan terakhir   | Pasangan | 1      |
| PSG-SLIP    | Slip Gaji Carbonized         | Pasangan | 2      |
| PSG-USAHA   | Legalitas Usaha (NIB / SKDU) | Pasangan | 3      |
| PSG-FAKTUR  | Bon / Faktur Penjualan       | Pasangan | 4      |
| PSG-PROFESI | Surat Ijin Profesi           | Pasangan | 5      |

Total katalog: **26 requirement**

---

## 7. Applicability Rules

### Perorangan (Non Wiraswasta)

```text
PMH-KTP      KTP Pemohon
PSG-KTP      KTP Pasangan
PMH-NPWP     NPWP Pemohon
PMH-KK       Kartu Keluarga
PMH-RUMAH    Bukti Kepemilikan Rumah
PMH-SLIP     Slip Gaji Carbonized
PMH-RKR      Rek Koran 3 bulan terakhir
```

Jumlah: **7**

### Perorangan (Wiraswasta)

```text
PMH-KTP      KTP Pemohon
PSG-KTP      KTP Pasangan
PMH-NPWP     NPWP Pemohon
PMH-KK       Kartu Keluarga
PMH-RUMAH    Bukti Kepemilikan Rumah
PMH-RKR      Rek Koran 3 bulan terakhir
PMH-USAHA    Legalitas Usaha (NIB / SKDU)
PMH-FAKTUR   Bon / Faktur Penjualan
PMH-PROFESI  Surat Ijin Profesi
```

Jumlah: **9**

Slip Gaji Carbonized tidak berlaku pada Wiraswasta.

### Badan Hukum Usaha

```text
KOM-KTP       KTP Komisaris
DIR-KTP       KTP Direksi
DIR-NPWP      NPWP Direksi
BDN-NPWP      NPWP Usaha
BDN-AKTA-DIR  Akte Pendirian
BDN-AKTA-UBH  Akte Perubahan
BDN-SKKUM     SK Kemenkumham
BDN-NIB       Legalitas Usaha (NIB & Ijin Usaha khusus)
BDN-LAPKEU    Laporan Keuangan
BDN-RKR       Rek Koran 3 bulan terakhir
BDN-SPK       SPK / MOU
```

Jumlah: **11**

### Dokumen Pasangan

Berlaku hanya apabila Type Debitur adalah Perorangan.

| Konfirmasi Sumber Penghasilan Lainnya                    | Requirement yang berlaku       |
| -------------------------------------------------------- | ------------------------------ |
| Pasangan Bekerja dan memiliki penghasilan                | PSG-RKR, PSG-SLIP              |
| Pasangan memiliki usaha lainnya dan memiliki penghasilan | PSG-RKR, PSG-USAHA, PSG-FAKTUR |
| Pasangan adalah profesional dan memiliki penghasilan     | PSG-RKR, PSG-PROFESI           |
| Tidak Ada                                                | Tidak ada                      |

---

## 8. Perubahan Field Penentu

Apabila Type Debitur atau Konfirmasi Sumber Penghasilan Lainnya diubah, sistem menghitung ulang daftar requirement yang berlaku.

```text
Requirement yang tetap berlaku          →  status dipertahankan
Requirement yang menjadi berlaku        →  baris baru, status Belum
Requirement yang menjadi tidak berlaku  →  baris dihapus
```

Contoh perubahan dari Non Wiraswasta menjadi Wiraswasta:

```text
PMH-KTP      Lengkap  →  Lengkap   tetap berlaku
PMH-NPWP     Lengkap  →  Lengkap   tetap berlaku
PMH-RKR      Lengkap  →  Lengkap   tetap berlaku
PMH-SLIP     Lengkap  →  dihapus   tidak berlaku pada Wiraswasta
PMH-USAHA    —        →  Belum     menjadi berlaku
PMH-FAKTUR   —        →  Belum     menjadi berlaku
PMH-PROFESI  —        →  Belum     menjadi berlaku
```

Status dokumen yang tetap berlaku **tidak hilang**, dan tidak ada status yang berpindah ke dokumen lain. Ini merupakan alasan utama penggunaan requirement beridentitas.

---

## 9. Verification Process

```text
AO menerima dokumen dari Referral
        ↓
AO memeriksa kelengkapan
        ↓
AO menandai status setiap requirement
        ↓
Sistem menyimpan status
```

Ketentuan:

* Hanya AO pemilik application yang dapat mengubah status dokumen.
* Status dapat diubah bolak-balik antara Belum dan Lengkap.
* Tidak ada urutan wajib dalam penandaan dokumen.
* Kelengkapan dokumen tidak menjadi syarat untuk memulai Application Tracking.

---

## 10. Access

| Role     | Akses                                               |
| -------- | --------------------------------------------------- |
| AO       | Lihat dan ubah, terbatas pada application miliknya   |
| Referral | Lihat saja, terbatas pada application yang dibawanya |
| Admin    | Tidak memiliki akses                                |

Pembatasan tersebut merupakan authorization rule dan harus diterapkan pada backend, bukan hanya pada tampilan.

---

## 11. Open Items

| No | Item                     | Keterangan                                                                                                                                       |
| -- | ------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| 1  | Urutan dokumen pasangan  | Kelompok Pemohon menempatkan Slip Gaji sebelum Rek Koran, sedangkan kelompok Pasangan sebaliknya. Perlu ditetapkan apakah keduanya diseragamkan.  |
| 2  | Pengelolaan katalog      | Perlu ditetapkan apakah Admin dapat menambah atau menonaktifkan Document Requirement, atau katalog bersifat tetap.                                |
| 3  | Riwayat perubahan status | Perlu ditetapkan apakah sistem menyimpan riwayat perubahan status dokumen beserta waktu dan pelakunya.                                            |
| 4  | Catatan verifikasi       | Perlu ditetapkan apakah AO dapat menambahkan catatan pada requirement yang belum terpenuhi.                                                       |

---

## 12. Related Documentation

| Document                  | Purpose                                       |
| ------------------------- | --------------------------------------------- |
| `business.md`             | Business context dan system scope             |
| `actors.md`               | Actors, responsibilities, dan access          |
| `workflow.md`             | End-to-end business workflow                  |
| `application-tracking.md` | Application Tracking dan struktur application |
| `credit-simulation.md`    | Credit Simulation                             |
| `lending.md`              | Lending                                       |