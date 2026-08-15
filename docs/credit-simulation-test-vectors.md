# Credit Simulation Test Vectors

## 1. Overview

Dokumen ini berisi skenario uji beserta nilai acuan untuk memastikan perhitungan sistem menghasilkan angka yang sama dengan draft perhitungan.

Seluruh nilai pada dokumen ini diambil dari draft perhitungan dan telah diverifikasi terhadap implementasi yang dibangun dari `credit-simulation.md`.

Pada skenario S6, nilai acuan **sengaja berbeda** dari draft. Draft mengandung cacat yang telah diputuskan untuk diperbaiki di sistem. Nilai acuan yang berlaku adalah nilai hasil perbaikan.

Implementasi dianggap benar apabila menghasilkan nilai yang **sama persis**, tanpa toleransi, pada seluruh angka berpembulatan.

---

## 2. Hasil Verifikasi

| Skenario                       | Titik Uji | Cocok |
| ------------------------------ | --------- | ----- |
| S1 — Dana Tunai ADDB           | 55        | 55    |
| S2 — Mobil Bekas ADDB          | 60        | 60    |
| S3 — Dana Tunai ADDM           | 25        | 25    |
| S4 — Mobil Bekas ADDM          | 25        | 25    |
| S5 — Mobil Bekas dengan Deviasi | 30       | 30    |
| S6 — Unit tidak layak          | 35        | 25 + 10 koreksi |
| S7 — Variasi usia debitur      | 20        | 20    |
| **Total**                      | **250**   | **240 + 10 koreksi** |

Sepuluh titik koreksi pada S6 seluruhnya berada pada komponen Refund Pembiayaan Mobil Bekas.

---

## 3. Konfigurasi Dasar

Konfigurasi berikut berlaku pada seluruh skenario kecuali dinyatakan berbeda.

### Parameter Global

| Parameter             | Nilai |
| --------------------- | ----- |
| Tahun Berjalan        | 2026  |
| Batas Usia Maksimal   | 16    |
| Wilayah Asuransi      | Wilayah 2 |
| Varian Rate Asuransi  | Batas Bawah |

### Master PHPM

| Field            | Nilai                        |
| ---------------- | ---------------------------- |
| Penggunaan Unit  | Passenger                    |
| Merk             | HONDA                        |
| Type Kendaraan   | BRIO                         |
| Model Kendaraan  | ALL NEW BRIO RS CVT          |
| Tahun Kendaraan  | 2017                         |
| Harga PHPM       | 110.000.026                  |
| Klasifikasi Asal | Japan                        |

Harga OTR hasil pembulatan: **110.000.000**

### Nilai Default Simulasi

| Parameter | Nilai |
| --------- | ----- |
| Deposit / Titipan Angsuran | 0 |
| BBNKB / PKB / Faktur | 0 |
| Banjir / Gempa / Huru-hara / Teroris | Tidak |
| TJH / Pengemudi / Penumpang | 0 |
| Garansi Mesin | Ya |

---

## 4. Skenario S1 — Dana Tunai ADDB

### Input

| Field              | Nilai                                |
| ------------------ | ------------------------------------ |
| Product            | Reguler Passenger Sales Dealer       |
| Type Debitur       | Perorangan (Wiraswasta)              |
| Usia Debitur       | 36-45 tahun                          |
| Penggunaan Unit    | Passenger                            |
| STNK atas nama     | Pribadi (milik sendiri)              |
| Type Angsuran      | ADDB                                 |
| Asuransi           | Comprehensive 1 tahun, sisanya TLO   |
| Up Rate / Admin / Provisi | 0                             |
| Up ACP             | 0,3                                  |
| Dana yang dibutuhkan (Mode B) | 60.000.000                |

### Nilai Antara

| Tenor | Effective Rate | Flat Rate           | Bunga Jual %        |
| ----- | -------------- | ------------------- | ------------------- |
| 12    | 18,47%         | 0,10284584815780895 | 0,10284584815780895 |
| 24    | 17,33%         | 0,09521169008433106 | 0,19042338016866212 |
| 36    | 17,66%         | 0,09845143022123226 | 0,29535429066369680 |
| 48    | 18,05%         | 0,10281359899688652 | 0,41125439598754610 |
| 60    | 19,26%         | 0,11300587506610578 | 0,56502937533052890 |

Net DP: 5% — 5.500.000
LTV: 95% — 104.500.000

