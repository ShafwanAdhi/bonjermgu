# Credit Simulation

## 1. Overview

Credit Simulation merupakan proses perhitungan estimasi pembiayaan yang dilakukan Referral bersama calon debitur.

Simulasi dijalankan sepenuhnya oleh sistem menggunakan parameter yang telah ditetapkan Admin. Referral tidak dapat mengubah parameter perhitungan.

Hasil simulasi bersifat **estimasi** dan tidak mengikat. Nilai akhir pembiayaan ditentukan setelah verifikasi profil debitur dan kondisi kendaraan.

Parameter yang dirujuk dalam dokumen ini didefinisikan pada `credit-simulation-configuration.md`.

---

## 2. Scope

Simulasi tersedia untuk dua produk pembiayaan:

| Code | Produk                 |
| ---- | ---------------------- |
| DTN  | Dana Tunai             |
| UCF  | Pembiayaan Mobil Bekas |

Produk berikut belum memiliki ketentuan perhitungan dan tidak ditampilkan dalam sistem:

- Pembiayaan Emas / Logam Mulia (LMF)
- Pembiayaan Mobil Baru (NCF)

---

## 3. Simulation Modes

Setiap produk memiliki dua mode simulasi.

Nama Mode A dan Mode B hanya dipakai sebagai istilah internal domain. Antarmuka menampilkan label berikut:

| Nilai internal | Label pengguna                                                |
| -------------- | ------------------------------------------------------------- |
| A              | Berdasarkan Nilai Kendaraan                                    |
| B              | Berdasarkan Kebutuhan Dana (DTN) / Berdasarkan Total DP (UCF) |

### Mode A — Berdasarkan Aset

```text
Data Kendaraan
      ↓
Sistem menghitung
      ↓
Pencairan Maksimal + Angsuran per tenor
```

Digunakan ketika calon debitur ingin mengetahui berapa besar pembiayaan yang dapat diperoleh.

### Mode B — Berdasarkan Kebutuhan

```text
Data Kendaraan + Nominal yang Dikehendaki
      ↓
Sistem menghitung
      ↓
Angsuran per tenor
```

Digunakan ketika calon debitur telah memiliki nominal tertentu.

Nominal yang dikehendaki berbeda per produk:

| Produk | Input Mode B         |
| ------ | -------------------- |
| DTN    | Dana yang dibutuhkan |
| UCF    | Total DP dikehendaki |

Kedua mode berjalan pada halaman yang sama dan menggunakan data kendaraan yang sama.

---

## 4. Tenor Output

Setiap simulasi menghasilkan lima baris hasil:

```text
12 Bulan
24 Bulan
36 Bulan
48 Bulan
60 Bulan
```

Tenor yang tidak memenuhi ketentuan kelayakan unit, atau yang tidak tersedia pada Product terkait, menghasilkan nilai 0.

---

## 5. Common Input

Input berikut berlaku pada kedua produk.

| Input                 | Sumber                                                                    |
| --------------------- | ------------------------------------------------------------------------- |
| Jenis Pembiayaan      | Pilihan produk                                                            |
| Domisili Debitur      | Master domisili                                                           |
| Type Debitur          | Perorangan (Wiraswasta) / Perorangan (Non Wiraswasta) / Badan Hukum Usaha |
| Usia Debitur          | Kelompok usia, hanya jika Type Debitur Perorangan                         |
| Type Kendaraan        | Passenger / Commercial                                                    |
| Merk Kendaraan        | Master PHPM                                                               |
| Type Kendaraan (unit) | Master PHPM, tergantung Merk                                              |
| Model Kendaraan       | Master PHPM, tergantung Type                                              |
| Tahun Kendaraan       | Master PHPM                                                               |
| Type Angsuran         | ADDB / ADDM                                                               |
| Asuransi              | Pilihan coverage                                                          |

Identitas calon debitur tidak diperlukan untuk menjalankan perhitungan. Nama, NIK, dan Tanggal Lahir baru diminta ketika Referral akan mengunduh hasil simulasi:

