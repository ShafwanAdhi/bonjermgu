# Credit Simulation Configuration

## 1. Overview

Dokumen ini mendefinisikan seluruh parameter yang digunakan sistem dalam melakukan Credit Simulation.

Seluruh parameter pada dokumen ini dikelola oleh Admin melalui sistem. Referral tidak dapat melihat maupun mengubah parameter ini.

Konfigurasi bersifat **data**, bukan konstanta program. Perubahan nilai tidak boleh memerlukan perubahan kode.

Konfigurasi berlaku untuk dua produk pembiayaan yang saat ini berada dalam scope:

- Dana Tunai (DTN)
- Pembiayaan Mobil Bekas (UCF)

Ketentuan produk di luar scope dijelaskan pada bagian 15.

---

## 2. Configuration Domains

```text
Admin
  ↓
Credit Simulation Configuration
  ├── Product Configuration
  ├── Upping Configuration
  ├── Vehicle Price (PHPM)
  ├── Vehicle Eligibility
  ├── Insurance Configuration
  ├── Fee Configuration
  ├── Down Payment Configuration
  └── Simulation Default Values
  ↓
Active Configuration
  ↓
Available for Credit Simulation
```

---

## 3. Product Configuration

### Definition

Product merupakan kombinasi segmen, penggunaan unit, dan tier yang menentukan rate serta biaya yang berlaku pada sebuah simulasi.

### Product Attributes

Setiap Product memiliki atribut berikut:

| Attribute      | Description                                   |
| -------------- | --------------------------------------------- |
| Product Name   | Nama produk, unik                             |
| Rate Tenor 12  | Effective rate per annum untuk tenor 12 bulan |
| Rate Tenor 24  | Effective rate per annum untuk tenor 24 bulan |
| Rate Tenor 36  | Effective rate per annum untuk tenor 36 bulan |
| Rate Tenor 48  | Effective rate per annum untuk tenor 48 bulan |
| Rate Tenor 60  | Effective rate per annum untuk tenor 60 bulan |
| DP             | Persentase Down Payment                       |
| Admin Minimal  | Batas bawah biaya administrasi                |
| Admin Maksimal | Batas atas biaya administrasi                 |
| Provisi        | Persentase provisi                            |

Rate tenor yang dikosongkan berarti tenor tersebut **tidak tersedia** untuk produk terkait.

### Product Table

