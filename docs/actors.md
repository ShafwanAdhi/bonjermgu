# Actors

Sistem memiliki tiga role utama:

- Admin
- Referral
- Account Officer (AO)

Dokumen ini mendefinisikan tanggung jawab dan hak akses masing-masing role.

---

## 1. Admin

### Responsibilities

- Mengatur Credit Simulation Configuration.
- Mengelola master data yang digunakan perhitungan.
- Mengakses dan mengubah profil Referral.
- Mengakses dan mengubah profil AO.
- Membuat akun AO.
- Mengakses Lending.
- Mengakses Report.

Cakupan konfigurasi meliputi seluruh parameter yang dibutuhkan perhitungan: Product, Upping, master PHPM, tabel asuransi, biaya, ketentuan Down Payment, dan nilai default simulasi. Detailnya berada pada `credit-simulation-configuration.md`.

### Uji Konfigurasi

Admin dapat menjalankan engine perhitungan untuk memeriksa hasil konfigurasi yang diaturnya, pada halaman `/configuration/simulation`.

Ini **bukan** Credit Simulation dalam arti operasional. Tiga pembatas berikut mengikat:

- **Tidak ada data debitur.** Tidak ada field nama, NIK, maupun tanggal lahir. Pemeriksaan konfigurasi tidak memiliki debitur, dan Admin tidak berkepentingan atas data debitur — lihat `business.md` bagian 5.
- **Product dipilih langsung**, bukan diturunkan dari kategori Referral. Yang sedang diuji adalah Product itu sendiri. Halaman ini juga menampilkan kategori Referral mana yang menjangkau Product terpilih, sehingga Product yang tidak terjangkau siapa pun langsung terlihat.
- **Tidak menghasilkan keluaran.** Tidak dapat dicetak, tidak disimpan, dan tidak dapat diserahkan kepada calon debitur.

Halaman ini menampilkan seluruh langkah perhitungan beserta rumusnya, dibaca langsung dari hasil engine tanpa perhitungan ulang. Tujuannya agar setiap angka dapat diperiksa terhadap `credit-simulation.md`.

### Access Matrix

| Feature                         | Admin                |
| ------------------------------- | -------------------- |
| Credit Simulation Configuration | Full                 |
| Master Data                     | Full                 |
| Referral Profile                | View / Edit          |
| AO Profile                      | View / Edit          |
| Create AO Account               | Yes                  |
| Credit Simulation               | No — kecuali Uji Konfigurasi |
| Credit Application              | No operational input |
| Document Verification           | No                   |
| Application Tracking            | No                   |
| Lending                         | Yes                  |
| Report                          | Yes                  |

---

## 2. Referral

### Responsibilities

- Membuat akun Referral sendiri.
- Melakukan Credit Simulation.
- Menunjukkan hasil simulasi kepada calon debitur.
- Mengunduh PDF hasil simulasi apabila calon debitur menyetujui pembiayaan.
- Mengirimkan hasil simulasi kepada AO melalui jalur komunikasi di luar sistem.
- Mengirimkan dokumen debitur kepada AO melalui jalur komunikasi di luar sistem.
- Memantau perkembangan application yang dibawanya.

### Access Matrix

| Feature                         | Referral                    |
| ------------------------------- | --------------------------- |
| Own Referral Account            | Yes                         |
| Credit Simulation               | Yes                         |
| Print Simulation                | Yes                         |
| Credit Simulation Configuration | No                          |
| Master Data                     | No                          |
| Credit Application              | No                          |
| Document Verification           | View only, own applications |
| Application Tracking            | View only, own applications |
| Lending                         | No                          |
| Report                          | No                          |

Referral tidak dapat mengubah status dokumen maupun status tracking. Akses bersifat baca saja.

---

## 3. Account Officer (AO)

### Responsibilities

- Menerima informasi application dari Referral.
- Menginput data awal customer.
- Membuat Credit Application.
- Menginput Amount Finance.
- Memverifikasi dokumen dan mencatat statusnya.
- Melakukan Application Tracking.
- Mengakses riwayat application yang menjadi tanggung jawabnya.

### Access Matrix

| Feature                         | AO                  |
| ------------------------------- | ------------------- |
| Credit Simulation               | No                  |
| Credit Simulation Configuration | No                  |
| Master Data                     | No                  |
| Credit Application              | Create / Manage Own |
| Document Verification           | Own Application     |
| Application Tracking            | Own Application     |
| Application History             | Own Application     |
| Lending                         | No                  |
| Report                          | No                  |

Detail persyaratan dokumen dan Application Tracking berada pada:

- `document-requirement.md`
- `application-tracking.md`

---

## 4. Application Ownership

Setiap Credit Application memiliki AO yang bertanggung jawab terhadap application tersebut.

AO hanya dapat mengakses application yang menjadi tanggung jawabnya.

Contoh:

```text
AO A
├── Application A1
├── Application A2
└── Application A3

AO B
├── Application B1
└── Application B2
```