```text
Nama
NIK
Tanggal Lahir
```

Tidak ada data debitur lain yang diminta pada alur download. Identitas tersebut tidak memengaruhi hasil perhitungan.

---

## 6. Dana Tunai — Input Tambahan

| Input                | Keterangan                                                                                          |
| -------------------- | --------------------------------------------------------------------------------------------------- |
| Kebutuhan Dana       | Pendidikan / Modal Usaha / Renovasi Rumah / Liburan atau Wisata Religi / Pernikahan atau Persalinan |
| STNK atas nama       | Pribadi (milik sendiri) / Orang lain                                                                |
| Dana yang dibutuhkan | Hanya pada Mode B                                                                                   |

Harga unit pada Dana Tunai **tidak diinput**. Harga diambil dari master PHPM.

---

## 7. Dana Tunai — Mode A

### 7.1 Alur Perhitungan

```text
Data Kendaraan
      ↓
Harga PHPM
      ↓
Sum Insured
      ↓
Net DP
      ↓
LTV
      ↓
Rate & Bunga Jual
      ↓
Total A/R  →  Angsuran
      ↓
Biaya (Asuransi, Provisi, Admin, Fiducia)
      ↓
Total Bayar Pertama
      ↓
Pencairan Gross
      ↓
Pencairan Neto
```

### 7.2 Langkah Perhitungan

**Langkah 1 — Kelayakan Unit**

```text
Kelayakan = (Batas Usia Maksimal - Tenor Tahun) - (Tahun Berjalan - Tahun Kendaraan)
```

Jika Kelayakan < 1, seluruh hasil untuk tenor tersebut bernilai 0.

**Langkah 2 — Harga Unit**

```text
Kunci PHPM   = Penggunaan Unit + Merk + Type + Model + Tahun Kendaraan
Harga PHPM   = nilai dari master PHPM, tanpa pembulatan
Harga OTR    = ROUNDDOWN(Harga PHPM, ratusan)
```

Harga PHPM dan Harga OTR adalah dua nilai berbeda. Harga PHPM digunakan pada perhitungan Deviasi, ACP, dan seluruh perhitungan Mode B. Harga OTR digunakan pada Net DP, LTV, Sum Insured, dan Fiducia.

Apabila kombinasi kunci tidak memiliki harga, Harga PHPM bernilai 0 dan seluruh hasil tenor bernilai 0.

**Langkah 3 — Deviasi**

Pada Dana Tunai, Harga OTR berasal dari PHPM sehingga Deviasi selalu 0.

**Langkah 4 — Sum Insured**

```text
Sum Insured = Harga OTR × Persentase Sum Insured tahun berjalan
```

**Langkah 5 — Net DP**

```text
Net DP (%)  = 15%  jika STNK atas nama Orang lain
            = 15%  jika Type Kendaraan Commercial
            = 15%  jika unit berklasifikasi Non Japan
            = 5%   selain kondisi di atas

Net DP (Rp) = Harga OTR × Net DP (%)
```

**Langkah 6 — LTV**

```text
LTV (%)  = 100% - Net DP (%)
LTV (Rp) = Harga OTR - Net DP (Rp)
```

**Langkah 7 — Rate**

```text
Effective Rate = rate Product pada tenor terkait
Flat Rate      = konversi Effective Rate sesuai Type Angsuran
Flat Rate Final = Flat Rate + Up Rate
```

**Langkah 8 — Bunga Jual**

```text
Bunga Jual (%)  = Flat Rate Final × Tenor Tahun
Bunga Jual (Rp) = LTV (Rp) × Bunga Jual (%)
```

**Langkah 9 — Total A/R dan Angsuran**

```text
Total A/R = LTV (Rp) + Bunga Jual (Rp)
Angsuran  = ROUNDUP(Total A/R ÷ Tenor Bulan, ribuan)
```

**Langkah 10 — Asuransi**