| No  | Product Name                                     | 12       | 24     | 36     | 48     | 60     | DP  | Admin Min | Admin Maks | Provisi |
| --- | ------------------------------------------------ | -------- | ------ | ------ | ------ | ------ | --- | --------- | ---------- | ------- |
| 1   | Captive Passenger Khusus Karyawan Bank Mandiri   | 10,26%   | 9,66%  | 9,39%  | 9,66%  | 10,37% | 5%  | 3.500.000 | 4.400.000  | 0%      |
| 2   | Captive Passenger Khusus SME & Private/Prioritas | 13,74%   | 13,45% | 13,50% | 14,97% | 15,89% | 5%  | 3.500.000 | 4.400.000  | 0%      |
| 3   | Captive Passenger Semangat                       | 16,88%   | 16,36% | 16,29% | 17,62% | 18,04% | 5%  | 3.500.000 | 4.400.000  | 0%      |
| 4   | Captive Passenger Tengah                         | 17,69%   | 16,85% | 16,97% | 17,84% | 18,65% | 5%  | 3.500.000 | 4.400.000  | 0%      |
| 5   | Captive Passenger Cuan                           | 18,47%   | 17,33% | 17,66% | 18,05% | 19,26% | 5%  | 3.500.000 | 4.400.000  | 0%      |
| 6   | Captive Commercial Semangat                      | 19,83%   | 19,07% | 19,31% | 19,21% | —      | 15% | 3.500.000 | 4.400.000  | 0%      |
| 7   | Captive Commercial Tengah                        | 20,35%   | 19,79% | 19,99% | 19,81% | —      | 15% | 3.500.000 | 4.400.000  | 0%      |
| 8   | Captive Commercial Cuan                          | 20,88%   | 20,50% | 20,65% | 20,39% | —      | 15% | 3.500.000 | 4.400.000  | 0%      |
| 9   | Reguler Passenger Referral                       | 17,253%  | 17,50% | 18,00% | 18,69% | 19,07% | 5%  | 3.750.000 | 5.350.000  | 0%      |
| 10  | Reguler Passenger TOMI Spesial Referral          | 10,00%   | 10,25% | 10,50% | 10,75% | 11,00% | 5%  | 3.750.000 | 5.350.000  | 0%      |
| 11  | Reguler Commercial Referral                      | 20,35%   | 19,55% | 19,76% | 19,63% | —      | 15% | 3.750.000 | 5.350.000  | 0%      |
| 12  | Reguler Passenger Sales Dealer                   | 18,47%   | 17,33% | 17,66% | 18,05% | 19,26% | 15% | 3.500.000 | 5.350.000  | 0%      |
| 13  | Reguler Commercial Sales Dealer                  | 20,88%   | 20,50% | 20,65% | 20,39% | —      | 15% | 3.500.000 | 5.350.000  | 0%      |
| 14  | Captive Passenger Authorized Showroom            | 14,00%   | 14,00% | 14,00% | 16,00% | 16,00% | 20% | 4.700.000 | 4.700.000  | 0%      |
| 15  | Captive Passenger Low Rate                       | 14,4699% | 14,68% | 14,55% | 16,23% | 16,50% | 10% | 4.700.000 | 4.700.000  | 0%      |
| 16  | Captive Passenger Authorized Reguler             | 16,22%   | 16,43% | 16,24% | 17,51% | 17,66% | 10% | 4.700.000 | 4.700.000  | 0%      |
| 17  | Captive Passenger High Benefit                   | 17,97%   | 18,16% | 17,92% | 19,43% | 19,18% | 10% | 4.700.000 | 4.700.000  | 0%      |

Tabel di atas merupakan nilai awal. Admin dapat menambah, mengubah, dan menonaktifkan Product.

### Product Resolution

Product tidak dipilih oleh Referral. Product ditentukan sistem berdasarkan kombinasi berikut:

```text
Segmen Referral + Penggunaan Unit + Tier
        ↓
   Product Name
        ↓
   Product Configuration
```

Contoh:

```text
Reguler + Passenger + Sales Dealer
        ↓
Reguler Passenger Sales Dealer
```

Segmen dan tier berasal dari kategori Referral. Penggunaan Unit berasal dari input simulasi dan dibatasi oleh eligibility kategori. Captive Internal hanya mengizinkan Passenger; kategori awal lainnya mengizinkan Passenger dan Commercial.

Setiap kombinasi kategori aktif dan penggunaan unit yang diizinkan wajib mempunyai Product aktif. Konfigurasi Admin ditolak apabila ketentuan ini tidak terpenuhi.

---

## 4. Upping Configuration

Upping merupakan penambahan nilai di atas parameter dasar Product.

Upping dikonfigurasi **per Product**.

| Parameter  | Description                              |
| ---------- | ---------------------------------------- |
| Up ACP     | Pengali tambahan atas rate ACP           |
| Up Rate    | Penambahan atas flat rate hasil konversi |
| Up Admin   | Penambahan atas biaya administrasi       |
| Up Provisi | Penambahan atas persentase provisi       |

Nilai default seluruh parameter upping adalah 0.

Khusus Up ACP, nilai ditetapkan per kelompok usia debitur sebagaimana bagian 9.9.

Upping bukan input Referral dan tidak ditampilkan pada hasil simulasi.

---

## 5. Tenor

Tenor bersifat tetap dan tidak dapat dikonfigurasi.