| Tenor | Total Asuransi | Total Bayar Pertama |
| ----- | -------------- | ------------------- |
| 12    | 6.518.200      | 17.868.200          |
| 24    | 7.876.700      | 19.226.700          |
| 36    | 9.206.600      | 20.556.600          |
| 48    | 10.722.400     | 22.072.400          |
| 60    | 12.138.100     | 23.488.100          |

### Output Mode A

| Tenor | Pencairan Maksimal | Angsuran  |
| ----- | ------------------ | --------- |
| 12    | 92.131.800         | 9.604.000 |
| 24    | 90.773.300         | 5.184.000 |
| 36    | 89.443.400         | 3.761.000 |
| 48    | 87.927.600         | 3.073.000 |
| 60    | 86.511.900         | 2.726.000 |

### Output Mode B

| Tenor | Total DP   | DP Net     | LTV        | Angsuran  |
| ----- | ---------- | ---------- | ---------- | --------- |
| 12    | 12.368.200 | 37.631.826 | 72.368.200 | 6.651.000 |
| 24    | 13.726.700 | 36.273.326 | 73.726.700 | 3.657.000 |
| 36    | 15.056.600 | 34.943.426 | 75.056.600 | 2.701.000 |
| 48    | 16.572.400 | 33.427.626 | 76.572.400 | 2.252.000 |
| 60    | 17.988.100 | 32.011.926 | 77.988.100 | 2.035.000 |

Mode B menggunakan Harga PHPM **110.000.026**, bukan Harga OTR.

---

## 5. Skenario S2 — Mobil Bekas ADDB

### Input

| Field                   | Nilai                          |
| ----------------------- | ------------------------------ |
| Product                 | Captive Passenger Low Rate     |
| Type Debitur            | Perorangan (Non Wiraswasta)    |
| Penggunaan Unit         | Passenger                      |
| Harga Pasar             | 110.000.000                    |
| Type Angsuran           | ADDB                           |
| Asuransi                | TLO All Tenor                  |
| Up Rate                 | 0,005                          |
| Up Admin / Provisi / ACP | 0                             |
| Total DP dikehendaki (Mode B) | 60.000.000               |

### Nilai Antara

| Tenor | Effective Rate | Flat Rate + Up Rate | Bunga Jual %        |
| ----- | -------------- | ------------------- | ------------------- |
| 12    | 14,4699%       | 0,08510036380248776 | 0,08510036380248776 |
| 24    | 14,68%         | 0,08501699398093465 | 0,17003398796186930 |
| 36    | 14,55%         | 0,08501107580728577 | 0,25503322742185730 |
| 48    | 16,23%         | 0,09649856929686740 | 0,38599427718746960 |
| 60    | 16,50%         | 0,10001425315586772 | 0,50007126577933860 |

Deviasi: 0
Net DP: 10% — 11.000.000
LTV: 90% — 99.000.000

| Tenor | Total Asuransi | Total Bayar Pertama | Pencairan Neto | Total Refund |
| ----- | -------------- | ------------------- | -------------- | ------------ |
| 12    | 2.930.000      | 19.130.000          | 90.870.000     | 438.000      |
| 24    | 4.288.500      | 20.488.500          | 89.511.500     | 818.000      |
| 36    | 5.618.400      | 21.818.400          | 88.181.600     | 1.151.000    |
| 48    | 7.134.200      | 23.334.200          | 86.665.800     | 1.402.000    |
| 60    | 8.549.900      | 24.749.900          | 85.250.100     | 1.635.000    |

Total Refund tidak menambah Pencairan; lihat bagian 10c.

### Output Mode A

| Tenor | Pencairan Neto | Angsuran  |
| ----- | -------------- | --------- |
| 12    | 90.870.000     | 8.953.000 |
| 24    | 89.511.500     | 4.827.000 |
| 36    | 88.181.600     | 3.452.000 |
| 48    | 86.665.800     | 2.859.000 |
| 60    | 85.250.100     | 2.476.000 |

### Output Mode B

| Tenor | Total Bayar Pertama | DP Net     | LTV        | Angsuran  |
| ----- | ------------------- | ---------- | ---------- | --------- |
| 12    | 8.130.000           | 51.870.000 | 58.130.000 | 5.257.000 |
| 24    | 9.488.500           | 50.511.500 | 59.488.500 | 2.901.000 |
| 36    | 10.818.400          | 49.181.600 | 60.818.400 | 2.121.000 |
| 48    | 12.334.200          | 47.665.800 | 62.334.200 | 1.800.000 |
| 60    | 13.749.900          | 46.250.100 | 63.749.900 | 1.594.000 |