Premi dihitung kumulatif sepanjang tenor.

```text
Casco        = Rate Casco × Sum Insured
Loading      = Casco × Rate Loading
Perluasan    = (Banjir + Gempa + Huru-hara + Teroris) × Sum Insured
TJH          = perhitungan berjenjang atas nilai TJH
Pengemudi    = Rate Pengemudi × nilai pertanggungan
Penumpang    = Rate Penumpang × jumlah penumpang × nilai pertanggungan
ACP          = Rate ACP × Harga PHPM
Garansi Mesin = nilai tetap

Total Asuransi = ROUNDDOWN(seluruh komponen di atas, ratusan)
```

Ketentuan coverage per tahun:

```text
Comprehensive All Tenor            → Comprehensive seluruh tahun
Comprehensive 1 tahun, sisanya TLO → tahun 1 Comprehensive, tahun berikutnya TLO
TLO All Tenor                      → TLO seluruh tahun
```

Loading dan seluruh perluasan hanya berlaku pada tahun dengan coverage Comprehensive.

ACP tidak berlaku apabila Type Debitur adalah Badan Hukum Usaha.

**Langkah 11 — Biaya Lain**

```text
Provisi      = (Persentase Provisi + Up Provisi) × LTV (Rp)
Administrasi = Admin Maksimal + Up Admin
Fiducia      = nilai berjenjang berdasarkan Harga OTR
```

**Langkah 12 — Total Bayar Pertama**

```text
Total Bayar Pertama = Net DP (Rp) + Total Asuransi + Provisi + Administrasi + Fiducia
```

**Langkah 13 — Pencairan**

```text
Pencairan Gross = Harga OTR - Total Bayar Pertama

Pengurang       = BBNKB + PKB + Faktur + Nilai Titipan Angsuran

Pencairan Neto  = Pencairan Gross - Pengurang
```

Apabila Harga PHPM bernilai 0, Pencairan Neto bernilai 0.

### 7.3 Output Mode A

| Kolom              | Isi                          |
| ------------------ | ---------------------------- |
| Tenor              | 12 / 24 / 36 / 48 / 60 Bulan |
| Pencairan Maksimal | Pencairan Neto               |
| Angsuran           | Angsuran per bulan           |

---

## 8. Dana Tunai — Mode B

Mode B menghitung angsuran berdasarkan dana yang dibutuhkan calon debitur.

### 8.1 Langkah Perhitungan

**Langkah 1 — Total DP**

```text
Total DP = Total Asuransi + Provisi + Administrasi + Fiducia
```

Net DP tidak termasuk dalam Total DP pada mode ini.

**Langkah 2 — DP Net**

```text
DP Net (Rp) = Harga PHPM - (Dana yang dibutuhkan + Total DP)
DP Net (%)  = DP Net (Rp) ÷ Harga PHPM
```

**Langkah 3 — LTV**

```text
Jika DP Net (%) >= Net DP (%) minimum:
    LTV (%)  = 100% - DP Net (%)
    LTV (Rp) = Harga PHPM × LTV (%)

Jika tidak:
    LTV = 0
```

**Langkah 4 — Angsuran**

```text
Total A/R = LTV (Rp) + (LTV (Rp) × Bunga Jual (%))
Angsuran  = ROUNDUP(Total A/R ÷ Tenor Bulan, ribuan)
```

Apabila LTV bernilai 0, permohonan tidak memenuhi ketentuan Down Payment minimum dan Angsuran bernilai 0.

### 8.2 Output Mode B

| Kolom     | Isi                          |
| --------- | ---------------------------- |
| Tenor     | 12 / 24 / 36 / 48 / 60 Bulan |
| Pencairan | Dana yang dibutuhkan         |
| Angsuran  | Angsuran per bulan           |

---

## 9. Pembiayaan Mobil Bekas — Input Tambahan