```text
12 bulan
24 bulan
36 bulan
48 bulan
60 bulan
```

Setiap simulasi menghasilkan lima baris hasil sesuai kelima tenor di atas, kecuali tenor yang tidak tersedia pada Product terkait.

---

## 6. Rate Configuration

### Type Angsuran

Sistem mengenal dua tipe angsuran:

| Code | Description          |
| ---- | -------------------- |
| ADDB | Angsuran di belakang |
| ADDM | Angsuran di muka     |

### Rate Conversion

Rate pada Product Configuration merupakan **effective rate per annum**. Perhitungan angsuran menggunakan **flat rate**.

Konversi dilakukan sistem:

```text
Effective Rate (p.a)
        ↓
Konversi berdasarkan Type Angsuran
        ↓
Flat Rate
        ↓
Flat Rate + Up Rate
```

Ketentuan konversi:

- ADDM menggunakan pembayaran di awal periode.
- ADDB menggunakan pembayaran di akhir periode.

Hasil konversi berlaku untuk jumlah bulan sesuai tenor.

---

## 7. Vehicle Price (PHPM)

PHPM merupakan master harga kendaraan yang menjadi acuan nilai unit.

### Key

Master PHPM **tersegmentasi berdasarkan Penggunaan Unit**. Daftar Merk, Type, dan Model untuk Passenger berbeda dari Commercial.

Setiap baris PHPM diidentifikasi oleh kombinasi:

```text
Penggunaan Unit + Merk + Type Kendaraan + Model Kendaraan + Tahun Kendaraan
```

Contoh kunci pencarian:

```text
Passenger | HONDA-BRIO-ALL NEW BRIO RS CVT | 2017
```

Kombinasi tersebut harus unik.

Harga disimpan **per tahun kendaraan**. Satu model memiliki harga berbeda untuk setiap tahun, dan dapat tidak memiliki harga sama sekali pada tahun tertentu.

### Attributes

| Attribute        | Description          |
| ---------------- | -------------------- |
| Merk             | Merk kendaraan       |
| Type Kendaraan   | Type kendaraan       |
| Model Kendaraan  | Model kendaraan      |
| Tahun Kendaraan  | Tahun produksi       |
| Harga            | Harga acuan          |
| Klasifikasi Asal | Japan atau Non Japan |

### Maintenance

Data PHPM diinput dan diperbarui secara manual per baris oleh Admin.

Nilai harga yang tidak tersedia diperlakukan sebagai 0 dan menyebabkan simulasi tidak menghasilkan nilai pembiayaan.

### Klasifikasi Asal

Klasifikasi Japan atau Non Japan ditetapkan pada level Merk, bukan per model.

Klasifikasi ini memengaruhi ketentuan Down Payment pada Dana Tunai.

---

## 8. Vehicle Eligibility

Kelayakan unit ditentukan oleh usia kendaraan pada akhir masa pembiayaan.

Ketentuan:

```text
(Batas Usia Maksimal - Tenor dalam tahun) - (Tahun Berjalan - Tahun Kendaraan) >= 1
```

| Parameter           | Nilai |
| ------------------- | ----- |
| Batas Usia Maksimal | 16    |

Batas Usia Maksimal merupakan parameter konfigurasi.

Apabila hasil perhitungan kurang dari 1, tenor tersebut tidak tersedia dan hasil simulasi untuk tenor tersebut bernilai 0.

---

## 9. Insurance Configuration

### 9.1 Coverage Type

| Coverage                           | Description                                           |
| ---------------------------------- | ----------------------------------------------------- |
| Comprehensive All Tenor            | Comprehensive sepanjang tenor                         |
| Comprehensive 1 tahun, sisanya TLO | Comprehensive pada tahun pertama, TLO pada sisa tenor |
| TLO All Tenor                      | TLO sepanjang tenor                                   |

Coverage default per produk pembiayaan merupakan parameter konfigurasi.