---

## 6. Skenario S3 — Dana Tunai ADDM

Konfigurasi sama dengan S1, Type Angsuran diubah menjadi **ADDM**.

### Output Mode A

| Tenor | Pencairan Maksimal | Angsuran  |
| ----- | ------------------ | --------- |
| 12    | 92.131.800         | 9.459.000 |
| 24    | 90.773.300         | 5.110.000 |
| 36    | 89.443.400         | 3.706.000 |
| 48    | 87.927.600         | 3.027.000 |
| 60    | 86.511.900         | 2.683.000 |

### Output Mode B

| Tenor | Angsuran  |
| ----- | --------- |
| 12    | 6.551.000 |
| 24    | 3.605.000 |
| 36    | 2.662.000 |
| 48    | 2.218.000 |
| 60    | 2.003.000 |

Pada Dana Tunai, Angsuran Pertama **tidak** menambah Total Bayar Pertama. Pencairan Maksimal karena itu identik dengan skenario ADDB.

---

## 7. Skenario S4 — Mobil Bekas ADDM

Konfigurasi sama dengan S2, Type Angsuran diubah menjadi **ADDM**.

### Output Mode A

| Tenor | Total Bayar Pertama | Pencairan Neto | Angsuran  |
| ----- | ------------------- | -------------- | --------- |
| 12    | 27.976.000          | 82.024.000     | 8.846.000 |
| 24    | 25.257.500          | 84.742.500     | 4.769.000 |
| 36    | 25.229.400          | 84.770.600     | 3.411.000 |
| 48    | 26.156.200          | 83.843.800     | 2.822.000 |
| 60    | 27.192.900          | 82.807.100     | 2.443.000 |

### Output Mode B

| Tenor | Angsuran  |
| ----- | --------- |
| 12    | 5.704.000 |
| 24    | 3.011.000 |
| 36    | 2.170.000 |
| 48    | 1.829.000 |
| 60    | 1.613.000 |

Berbeda dengan Dana Tunai, Angsuran Pertama **menambah** Total Bayar Pertama sehingga Pencairan turun signifikan pada tenor pendek.

---

## 8. Skenario S5 — Mobil Bekas dengan Deviasi

Konfigurasi sama dengan S2, dengan perubahan:

| Field        | Nilai                    |
| ------------ | ------------------------ |
| Harga Pasar  | 145.000.000              |
| Type Debitur | Perorangan (Wiraswasta)  |

### Nilai Antara

```text
Deviasi (Rp) = 145.000.000 - 110.000.026 = 34.999.974
Deviasi (%)  = 34.999.974 ÷ 110.000.026 = 0,318181506611644
Net DP (%)   = 30% + 31,8181506611644% = 61,8181506611644%
Net DP (Rp)  = 89.636.318,45868836
```

Deviasi diukur terhadap **Harga PHPM**, bukan Harga Pasar — ditetapkan 11 Agustus 2026. Memakai Harga PHPM yang telah dibulatkan sebagai pengurang akan menyebabkan selisih Rp 26 pada seluruh nilai turunan.

### Output Mode A

| Tenor | Total Asuransi | Total Bayar Pertama | Pencairan Neto | Angsuran  |
| ----- | -------------- | ------------------- | -------------- | --------- |
| 12    | 3.080.500      | 97.916.818,46       | 47.083.181,54  | 5.007.000 |
| 24    | 4.597.200      | 99.433.518,46       | 45.566.481,54  | 2.700.000 |
| 36    | 6.350.300      | 101.186.618,46      | 43.813.381,54  | 1.931.000 |
| 48    | 8.348.400      | 103.184.718,46      | 41.815.281,54  | 1.599.000 |
| 60    | 10.214.500     | 105.050.818,46      | 39.949.181,54  | 1.385.000 |

### Output Mode B

Total DP dikehendaki 60.000.000 tidak mencapai Net DP minimum 61,82% dari 145.000.000.

| Tenor | LTV | Angsuran |
| ----- | --- | -------- |
| 12–60 | 0   | 0        |

Skenario ini menguji penolakan ketika Down Payment tidak mencukupi.

---

## 9. Skenario S6 — Unit Tidak Layak

Konfigurasi sama dengan S1 dan S2, Tahun Kendaraan diubah menjadi **2013**.

### Kelayakan