| Input                | Keterangan                           |
| -------------------- | ------------------------------------ |
| Harga Pasar          | Diinput Referral                     |
| STNK atas nama       | Pribadi (milik sendiri) / Orang lain |
| Total DP dikehendaki | Hanya pada Mode B                    |

Berbeda dengan Dana Tunai, harga unit **diinput** dan dapat berbeda dari harga PHPM.

---

## 10. Pembiayaan Mobil Bekas — Mode A

Alur perhitungan mengikuti Dana Tunai dengan perbedaan berikut.

**Harga Unit**

```text
Harga OTR  = Harga Pasar (input)
Harga PHPM = nilai dari master PHPM
```

**Deviasi**

```text
Deviasi (Rp) = Harga OTR - Harga PHPM   (jika positif, selain itu 0)
Deviasi (%)  = Deviasi (Rp) ÷ Harga OTR
```

Harga PHPM pada rumus di atas adalah nilai **tanpa pembulatan**. Menggunakan nilai yang telah dibulatkan menghasilkan selisih pada Net DP dan seluruh nilai turunannya.

**Net DP**

```text
Net DP (%) = 30% + Deviasi (%)   jika Type Debitur Perorangan (Wiraswasta)
           = 10% + Deviasi (%)   selain itu
```

**Asuransi**

```text
Total Asuransi = ROUNDUP(seluruh komponen, ratusan)
```

ACP tidak berlaku pada Pembiayaan Mobil Bekas.

**Angsuran Pertama**

```text
Angsuran Pertama = Angsuran   jika Type Angsuran ADDM
                 = 0          jika Type Angsuran ADDB
```

**Total Bayar Pertama**

```text
Total Bayar Pertama = Net DP (Rp) + Total Asuransi + Provisi
                    + Administrasi + Fiducia + Angsuran Pertama
```

**Refund**

```text
Refund Asuransi = (Casco + Perluasan) × Persentase Dasar × Persentase Refund
Refund Bunga    = (LTV (Rp) × (Up Rate × Tenor Tahun)) ÷ (1 + Bunga Jual (%)) × Persentase Refund
Refund Provisi  = Provisi (Rp) × Persentase Refund
Refund Admin    = Up Admin × Persentase Refund

Total Refund = ROUNDDOWN(seluruh komponen, ribuan)
```

**Pencairan**

```text
Pencairan Gross  = Harga OTR - Total Bayar Pertama
Pencairan Neto   = Pencairan Gross - (BBNKB + PKB + Faktur + Deposit Angsuran)
Pencairan All In = Pencairan Neto + Total Refund
```

### Output Mode A

| Kolom            | Isi                          |
| ---------------- | ---------------------------- |
| Tenor            | 12 / 24 / 36 / 48 / 60 Bulan |
| Pencairan All In | Pencairan All In             |
| Angsuran         | Angsuran per bulan           |

---

## 11. Pembiayaan Mobil Bekas — Mode B

Mode B menghitung angsuran berdasarkan Total DP yang dikehendaki calon debitur.

**Langkah 1 — Total Bayar Pertama**

```text
Total Bayar Pertama = Total Asuransi + Provisi + Administrasi + Fiducia
```

**Langkah 2 — Angsuran**

```text
Dasar = Harga Pasar - (Total DP dikehendaki - Total Bayar Pertama)

ADDB: Angsuran = ROUNDUP((Dasar × (1 + Bunga Jual (%))) ÷ Tenor Bulan, ribuan)
ADDM: Angsuran = ROUNDUP((Dasar × (1 + Bunga Jual (%))) ÷ (Tenor Bulan - (1 + Bunga Jual (%))), ribuan)
```

**Langkah 3 — DP Net dan LTV**

```text
ADDB: DP Net (Rp) = Total DP - Total Bayar Pertama
ADDM: DP Net (Rp) = Total DP - (Total Bayar Pertama + Angsuran)

DP Net (%) = DP Net (Rp) ÷ Harga Pasar

Jika DP Net (%) >= Net DP (%) minimum:
    LTV (%)  = 100% - DP Net (%)
    LTV (Rp) = Harga Pasar - DP Net (Rp)
Jika tidak:
    LTV = 0
```

