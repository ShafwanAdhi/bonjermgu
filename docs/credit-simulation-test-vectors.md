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
| 12    | 2.215.000      | 18.415.000          | 91.585.000     | 436.000      |
| 24    | 2.858.500      | 19.058.500          | 90.941.500     | 812.000      |
| 36    | 3.430.500      | 19.630.500          | 90.369.500     | 1.139.000    |
| 48    | 3.931.000      | 20.131.000          | 89.869.000     | 1.385.000    |
| 60    | 4.431.500      | 20.631.500          | 89.368.500     | 1.613.000    |

### Output Mode A

| Tenor | Pencairan All In | Angsuran  |
| ----- | ---------------- | --------- |
| 12    | 92.021.000       | 8.953.000 |
| 24    | 91.753.500       | 4.827.000 |
| 36    | 91.508.500       | 3.452.000 |
| 48    | 91.254.000       | 2.859.000 |
| 60    | 90.981.500       | 2.476.000 |

### Output Mode B

| Tenor | Total Bayar Pertama | DP Net     | LTV        | Angsuran  |
| ----- | ------------------- | ---------- | ---------- | --------- |
| 12    | 7.415.000           | 52.585.000 | 57.415.000 | 5.192.000 |
| 24    | 8.058.500           | 51.941.500 | 58.058.500 | 2.831.000 |
| 36    | 8.630.500           | 51.369.500 | 58.630.500 | 2.044.000 |
| 48    | 9.131.000           | 50.869.000 | 59.131.000 | 1.708.000 |
| 60    | 9.631.500           | 50.368.500 | 59.631.500 | 1.491.000 |

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

| Tenor | Total Bayar Pertama | Pencairan All In | Angsuran  |
| ----- | ------------------- | ---------------- | --------- |
| 12    | 27.261.000          | 83.179.000       | 8.846.000 |
| 24    | —                   | 86.992.500       | 4.769.000 |
| 36    | —                   | 88.108.500       | 3.411.000 |
| 48    | —                   | 88.448.000       | 2.822.000 |
| 60    | —                   | 88.555.500       | 2.443.000 |

### Output Mode B

| Tenor | Angsuran  |
| ----- | --------- |
| 12    | 5.634.000 |
| 24    | 2.939.000 |
| 36    | 2.092.000 |
| 48    | 1.735.000 |
| 60    | 1.509.000 |

Berbeda dengan Dana Tunai, Angsuran Pertama **menambah** Total Bayar Pertama sehingga Pencairan All In turun signifikan.

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
Deviasi (%)  = 34.999.974 ÷ 145.000.000 = 0,241379131034483
Net DP (%)   = 30% + 24,1379131034483% = 54,1379131034483%
Net DP (Rp)  = 78.499.974
```

Menggunakan Harga OTR yang telah dibulatkan sebagai pengurang akan menghasilkan Net DP 78.500.000 dan menyebabkan selisih Rp 26 pada seluruh nilai turunan.

### Output Mode A

| Tenor | Total Asuransi | Total Bayar Pertama | Pencairan All In | Angsuran  |
| ----- | -------------- | ------------------- | ---------------- | --------- |
| 12    | 2.138.000      | 85.837.974          | 59.470.026       | 6.014.000 |
| 24    | 2.712.200      | 86.412.174          | 59.162.826       | 3.242.000 |
| 36    | 3.466.200      | 87.166.174          | 58.665.826       | 2.319.000 |
| 48    | 4.126.000      | 87.825.974          | 58.204.026       | 1.921.000 |
| 60    | 4.785.700      | 88.485.674          | 57.729.326       | 1.663.000 |

### Output Mode B

Total DP dikehendaki 60.000.000 tidak mencapai Net DP minimum 54,14% dari 145.000.000.

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