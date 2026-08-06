# Application Tracking

## 1. Overview

Application Tracking merupakan pencatatan perkembangan permohonan pembiayaan melalui sebelas tahapan yang telah ditentukan.

Sebagian besar aktivitas pada setiap tahapan berlangsung **di luar sistem**. Sistem berfungsi mencatat status, bukan menjalankan prosesnya.

Pengisian status dilakukan oleh AO yang bertanggung jawab atas application terkait. Referral dapat melihat status tanpa dapat mengubahnya.

---

## 2. Application Record

Setiap Credit Application menyimpan data berikut.

### Identitas Application

| Field         | Keterangan                          |
| ------------- | ----------------------------------- |
| Kode Aplikasi     | Kode unik, dibangkitkan sistem  |
| Produk Pembiayaan | Dana Tunai atau Pembiayaan Mobil Bekas |
| AO                | AO yang bertanggung jawab       |

### Data Debitur

| Field         | Keterangan     |
| ------------- | -------------- |
| Nama          | Nama debitur   |
| NIK           | NIK debitur    |
| Tanggal Lahir | Tanggal lahir  |

Tidak ada data debitur lain yang disimpan, sesuai ketentuan pada `business.md`.

### Data Referral

Referral disimpan sebagai empat kolom relasional.

| Field         | Contoh                      |
| ------------- | --------------------------- |
| Kategori      | Wira Agency Retail          |
| Sub-kategori  | Sales Authorized Dealer     |
| Instansi      | Toyota                      |
| Cabang        | —                           |

### Field Penentu Dokumen

| Field                                | Keterangan                        |
| ------------------------------------ | --------------------------------- |
| Type Debitur                         | Menentukan daftar dokumen         |
| Konfirmasi Sumber Penghasilan Lainnya | Menentukan dokumen pasangan      |

### Nilai Pembiayaan

| Field           | Keterangan                                  |
| --------------- | ------------------------------------------- |
| Amount Finance  | Nilai LTV, diinput manual oleh AO           |

Amount Finance **tidak diambil dari hasil Credit Simulation**. Nilai final dapat berbeda dari estimasi simulasi karena ditentukan setelah verifikasi profil debitur dan kondisi kendaraan.

### Tanggal

| Field           | Keterangan                                        |
| --------------- | ------------------------------------------------- |
| Tanggal Go Live | Tanggal tahapan 11 ditandai selesai               |

Tanggal Go Live diisi sistem, bukan diinput manual. Ketentuannya dijelaskan pada bagian 8.

### Status

| Kelompok        | Penyimpanan                                        |
| --------------- | -------------------------------------------------- |
| Status Dokumen  | Satu baris per Document Requirement yang berlaku   |
| Status Tracking | Sebelas tahapan tetap                              |

Status dokumen tidak disimpan sebagai slot posisional. Ketentuannya dijelaskan pada `document-requirement.md`.

---

## 3. Kode Aplikasi

Kode Aplikasi dibangkitkan sistem saat Credit Application dibuat.

| Ketentuan | Nilai                        |
| --------- | ---------------------------- |
| Panjang   | 6 karakter                   |
| Isi       | Huruf dan angka              |
| Pola      | Acak                         |
| Keunikan  | Wajib unik                   |

Contoh: `9anxfq`

Kode Aplikasi bersifat per application, bukan per Referral. Satu Referral yang membawa beberapa debitur menghasilkan beberapa Kode Aplikasi.

Kode Aplikasi berfungsi sebagai **identifier**, bukan sebagai **authentication**.

Kode digunakan untuk merujuk dan mencari application. Mengetahui kode tidak memberikan akses apa pun. Setiap permintaan tetap harus melalui autentikasi dan pemeriksaan kepemilikan.

Sistem tidak boleh menyediakan endpoint yang mengembalikan data application hanya berbekal Kode Aplikasi.

---

## 4. Tracking Stages

Terdapat sebelas tahapan tetap.

| No | Tahapan                                              |
| -- | ---------------------------------------------------- |
| 1  | Verifikasi, Validasi dan kelengkapan data permohonan |
| 2  | Survey Domisili & Tempat usaha/Kantor                |
| 3  | Cek fisik Kendaraan                                  |
| 4  | Laporan Hasil Survey                                 |
| 5  | Proses Aplikasi                                      |
| 6  | Konfirmasi Debitur by phone & Scoring Credit         |
| 7  | Permohonan Persetujuan Pembiayaan                    |
| 8  | Persetujuan Pembiayaan (PO)                          |
| 9  | Verifikasi, Validasi dan kelengkapan Jaminan (BPKB)  |
| 10 | Konfirmasi Debitur by phone                          |
| 11 | Golive & Payment                                     |

Jumlah dan nama tahapan bersifat tetap dan tidak dapat dikonfigurasi.

---

## 5. Status Codes

| Status  | Arti                  | Tampilan |
| ------- | --------------------- | -------- |
| Belum   | Tahapan belum selesai | Silang   |
| Selesai | Tahapan telah selesai | Ceklis   |

Seluruh sebelas tahapan berlaku pada setiap application. Status awal setiap tahapan adalah **Belum**.

Tidak ada status ketiga. Berbeda dengan dokumen, seluruh tahapan selalu berlaku sehingga tidak diperlukan penanda tidak berlaku.

---

## 6. Ketentuan Pengisian

### Bebas Urutan

Tahapan tidak harus diselesaikan berurutan.

```text
Tahap 1 → Selesai
Tahap 2 → Belum
Tahap 3 → Selesai
Tahap 4 → Belum
```