Apabila LTV bernilai 0, Angsuran bernilai 0.

### Output Mode B

| Kolom    | Isi                          |
| -------- | ---------------------------- |
| Tenor    | 12 / 24 / 36 / 48 / 60 Bulan |
| Total DP | Total DP dikehendaki         |
| Angsuran | Angsuran per bulan           |

---

## 12. Ringkasan Perbedaan DTN dan UCF

| Aspek                                     | Dana Tunai                        | Pembiayaan Mobil Bekas         |
| ----------------------------------------- | --------------------------------- | ------------------------------ |
| Sumber Harga OTR                          | PHPM                              | Input Harga Pasar              |
| Deviasi                                   | Selalu 0                          | Dapat terjadi                  |
| Net DP                                    | 5% atau 15%                       | 10% atau 30%, ditambah Deviasi |
| Dasar Net DP                              | STNK, Penggunaan Unit, Asal Unit  | Type Debitur                   |
| ACP                                       | Berlaku kecuali Badan Hukum Usaha | Tidak berlaku                  |
| Dasar perhitungan ACP                     | Harga PHPM                        | —                              |
| Pembulatan Total Asuransi                 | ROUNDDOWN ratusan                 | ROUNDUP ratusan                |
| Angsuran Pertama pada Total Bayar Pertama | Tidak termasuk                    | Termasuk jika ADDM             |
| Dasar Deposit Angsuran                    | Angsuran Pertama                  | Angsuran                       |
| Refund                                    | Tidak ada                         | Ada                            |
| Output Mode A                             | Pencairan Maksimal                | Pencairan All In               |
| Input Mode B                              | Dana yang dibutuhkan              | Total DP dikehendaki           |

---

## 13. Rounding Rules

| Nilai                | Pembulatan        |
| -------------------- | ----------------- |
| Harga OTR            | ROUNDDOWN ratusan |
| Angsuran             | ROUNDUP ribuan    |
| Total Asuransi (DTN) | ROUNDDOWN ratusan |
| Total Asuransi (UCF) | ROUNDUP ratusan   |
| Provisi (UCF)        | ROUNDUP ratusan   |
| Total Refund         | ROUNDDOWN ribuan  |

Pembulatan merupakan bagian dari hasil dan harus diterapkan persis pada urutan yang ditentukan.

---

## 14. Simulation Result

### Tampilan

Hasil simulasi ditampilkan sebagai lima baris tenor beserta nilai pencairan dan angsuran.

Setiap hasil disertai keterangan:

```text
Nominal pembiayaan bersifat estimasi.
Besarnya pembiayaan berdasarkan hasil verifikasi profil debitur dan kondisi kendaraan.
```

### Download Simulation

Referral dapat mengunduh hasil simulasi apabila calon debitur menyetujui pembiayaan.

Saat aksi download dipilih, sistem meminta dan memvalidasi Nama, NIK, dan Tanggal Lahir calon debitur. Halaman review dan file PDF tidak dapat dibuat dari hasil aktif yang belum memiliki ketiga data tersebut.

PDF hasil simulasi memuat:

```text
Identitas calon debitur (Nama, NIK, Tanggal Lahir)
Kode Referral
Jenis Pembiayaan
Data kendaraan
Type Angsuran dan pilihan Asuransi
Lima baris hasil tenor
Keterangan estimasi
```

PDF hasil simulasi dikirimkan Referral kepada AO melalui jalur komunikasi di luar sistem sebagaimana dijelaskan pada `workflow.md`.

---

## 15. Validation Rules

