# Master Data Extraction

## 1. Overview

Dokumen ini mencatat bagaimana master data diekstraksi dari draft perhitungan menjadi berkas seeder.

Sumber:

```text
draft_Web_bonjermgu_agt_2026_2.xlsx
```

Berkas hasil:

```text
database/seeders/data/vehicle_prices.csv     26.791 baris harga
database/seeders/data/vehicle_origins.json   47 merk dengan klasifikasi asal
database/seeders/data/referral_master.json   kategori, sub-kategori, instansi, domisili, kelompok usia
```

Seeder:

```text
database/seeders/VehicleSeeder.php
database/seeders/ReferralMasterSeeder.php
```

---

## 2. Hasil Ekstraksi

### Master Kendaraan

| Ukuran           | Passenger | Commercial | Total  |
| ---------------- | --------- | ---------- | ------ |
| Merk             | 21        | 6          | 27     |
| Model            | 2.990     | 1.890      | 4.880  |
| Baris harga      | —         | —          | 26.791 |

Rentang tahun berbeda antar segmen:

| Segmen     | Rentang Tahun | Jumlah Kolom |
| ---------- | ------------- | ------------ |
| Passenger  | 2012 – 2026   | 15           |
| Commercial | 2015 – 2025   | 11           |

Type kendaraan unik: 271

### Master Referral

| Kategori             | Code | Segment | Tier                           | Usage                 | Sub-kategori | Instansi |
| -------------------- | ---- | ------- | ------------------------------ | --------------------- | ------------ | -------- |
| Showroom Mobil Bekas | SRB  | Reguler | Referral                       | Passenger, Commercial | 13           | 0        |
| Karyawan Internal & Captive | KIN  | Reguler | Referral                       | Passenger, Commercial | 5            | 4        |
| Captive Internal     | CIN  | Captive | Khusus Karyawan Bank Mandiri   | Passenger             | 3            | 55       |
| Captive External     | CEX  | Captive | Semangat                       | Passenger, Commercial | 4            | 55       |
| Wira Agency Retail   | WAR  | Reguler | Sales Dealer                   | Passenger, Commercial | 5            | 76       |
| Wira Bisnis          | WBS  | Reguler | Referral                       | Passenger, Commercial | 12           | 0        |
| Others               | OTH  | Reguler | Referral                       | Passenger, Commercial | 0            | 0        |

> **Ketentuan bisnis, dikonfirmasi client:** workbook sumber menuliskan `Referral C2C` untuk SRB. Client mengonfirmasi `Referral C2C` **bukan kategori tersendiri** — tidak ada perbedaan pricing, Product, maupun proses dengan `Referral`. Nilai canonical tunggal adalah `Referral`, berlaku di seluruh sistem.
>
> Ini keputusan bisnis, bukan penyesuaian teknis agar pencarian Product berhasil. Karena keduanya memang satu kategori, sistem tidak memerlukan Product berakhiran `Referral C2C`, tidak memerlukan fallback, dan tidak menyalin rate antar-Product.
>
> **Saat master data diekstrak ulang dari workbook, nilai `Referral C2C` wajib dipetakan ke `Referral`.** Menyalinnya apa adanya akan membuat resolver mencari Product yang tidak ada dan mematikan simulasi untuk seluruh kategori tersebut. Database menolak nilai lama lewat `CHECK`; ketentuannya pada `data-model.md`.
>
> Client juga mengonfirmasi Captive Internal hanya mendukung Passenger.

Total: 7 kategori, 42 sub-kategori, 190 instansi.

Domisili: 13 wilayah Jabodetabek.
Kelompok usia: 4.

---

## 3. Sumber Kolom

### Harga Kendaraan

Sheet `PHPM 1 DT Ext` memuat dua blok statis yang menjadi sumber harga.

| Segmen     | Kolom Kunci | Kolom Harga | Baris     |
| ---------- | ----------- | ----------- | --------- |
| Passenger  | O           | P – AD      | 5 – 3396  |
| Commercial | BP          | BQ – CA     | 5 – 1915  |

Kolom lain pada sheet tersebut bersifat turunan: kolom `K` dan `L` merupakan hasil pencarian dinamis berdasarkan merk dan tahun yang sedang dipilih, sehingga tidak dapat dijadikan sumber.

### Klasifikasi Asal Unit

Sheet `PHPM 2 DT Ext`, kolom D dan E. Klasifikasi Japan atau Non Japan ditetapkan pada level merk.

### Master Referral

Sheet `Data Ext`.

| Data          | Lokasi                                            |
| ------------- | ------------------------------------------------- |
| Kategori      | C5:F11 — nama, segment, type, tier                |
| Kode kategori | C23:D29                                           |
| Sub-kategori  | M5:R25, satu kolom per kategori                   |
| Instansi      | T5:AC25, satu kolom per sub-kategori              |
| Domisili      | F32:F44                                           |
| Kelompok usia | C79:C82                                           |

Pemetaan kolom instansi mengikuti indeks sub-kategori pada formula `Data Ext!K4`.