### 9.2 Wilayah

Rate asuransi ditetapkan per wilayah. Wilayah yang saat ini dikonfigurasi adalah **Wilayah 2**.

### 9.3 Batas Atas dan Batas Bawah

Setiap rate memiliki dua varian. Varian yang digunakan merupakan parameter konfigurasi.

### 9.4 Casco Rate

Rate ditentukan oleh band harga unit, penggunaan unit, dan coverage.

**Batas Atas**

| Band Harga (juta) | Passenger Comprehensive | Passenger TLO | Commercial Comprehensive | Commercial TLO |
| ----------------- | ----------------------- | ------------- | ------------------------ | -------------- |
| 0–125             | 3,59%                   | 0,78%         | 3,69%                    | 0,85%          |
| >125–200          | 2,72%                   | 0,53%         | 2,82%                    | 0,57%          |
| >200–400          | 2,29%                   | 0,42%         | 2,39%                    | 0,45%          |
| >400–800          | 1,32%                   | 0,30%         | 1,42%                    | 0,33%          |
| >800              | 1,16%                   | 0,24%         | 1,26%                    | 0,26%          |

**Batas Bawah**

| Band Harga (juta) | Passenger Comprehensive | Passenger TLO | Commercial Comprehensive | Commercial TLO |
| ----------------- | ----------------------- | ------------- | ------------------------ | -------------- |
| 0–125             | 3,26%                   | 0,65%         | 3,36%                    | 0,72%          |
| >125–200          | 2,47%                   | 0,44%         | 2,57%                    | 0,48%          |
| >200–400          | 2,08%                   | 0,38%         | 2,18%                    | 0,41%          |
| >400–800          | 1,20%                   | 0,25%         | 1,30%                    | 0,28%          |
| >800              | 1,05%                   | 0,20%         | 1,15%                    | 0,22%          |

Band harga dihitung terhadap Sum Insured.

### 9.5 Sum Insured Schedule

Sum Insured menurun mengikuti tahun pembiayaan.

| Tahun | Persentase |
| ----- | ---------- |
| 1     | 100%       |
| 2     | 90%        |
| 3     | 80%        |
| 4     | 70%        |
| 5     | 70%        |

### 9.6 Loading

Loading merupakan penambahan atas premi Casco berdasarkan usia kendaraan. Loading hanya berlaku pada coverage Comprehensive.

| Usia Kendaraan | Loading |
| -------------- | ------- |
| 0–5 tahun      | 0%      |
| 6 tahun        | 5%      |
| 7 tahun        | 10%     |
| 8 tahun        | 15%     |
| 9 tahun        | 20%     |
| 10 tahun       | 25%     |
| 11–14 tahun    | 0%      |

Nilai pada usia 11 tahun ke atas memerlukan konfirmasi. Lihat bagian 16.

### 9.7 Perluasan

Perluasan hanya berlaku pada coverage Comprehensive. Seluruh perluasan bersifat opsional dengan nilai default Tidak.

| Perluasan | Rate  | Dasar Perhitungan                      |
| --------- | ----- | -------------------------------------- |
| Banjir    | 0,10% | Sum Insured                            |
| Gempa     | 0,10% | Sum Insured                            |
| Huru-hara | 0,05% | Sum Insured                            |
| Teroris   | 0,05% | Sum Insured                            |
| Pengemudi | 0,50% | Nilai pertanggungan                    |
| Penumpang | 0,10% | Nilai pertanggungan × jumlah penumpang |

### 9.8 TJH

TJH dihitung berjenjang atas nilai pertanggungan yang dipilih.

| Lapisan            | Rate  |
| ------------------ | ----- |
| 25.000.000 pertama | 1,00% |
| 25.000.000 kedua   | 0,50% |
| 25.000.000 ketiga  | 0,25% |
| Sisa               | 0,15% |