| Tenor | Perhitungan       | Hasil | Status       |
| ----- | ----------------- | ----- | ------------ |
| 12    | (16-1) - (2026-2013) | 2  | Layak        |
| 24    | (16-2) - (2026-2013) | 1  | Layak        |
| 36    | (16-3) - (2026-2013) | 0  | Tidak layak  |
| 48    | (16-4) - (2026-2013) | -1 | Tidak layak  |
| 60    | (16-5) - (2026-2013) | -2 | Tidak layak  |

Model tersebut tidak memiliki harga pada tahun 2013, sehingga Harga PHPM bernilai 0.

### Output yang Diharapkan

| Produk | Tenor | Pencairan | Angsuran |
| ------ | ----- | --------- | -------- |
| DTN    | 12–60 | 0         | 0        |
| UCF    | 12–60 | 0         | 0        |

### Koreksi terhadap Draft

Draft menghasilkan Pencairan All In yang tidak valid pada Pembiayaan Mobil Bekas. Nilai yang berlaku adalah kolom Sistem.

| Tenor | Draft   | Sistem |
| ----- | ------- | ------ |
| 12    | 30.000  | 0      |
| 24    | 60.000  | 0      |
| 36    | 87.000  | 0      |
| 48    | 116.000 | 0      |
| 60    | 146.000 | 0      |

Nilai pada draft berasal dari Total Refund yang tetap dihitung meskipun Harga PHPM bernilai 0.

### Rate Tenor Kosong

Menggunakan Product **Reguler Commercial Sales Dealer** yang tidak memiliki rate tenor 60 bulan:

| Tenor | Angsuran  | Keterangan                          |
| ----- | --------- | ----------------------------------- |
| 48    | 2.865.000 | Rate tersedia, dihitung normal      |
| 60    | 0         | Rate kosong, tenor tidak tersedia   |

Implementasi yang memperlakukan rate kosong sebagai 0% akan menghasilkan angsuran pada tenor 60 dan dinyatakan gagal uji.

---

## 10. Skenario S7 — Variasi Usia Debitur

Konfigurasi sama dengan S1, hanya Usia Debitur yang berubah.

### Total Asuransi Dana Tunai

| Tenor | 18-35 tahun | 36-45 tahun | 51-60 tahun |
| ----- | ----------- | ----------- | ----------- |
| 12    | 6.518.200   | 6.518.200   | 6.793.200   |
| 24    | 7.876.700   | 7.876.700   | 8.426.700   |
| 36    | 9.206.600   | 9.206.600   | 10.048.100  |
| 48    | 10.722.400  | 10.722.400  | 11.954.400  |
| 60    | 12.138.100  | 12.138.100  | 13.722.100  |

### Pencairan Maksimal Dana Tunai

| Tenor | 18-35 tahun | 36-45 tahun | 51-60 tahun |
| ----- | ----------- | ----------- | ----------- |
| 12    | 92.131.800  | 92.131.800  | 91.856.800  |
| 24    | 90.773.300  | 90.773.300  | 90.223.300  |
| 36    | 89.443.400  | 89.443.400  | 88.601.900  |
| 48    | 87.927.600  | 87.927.600  | 86.695.600  |
| 60    | 86.511.900  | 86.511.900  | 84.927.900  |

Kelompok 18-35, 36-45, dan 46-50 menghasilkan nilai identik. Perbedaan hanya muncul pada kelompok 51-60 karena faktor Up ACP bernilai 0,8, bukan 0,3.

---

## 10b. Register Penyimpangan terhadap Draft

Verifikasi ulang 11 Agustus 2026 membandingkan `Draft-Web.xlsx` sel demi sel terhadap implementasi. Metodenya: replika formula Excel dibangun terpisah, divalidasi ke nilai cached workbook pada 30 titik uji, lalu dijalankan berdampingan dengan engine pada 2.160 skenario.

Draft **bukan** spesifikasi. Sepuluh titik berikut adalah cacat draft yang sengaja tidak direproduksi.