---

## 4. Aturan Pemecahan Kunci

Kunci pada master berbentuk teks tunggal:

```text
HONDA-BRIO-ALL NEW BRIO RS CVT
```

Aturan pemecahan menjadi merk, type, dan model:

1. Pecah pada tanda hubung. Segmen pertama menjadi merk, segmen kedua menjadi type, sisanya menjadi model.
2. Apabila kunci hanya memiliki dua segmen, cari nama type terpanjang dari daftar type pada sheet `PHPM 2 DT Ext` yang cocok sebagai awalan sisa kunci.
3. Apabila kunci menggunakan titik sebagai pemisah dan tidak memiliki cukup tanda hubung, titik diperlakukan sebagai pemisah.

Aturan 1 menangani mayoritas baris. Aturan 2 menangani 130 baris. Aturan 3 menangani satu baris.

Model yang mengandung tanda hubung tetap utuh karena hanya dua pemisah pertama yang digunakan. Contoh:

```text
MAZDA-CX-5 GT   →   merk MAZDA, type CX, model "5 GT"
```

---

## 5. Normalisasi

Dua merk ditulis tidak konsisten pada sumber dan diseragamkan.

| Sumber   | Menjadi       | Baris Terdampak | Alasan                                    |
| -------- | ------------- | --------------- | ----------------------------------------- |
| MERCEDES | MERCEDES BENZ | 3               | Merk yang sama tertulis dua bentuk        |
| UD       | UD TRUCK      | 111             | Sesuai daftar merk Commercial pada sumber |

Tanpa normalisasi, daftar merk akan menampilkan `MERCEDES` dan `MERCEDES BENZ` sebagai dua pilihan berbeda.

Setelah normalisasi, jumlah merk menjadi 21 Passenger dan 6 Commercial, tepat sama dengan daftar merk pada sheet sumber.

---

## 6. Duplikat

Terdapat 39 kombinasi merk, type, dan model yang muncul lebih dari satu kali.

Kemunculan pertama dipertahankan, sisanya dibuang.

Alasan: `VLOOKUP` pada draft mengambil kecocokan pertama. Mempertahankan kemunculan pertama menghasilkan perilaku yang sama.

Dari 39 duplikat tersebut, 16 memiliki harga identik sehingga tidak berpengaruh. Sisanya memiliki harga berbeda, dan pilihan kemunculan pertama menentukan harga yang berlaku.

---

## 7. Catatan Kualitas Data

| No | Temuan                                                                     | Dampak                                                                  |
| -- | -------------------------------------------------------------------------- | ----------------------------------------------------------------------- |
| 1  | 202 model tidak memiliki harga pada tahun mana pun                          | Model tetap muncul di dropdown namun seluruh hasil tenor bernilai 0     |
| 2  | 23 duplikat dengan harga berbeda                                            | Harga yang berlaku ditentukan urutan pada sumber                        |
| 3  | Segmen Commercial tidak memiliki harga untuk tahun 2012 – 2014 dan 2026     | Kendaraan Commercial pada tahun tersebut tidak dapat disimulasikan      |
| 4  | Daftar type pada sheet cascade tidak selalu cocok dengan kunci pada master  | Cascade dibangun dari kunci master, bukan dari sheet cascade            |

Temuan 1 tidak menghalangi implementasi. Perilakunya sudah ditentukan pada `credit-simulation.md` bagian 15: harga 0 menghasilkan seluruh tenor bernilai 0.

Temuan 4 merupakan alasan cascade merk, type, dan model diturunkan dari kunci master. Dengan cara itu, setiap kombinasi yang dapat dipilih pada antarmuka dijamin memiliki harga, sehingga pencarian tidak pernah gagal.

---

## 8. Ketentuan Seeder

* Seeder bersifat idempoten. Menjalankannya dua kali tidak menghasilkan duplikat.
* Harga dimasukkan dalam potongan seribu baris untuk menghindari pembengkakan memori.
* Hanya harga bernilai lebih dari nol yang disimpan. Ketiadaan baris berarti model tidak memiliki harga pada tahun tersebut.
* Seluruh proses berjalan dalam satu transaksi.

```bash
php artisan db:seed --class=VehicleSeeder
php artisan db:seed --class=ReferralMasterSeeder
```

---

## 9. Pemutakhiran Berikutnya

Master PHPM diperbarui secara manual per baris oleh Admin, sesuai `credit-simulation-configuration.md` bagian 7.

Berkas seeder merupakan muatan awal, bukan mekanisme pemutakhiran berkala. Menjalankan ulang seeder setelah Admin melakukan perubahan akan menimpa harga yang telah disunting.

---

## 10. Related Documentation

| Document                             | Purpose                                    |
| ------------------------------------ | ------------------------------------------ |
| `data-model.md`                      | Struktur tabel tujuan                      |
| `credit-simulation-configuration.md` | Ketentuan master PHPM                      |
| `credit-simulation.md`               | Penggunaan harga dalam perhitungan         |
| `actors.md`                          | Identitas Referral                         |