Pilihan nilai TJH: 0 sampai 50.000.000 dengan kelipatan 5.000.000.

### 9.9 ACP

ACP dihitung berdasarkan tenor, dengan pembeda usia debitur pada faktor upping.

**Rate Dasar**

| Tenor (tahun) | Rate Dasar |
| ------------- | ---------- |
| 1             | 0,50%      |
| 2             | 1,00%      |
| 3             | 1,53%      |
| 4             | 2,24%      |
| 5             | 2,88%      |

**Up ACP per Kelompok Usia**

| Kelompok Usia | Up ACP |
| ------------- | ------ |
| 18-35 tahun   | 0,3    |
| 36-45 tahun   | 0,3    |
| 46-50 tahun   | 0,3    |
| 51-60 tahun   | 0,8    |

**Perhitungan**

```text
Rate ACP = Rate Dasar tenor × (1 + Up ACP kelompok usia)
```

**Rate Hasil**

| Tenor (tahun) | 18-35 / 36-45 / 46-50 | 51-60 tahun |
| ------------- | --------------------- | ----------- |
| 1             | 0,650%                | 0,900%      |
| 2             | 1,300%                | 1,800%      |
| 3             | 1,989%                | 2,754%      |
| 4             | 2,912%                | 4,032%      |
| 5             | 3,744%                | 5,184%      |

Kelompok usia 18-35, 36-45, dan 46-50 menghasilkan premi ACP yang identik. Perbedaan hanya terjadi pada kelompok 51-60.

Rate Dasar dan Up ACP keduanya merupakan parameter konfigurasi.

Ketentuan berlaku tidaknya ACP per produk pembiayaan merupakan parameter konfigurasi.

Kelompok usia debitur merupakan master data:

```text
18-35 tahun
36-45 tahun
46-50 tahun
51-60 tahun
```

### 9.10 Garansi Mesin

| Parameter | Nilai     |
| --------- | --------- |
| Biaya     | 1.500.000 |
| Default   | Ya        |

Nilai bersifat flat, tidak bergantung tenor maupun harga unit.

---

## 10. Fee Configuration

### 10.1 Administrasi

Biaya administrasi diambil dari atribut Admin Maksimal pada Product Configuration, ditambah Up Admin.

### 10.2 Provisi

Provisi dihitung atas nilai pembiayaan, menggunakan persentase Provisi pada Product Configuration ditambah Up Provisi.

### 10.3 Fiducia

Biaya fiducia ditentukan berjenjang berdasarkan harga unit.

| Harga Unit                   | Biaya     |
| ---------------------------- | --------- |
| 0 – 25.000.000               | 350.000   |
| >25.000.000 – 50.000.000     | 375.000   |
| >50.000.000 – 100.000.000    | 400.000   |
| >100.000.000 – 250.000.000   | 500.000   |
| >250.000.000 – 500.000.000   | 750.000   |
| >500.000.000 – 1.000.000.000 | 1.150.000 |
| >1.000.000.000               | 2.250.000 |

---

## 11. Down Payment Configuration

### Dana Tunai

| Kondisi                                                         | Net DP |
| --------------------------------------------------------------- | ------ |
| STNK atas nama orang lain, atau Commercial, atau unit Non Japan | 15%    |
| Selain kondisi di atas                                          | 5%     |

### Pembiayaan Mobil Bekas

| Type Debitur            | Net DP        |
| ----------------------- | ------------- |
| Perorangan (Wiraswasta) | 30% + Deviasi |
| Selain di atas          | 10% + Deviasi |

### Deviasi

Deviasi merupakan selisih ketika harga input melebihi harga PHPM.

```text
Deviasi (Rp) = Harga Input - Harga PHPM   (jika positif)
Deviasi (%)  = Deviasi (Rp) / Harga Input
```

Pada Dana Tunai harga berasal dari PHPM sehingga Deviasi selalu 0.