| No | Sel draft | Cacat | Nilai yang berlaku |
| -- | --------- | ----- | ------------------ |
| 1 | `DT Ext!G20` | Berisi angka `93`, bukan formula `=G17+G19`. Angsuran DTN tenor 12 Mode A menjadi Rp 1.000 | Total A/R = LTV + Bunga Jual |
| 2 | `DT/UC Ext!G25` | Loading dikalikan Casco kumulatif sehingga Casco tahun sebelumnya dihitung ulang tiap tahun | Loading = Casco tahun berjalan × Rate Loading umur tahun tersebut |
| 3 | `DT/UC Ext!F25` | Rate Loading dikunci pada tahun model unit, tidak naik selama masa asuransi | Rate mengikuti umur unit pada tahun asuransi berjalan |
| 4 | `DT/UC Ext!I35,L35,O35,R35` | Pengali jumlah penumpang menunjuk sel kosong. Premi Penumpang hilang pada tahun 2 sampai 5 | Premi Penumpang berlaku pada setiap tahun Comprehensive |
| 5 | `DT/UC Ext!G33` | TJH ditagih pada tahun TLO, tidak konsisten dengan Loading dan Perluasan yang bergerbang Comprehensive | TJH hanya pada tahun Comprehensive |
| 6 | `UC Ext!F51` | Dasar Refund Asuransi tidak jelas: subtotal `F30` mencakup Loading, tetapi implementasi awal justru memasukkan Pengemudi dan Penumpang | Dasar = Casco + Loading + Perluasan |
| 7 | `UC Ext!F55` | Total Refund tidak bergerbang kelayakan unit, sehingga Pencairan All In bernilai kecil yang tidak valid | Seluruh komponen tenor bernilai 0, termasuk Refund |
| 8 | `UC Ext!G39` | ROUNDUP Provisi hanya pada kolom tenor 12; tenor 24 sampai 60 tidak dibulatkan | ROUNDUP ratusan pada seluruh tenor |
| 9 | `Asr Ext!D70:H73` | Seluruh kelompok usia membaca kolom rate 36-45. Kolom 18-35, 46-50, dan 51-60 tidak terpakai | Dipertahankan. Tabel upping per kelompok usia adalah konfigurasi Admin |
| 10 | `products.up_acp` | Up ACP ditambahkan dua kali: lewat tabel upping kelompok usia dan lewat kolom produk | Kolom dihapus. Upping hanya dari tabel kelompok usia |

Cacat draft berikut tidak berpengaruh pada hasil karena silang-kabel antar produk tidak direproduksi di sistem:

| Sel draft | Cacat |
| --------- | ----- |
| `UC Ext!F12` | Tenor 12 membaca master `PHPM 1 UC Ext`, tenor 24 sampai 60 membaca `PHPM 1 DT Ext` |
| `UC Ext!F69,G70` | Mode B membaca Type Angsuran milik Dana Tunai (`Home Ext!K33`) |
| `Asr Ext!B12,C20` | Rate Casco Pembiayaan Mobil Bekas mengikuti Penggunaan Unit dan varian rate milik Dana Tunai |
| `Asr Int!G40:M44` | Dana Tunai dan Mobil Bekas sama-sama membaca tabel TJH Used Car; tabel TJH Dana Tunai tidak terpakai |
| `Asr Ext!D31:E34` | Rate ACP Used Car bernilai `#REF!` |
| `Asr Ext!L6:N20` | Rate Loading nol untuk unit umur 11 tahun ke atas, padahal unit tersebut masih lolos kelayakan |
| `DT/UC Ext!F10`, `Asr Ext!L6` | Tahun berjalan 2026 dan batas usia 16 ditulis langsung di formula |
| `Home Int!K173` | Referensi `'UC Int'!$O$7882` tidak valid |

Hasil aljabar yang **bukan** cacat, dicatat supaya tidak diperiksa ulang: pada Mode B Mobil Bekas ADDM, sel `F69` dan `G74` menghasilkan angka identik. Substitusi `LTV = Dasar + Angsuran` ke dalam `LTV × (1 + Bunga) ÷ Tenor Bulan` mengembalikan `Angsuran` itu sendiri.

---

## 10c. Koreksi 15 Agustus 2026 — Refund Keluar dari Pencairan

Stakeholder melaporkan bahwa menaikkan upping "malah ngurangin pencairan". Penelusuran menemukan dua hal.

**Yang dikeluhkan memang benar dan disengaja.** Up Provisi membebankan biaya di muka, jadi pencairan turun sebesar Provisi. Itulah gunanya upping.

**Yang keliru justru kebalikannya.** Pada draft, Total Refund ditambahkan ke Pencairan All In. Karena bunga tidak dibebankan di muka, menaikkan Up Rate 3% justru **menaikkan** pencairan Pembiayaan Mobil Bekas dari 105.988.186 menjadi 108.434.186 — tepat sebesar Refund Bunga. Rate yang lebih tinggi membayar penerima lebih banyak.

Dua ketentuan yang berlaku sejak koreksi ini:

| No | Ketentuan |
| -- | --------- |
| 1  | Refund adalah komisi Referral, bukan uang debitur, sehingga tidak pernah menambah Pencairan |
| 2  | Dana Tunai memperoleh Refund Bunga dan Refund Provisi; Refund Asuransi dan Refund Admin tetap milik Pembiayaan Mobil Bekas |
| 3  | Penyebut Refund Bunga memakai Flat Rate sebelum Up Rate, bukan Bunga Jual |

Ketentuan 3 mengubah Total Refund pada S2 dan S4. Pencairan tidak ikut bergerak, karena Refund memang sudah keluar dari Pencairan.

| Skenario | Tenor | Refund lama | Refund berlaku |
| -------- | ----- | ----------- | -------------- |
| S2 | 12 | 436.000 | 438.000 |
| S2 | 24 | 812.000 | 818.000 |
| S2 | 36 | 1.139.000 | 1.151.000 |
| S2 | 48 | 1.385.000 | 1.402.000 |
| S2 | 60 | 1.613.000 | 1.635.000 |
| S4 | 12 | 440.000 | 442.000 |
| S4 | 24 | 820.000 | 826.000 |
| S4 | 36 | 1.150.000 | 1.162.000 |
| S4 | 48 | 1.401.000 | 1.418.000 |
| S4 | 60 | 1.630.000 | 1.653.000 |

Setelah koreksi, tiap upping bergerak pada satu arah saja: Up Rate hanya menaikkan Angsuran, Up Provisi hanya menurunkan Pencairan.

### Nilai Acuan yang Berubah

Nilai baru sama persis dengan nilai lama dikurangi Total Refund pada tenor yang sama. Lima belas titik diperiksa, seluruhnya cocok.

| Skenario | Tenor | Lama | Refund | Berlaku |
| -------- | ----- | ---- | ------ | ------- |
| S2 | 12 | 91.306.000 | 436.000 | 90.870.000 |
| S2 | 24 | 90.323.500 | 812.000 | 89.511.500 |
| S2 | 36 | 89.320.600 | 1.139.000 | 88.181.600 |
| S2 | 48 | 88.050.800 | 1.385.000 | 86.665.800 |
| S2 | 60 | 86.863.100 | 1.613.000 | 85.250.100 |
| S4 | 12 | 82.464.000 | 440.000 | 82.024.000 |
| S4 | 24 | 85.562.500 | 820.000 | 84.742.500 |
| S4 | 36 | 85.920.600 | 1.150.000 | 84.770.600 |
| S4 | 48 | 85.244.800 | 1.401.000 | 83.843.800 |
| S4 | 60 | 84.437.100 | 1.630.000 | 82.807.100 |
| S5 | 12 | 47.350.181,54 | 267.000 | 47.083.181,54 |
| S5 | 24 | 46.065.481,54 | 499.000 | 45.566.481,54 |
| S5 | 36 | 44.538.381,54 | 725.000 | 43.813.381,54 |
| S5 | 48 | 42.716.281,54 | 901.000 | 41.815.281,54 |
| S5 | 60 | 41.015.181,54 | 1.066.000 | 39.949.181,54 |

S1, S3, S6, dan S7 tidak berubah: Dana Tunai memang tidak pernah menambahkan Refund ke Pencairan, dan konfigurasi acuannya tidak memakai upping sehingga Refund bernilai 0.

Istilah **Pencairan All In** dihentikan. Nilai yang dimaksud sekarang adalah Pencairan Neto.

---

## 11. Ketentuan Pengujian

Implementasi wajib memenuhi seluruh ketentuan berikut.

| No | Ketentuan                                                                   |
| -- | --------------------------------------------------------------------------- |
| 1  | Seluruh nilai keluaran cocok persis dengan tabel pada dokumen ini            |
| 2  | Harga PHPM dan Harga OTR diperlakukan sebagai dua nilai berbeda              |
| 3  | Deviasi dihitung terhadap Harga PHPM tanpa pembulatan                        |
| 4  | Pembulatan diterapkan pada urutan yang ditentukan `credit-simulation.md`     |
| 5  | Unit tidak layak menghasilkan 0 pada seluruh komponen, termasuk Refund       |
| 6  | Rate tenor kosong diperlakukan sebagai tenor tidak tersedia, bukan rate 0    |
| 7  | Angsuran Pertama hanya menambah Total Bayar Pertama pada Pembiayaan Mobil Bekas |

---

## 12. Related Documentation

| Document                             | Purpose                               |
| ------------------------------------ | ------------------------------------- |
| `credit-simulation.md`               | Input, perhitungan, dan output        |
| `credit-simulation-configuration.md` | Parameter dan konfigurasi perhitungan |