Kombinasi di atas merupakan kondisi yang sah. Sistem tidak boleh menolak pengisian tahapan yang tahapan sebelumnya belum selesai.

Ketentuan ini mengikuti kenyataan operasional. Sebagian aktivitas berjalan paralel di luar sistem, sehingga urutan penyelesaian tidak selalu sesuai nomor tahapan.

### Dapat Dibatalkan

Status dapat diubah bolak-balik antara Belum dan Selesai.

### Tidak Bergantung Dokumen

Kelengkapan dokumen bukan syarat untuk mengisi tahapan mana pun. Kedua kelompok status berjalan independen.

---

## 7. Credit Risk Process

Sebagian tahapan mewakili aktivitas yang dilakukan pihak di luar sistem.

```text
Application
     ↓
AO
     ↓
Credit Risk Team memproses di luar sistem
     ↓
AO menerima hasil
     ↓
AO mencatat status tahapan
```

Sistem tidak menyimpan hasil scoring, catatan survey, maupun dokumen persetujuan. Sistem hanya mencatat bahwa tahapan tersebut telah selesai.

---

## 8. Go Live & Payment

Tahapan 11 merupakan akhir dari proses application.

```text
Tahap 11 → Selesai
      ↓
Sistem mencatat Tanggal Go Live
      ↓
Application dinyatakan Go Live
      ↓
Application masuk perhitungan Actual Lending
```

Sebelum tahapan 11 berstatus Selesai, application diperhitungkan sebagai Pipe Line.

### Ketentuan Tanggal Go Live

| Kejadian                            | Perlakuan                          |
| ----------------------------------- | ---------------------------------- |
| Tahap 11 ditandai Selesai           | Tanggal Go Live diisi tanggal saat itu |
| Tahap 11 dikembalikan menjadi Belum | Tanggal Go Live dikosongkan        |
| Tahap 11 ditandai Selesai kembali   | Tanggal Go Live diisi ulang        |

Tanggal Go Live menjadi dasar pemotongan periode pada pelaporan Lending.

Ketentuan perhitungan dijelaskan pada `lending.md`.

---

## 9. Access

| Role     | Akses                                                |
| -------- | ---------------------------------------------------- |
| AO       | Lihat dan ubah, terbatas pada application miliknya    |
| Referral | Lihat saja, terbatas pada application yang dibawanya  |
| Admin    | Tidak memiliki akses                                 |

### Application Ownership

Setiap application memiliki satu AO penanggung jawab. AO hanya dapat mengakses application miliknya.

```text
AO A
├── Application A1  → Allowed
├── Application A2  → Allowed
└── Application A3  → Allowed

AO B
├── Application B1  → Forbidden bagi AO A
└── Application B2  → Forbidden bagi AO A
```

### Referral Visibility

Referral melihat application yang membawa identitas Referral miliknya.

```text
Referral R
├── Application dengan Referral = R  → Read only
└── Application lainnya              → Forbidden
```

Halaman Referral menampilkan status dokumen dan status tracking tanpa kontrol pengubahan.

Seluruh pembatasan di atas merupakan authorization rule dan harus diterapkan pada backend, bukan hanya pada tampilan.

---

## 10. Tracking View

Tampilan tracking memuat:

```text
Kode Aplikasi
Nama Debitur
Nama AO
Type Debitur
Konfirmasi Sumber Penghasilan Lainnya
Produk Pembiayaan
Status seluruh Document Requirement yang berlaku
Status 11 tahapan
Amount Finance
Tanggal Go Live, apabila telah tercapai
```

Tampilan untuk AO dan Referral memuat informasi yang sama. Perbedaannya hanya pada kemampuan mengubah status.

---

## 11. Open Items

| No | Item                    | Keterangan                                                                                       |
| -- | ----------------------- | ------------------------------------------------------------------------------------------------ |
| 1  | Riwayat perubahan status | Perlu ditetapkan apakah sistem menyimpan riwayat perubahan status tahapan beserta waktu dan pelakunya. |
| 2  | Pemindahan AO           | Perlu ditetapkan apakah application dapat dipindahkan ke AO lain, dan bagaimana perlakuan status yang telah tercatat. |
| 3  | Pembatalan application  | Perlu ditetapkan apakah terdapat status ditolak atau dibatalkan, karena saat ini application hanya dapat berjalan atau menggantung. |
| 4  | Perubahan produk        | Produk Pembiayaan terkunci setelah Go Live. Perlu ditetapkan apakah perlu dikunci lebih awal, misalnya setelah tahap Persetujuan Pembiayaan. |
| 5  | Pengisian Amount Finance | Perlu ditetapkan kapan AO wajib mengisi Amount Finance, dan apakah nilai tersebut dapat diubah setelah Go Live tercapai. |
| 6  | Jumlah unit             | Perlu ditetapkan apakah satu application selalu bernilai satu unit, karena laporan Lending menghitung unit dan Amount Finance secara terpisah. |

---

## 12. Related Documentation

| Document                             | Purpose                                    |
| ------------------------------------ | ------------------------------------------ |
| `business.md`                        | Business context dan system scope          |
| `actors.md`                          | Actors, responsibilities, dan access       |
| `workflow.md`                        | End-to-end business workflow               |
| `document-requirement.md`            | Document requirements dan verification     |
| `credit-simulation.md`               | Credit Simulation                          |
| `credit-simulation-configuration.md` | Parameter dan konfigurasi perhitungan      |
| `lending.md`                         | Lending                                    |