| Kondisi                                        | Perilaku Sistem                                                                |
| ---------------------------------------------- | ------------------------------------------------------------------------------ |
| Kombinasi kunci tidak ada pada master PHPM     | Simulasi tidak dapat dijalankan                                                |
| Model tidak memiliki harga pada tahun terpilih | Seluruh hasil tenor bernilai 0                                                 |
| Unit tidak memenuhi ketentuan kelayakan        | Hasil tenor terkait bernilai 0, **termasuk Total Refund dan Pencairan All In** |
| Rate tenor tidak tersedia pada Product         | Hasil tenor terkait bernilai 0                                                 |
| DP Net kurang dari Net DP minimum (Mode B)     | LTV dan Angsuran bernilai 0                                                    |
| Kombinasi Product tidak terdaftar              | Simulasi tidak dapat dijalankan                                                |

### Ketentuan Penormalan

Ketika sebuah tenor dinyatakan tidak menghasilkan pembiayaan, **seluruh** komponen tenor tersebut bernilai 0, termasuk Total Asuransi, Total Bayar Pertama, Total Refund, Pencairan, dan Angsuran.

Tidak boleh ada komponen yang tetap terhitung sendiri. Pada draft, Total Refund pada Pembiayaan Mobil Bekas tetap dihitung meskipun Harga PHPM bernilai 0, sehingga Pencairan All In menampilkan nilai kecil yang tidak valid.

### Ketentuan Rate Tenor Kosong

Rate tenor yang kosong pada Product Configuration berarti **tenor tidak tersedia**, bukan rate 0%.

Sistem tidak boleh melanjutkan perhitungan dengan rate 0, karena menghasilkan angsuran tanpa bunga. Kondisi ini terjadi pada seluruh Product Commercial yang tidak memiliki rate tenor 60 bulan.

---

## 16. Open Items

| No  | Item                          | Keterangan                                                                                                                                                                   |
| --- | ----------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Product pada UCF              | Tier produk pada Pembiayaan Mobil Bekas bernilai tetap, tidak diturunkan dari kategori Referral seperti pada Dana Tunai. Perlu ditetapkan mana yang benar.                   |
| 2   | Dasar perhitungan ACP         | Dana Tunai menghitung ACP atas Harga PHPM. Perlu konfirmasi apakah seharusnya atas LTV.                                                                                      |
| 3   | Batas Harga Pasar             | Perlu ditetapkan batas maksimal Harga Pasar terhadap PHPM pada Pembiayaan Mobil Bekas.                                                                                       |
| 4   | Perbedaan pembulatan          | Total Asuransi dibulatkan ke bawah pada DTN dan ke atas pada UCF. Perlu konfirmasi apakah disengaja.                                                                         |
| 5   | Deposit Angsuran              | Dasar perhitungan berbeda antara DTN dan UCF. Perlu ditetapkan ketentuan yang berlaku.                                                                                       |
| 6   | Penyimpanan hasil simulasi    | Perlu ditetapkan apakah hasil simulasi disimpan sistem dan dapat dirujuk saat AO membuat Credit Application.                                                                 |
| 7   | Type Angsuran pada UCF Mode B | Pada draft, Mode B membaca Type Angsuran milik Dana Tunai, bukan milik Pembiayaan Mobil Bekas. Sistem harus menggunakan Type Angsuran produk yang bersangkutan.              |
| 8   | Sumber PHPM pada UCF          | Pada draft, tenor 12 bulan membaca master PHPM Mobil Bekas sedangkan tenor 24 sampai 60 membaca master PHPM Dana Tunai. Sistem harus menggunakan satu sumber yang konsisten. |

Hasil pengujian kesetaraan perhitungan didokumentasikan pada `credit-simulation-test-vectors.md`.

---

## 17. Related Documentation

| Document                             | Purpose                                |
| ------------------------------------ | -------------------------------------- |
| `business.md`                        | Business context dan system scope      |
| `actors.md`                          | Actors, responsibilities, dan access   |
| `workflow.md`                        | End-to-end business workflow           |
| `credit-simulation-configuration.md` | Parameter dan konfigurasi perhitungan  |
| `document-requirement.md`            | Document requirements dan verification |
| `application-tracking.md`            | Application Tracking                   |
| `lending.md`                         | Lending                                |