Pada Pembiayaan Mobil Bekas harga berasal dari input Harga Pasar sehingga Deviasi dapat muncul.

---

## 12. Simulation Default Values

Parameter berikut memiliki nilai default yang dikonfigurasi Admin. Referral tidak dapat mengubahnya pada saat simulasi.

| Parameter                  | Default |
| -------------------------- | ------- |
| Deposit / Titipan Angsuran | 0       |
| BBNKB                      | 0       |
| PKB                        | 0       |
| Faktur                     | 0       |
| Banjir                     | Tidak   |
| Gempa                      | Tidak   |
| Huru-hara                  | Tidak   |
| Teroris                    | Tidak   |
| TJH                        | 0       |
| Pengemudi                  | 0       |
| Penumpang                  | 0       |
| Jumlah Penumpang           | 0       |
| Garansi Mesin              | Ya      |

---

## 13. Refund Configuration

Refund berlaku pada Pembiayaan Mobil Bekas.

| Komponen        | Persentase Dasar | Persentase Refund |
| --------------- | ---------------- | ----------------- |
| Refund Asuransi | 10%              | 100%              |
| Refund Bunga    | Up Rate          | 80%               |
| Refund Provisi  | Up Provisi       | 80%               |
| Refund Admin    | Up Admin         | 80%               |

Seluruh persentase merupakan parameter konfigurasi.

---

## 14. Configuration Lifecycle

```text
Admin
  ↓
Ubah Configuration
  ↓
Active Configuration
  ↓
Credit Simulation berikutnya menggunakan nilai baru
```

Ketentuan:

- Hanya terdapat satu Active Configuration pada satu waktu.
- Perubahan konfigurasi tidak mengubah PDF hasil simulasi yang telah diunduh sebelumnya.
- Hasil Credit Simulation bersifat estimasi dan tidak mengikat.

---

## 15. Out of Scope

Konfigurasi untuk produk berikut belum tersedia dan tidak termasuk dalam implementasi saat ini:

- Pembiayaan Emas / Logam Mulia (LMF)
- Pembiayaan Mobil Baru (NCF)

Kedua produk tersebut belum memiliki ketentuan perhitungan. Sistem tidak boleh menampilkan simulasi untuk kedua produk tersebut sampai ketentuannya ditetapkan.

---

## 16. Open Configuration Items

Butir berikut memerlukan penetapan sebelum implementasi.

| No  | Item                          | Keterangan                                                                                                                                                  |
| --- | ----------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Atribut DP pada Product       | Atribut DP pada Product Configuration tidak digunakan dalam perhitungan. Ketentuan yang berlaku adalah bagian 11. Perlu ditetapkan mana yang menjadi acuan. |
| 2   | Admin Minimal                 | Perhitungan menggunakan Admin Maksimal. Fungsi Admin Minimal perlu ditetapkan.                                                                              |
| 3   | Loading usia 11 tahun ke atas | Nilai loading kembali menjadi 0% setelah usia 10 tahun. Perlu konfirmasi apakah disengaja.                                                                  |
| 4   | Batas Harga Pasar             | Perlu ditetapkan apakah input Harga Pasar pada Pembiayaan Mobil Bekas memiliki batas maksimal terhadap PHPM.                                                |
| 5   | Wilayah asuransi              | Hanya Wilayah 2 yang dikonfigurasi. Perlu ditetapkan apakah wilayah lain akan ditambahkan.                                                                  |

---

## 17. Related Documentation

| Document                  | Purpose                                 |
| ------------------------- | --------------------------------------- |
| `business.md`             | Business context dan system scope       |
| `actors.md`               | Actors, responsibilities, dan access    |
| `workflow.md`             | End-to-end business workflow            |
| `credit-simulation.md`    | Input, perhitungan, dan output simulasi |
| `document-requirement.md` | Document requirements dan verification  |
| `application-tracking.md` | Application Tracking                    |
| `lending.md`              | Lending                                 |