AO A dapat mengakses:

```text
A1 → Allowed
A2 → Allowed
A3 → Allowed
```

AO A tidak dapat mengakses:

```text
B1 → Forbidden
B2 → Forbidden
```

---

## 5. Referral Visibility

Setiap Credit Application menyimpan Referral yang membawa debitur tersebut.

Referral dapat melihat application yang membawa identitas Referral miliknya.

```text
Referral R
├── Application dengan Referral = R  → View only
└── Application lainnya              → Forbidden
```

Referral melihat status dokumen dan status tracking, tanpa kemampuan mengubahnya.

Pembatasan pada bagian 4 dan 5 merupakan authorization rule dan harus diterapkan pada backend/data-access layer, bukan hanya pada frontend.

---

## 6. Kode Aplikasi

Kode Aplikasi dibangkitkan sistem saat Credit Application dibuat, bersifat unik, dan melekat pada application.

Kode Aplikasi bukan milik Referral. Satu Referral yang membawa beberapa debitur menghasilkan beberapa Kode Aplikasi.

Kode Aplikasi berfungsi sebagai identifier untuk merujuk dan mencari application. Kode Aplikasi bukan sarana autentikasi dan tidak memberikan akses.

Ketentuan lengkap berada pada `application-tracking.md`.

---

## 7. Account Structure

Setiap role disimpan pada tabel terpisah. Atribut antar role tidak dicampur dalam satu tabel.

```text
Admin
Referral
Account Officer
```

### Referral Account

| Field         | Keterangan                      |
| ------------- | ------------------------------- |
| Nama Lengkap  |                                 |
| Tanggal Lahir |                                 |
| Alamat Email  |                                 |
| No. Handphone |                                 |
| Nama User     | Unik                            |
| Kata Sandi    | Lihat bagian 8                  |
| Kategori      | Kategori Referral               |
| Sub-kategori  | Sub-kategori Referral           |
| Instansi      | Instansi Referral               |
| Cabang        | Cabang Referral                 |

Empat atribut terakhir disimpan sebagai kolom relasional terpisah, bukan sebagai satu teks gabungan.

### AO Account

| Field         | Keterangan     |
| ------------- | -------------- |
| Nama Lengkap  |                |
| Tanggal Lahir |                |
| Alamat Email  |                |
| No. Handphone |                |
| Nama User     | Unik           |
| Kata Sandi    | Lihat bagian 8 |

AO tidak memiliki atribut kategori, sub-kategori, instansi, maupun cabang.

---

## 8. Account Creation

### Referral

Referral mendaftar sendiri melalui halaman registrasi. Akun langsung aktif tanpa persetujuan Admin.

```text
Calon Referral
      ↓
Isi Form Registrasi dan Kata Sandi
      ↓
Akun Aktif
```

### AO

Akun AO dibuat oleh Admin. AO tidak dapat mendaftar sendiri.

```text
Admin
  ↓
Buat Akun AO
  ↓
Akun Aktif
```

---

## 9. Kata Sandi

Referral menentukan kata sandinya sendiri saat pendaftaran. Akun AO tetap dibuat oleh Admin dengan kata sandi awal acak yang ditampilkan satu kali saat akun dibuat.

Ketentuan teknis:

- Kata sandi disimpan dalam bentuk hash. Sistem tidak boleh menyimpan kata sandi dalam bentuk terbaca.
- Kata sandi akun tidak berasal dari NIK atau nomor identitas pribadi.
- Percobaan login dibatasi jumlahnya untuk mencegah percobaan berulang.

---

## 10. External Actors

### Calon Debitur

Pihak eksternal yang berinteraksi langsung dengan Referral dalam proses Credit Simulation.

Calon debitur bukan salah satu dari tiga user role sistem dan tidak memiliki akun.

### Credit Risk Team

Pihak eksternal yang terlibat dalam proses Credit Risk dan scoring di luar sistem.

---

## 11. Open Items

| No | Item                  | Keterangan                                                                                 |
| -- | --------------------- | ------------------------------------------------------------------------------------------- |
| 1  | Penentuan AO          | Perlu ditetapkan bagaimana AO menentukan Referral pada saat membuat Credit Application, karena informasi diterima di luar sistem. |
| 2  | Akun Admin            | Perlu ditetapkan bagaimana akun Admin dibuat dan apakah jumlahnya lebih dari satu.          |
| 3  | Penonaktifan akun     | Perlu ditetapkan perlakuan terhadap akun Referral atau AO yang dinonaktifkan, khususnya terhadap application yang terkait. |

---

## 12. Related Documentation

- `business.md` — business context dan system scope.
- `workflow.md` — alur interaksi antar actor.
- `credit-simulation.md` — Credit Simulation.
- `credit-simulation-configuration.md` — konfigurasi Credit Simulation.
- `credit-simulation-test-vectors.md` — nilai acuan perhitungan.
- `document-requirement.md` — Document Verification.
- `application-tracking.md` — Application Tracking.
- `lending.md` — Lending.
