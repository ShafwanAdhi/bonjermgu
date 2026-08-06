# Client Decisions

Catatan keputusan client atas Open Items perhitungan. Dokumen ini **mengikat** dan menggantikan tebakan mana pun pada dokumen lain.

Setiap butir ditandai statusnya:

```text
SELESAI     keputusan jelas, sudah diterapkan atau tidak perlu perubahan
KONFLIK     keputusan jelas tetapi bertentangan dengan nilai acuan pengujian
BELUM       jawaban belum menutup pertanyaannya
```

---

## Sesi 5 Agustus 2026

### 1. Product pada Pembiayaan Mobil Bekas — BELUM

> "Pembiayaan mobil bekas khusus captive external dulu, temporer. Sebetulnya cuma ada 2 product, Captive & Reguler, Passenger dan Commercial."

UCF sementara memakai jalur Captive External. Client menyatakan sebetulnya hanya ada dua product — Captive dan Reguler — masing-masing Passenger dan Commercial.

Belum tertutup: sheet `Product` memuat **17 baris** dengan tier berbeda-beda (Low Rate, Semangat, Cuan, High Benefit, dan seterusnya), sedangkan pernyataan client mengarah ke **4 kombinasi**. Perlu ditetapkan apakah tier dihapus dari pembentukan nama Product, atau 17 baris itu tetap ada dan hanya sebagian yang berlaku untuk UCF.

### 2. Upping — BELUM

> "Upping di input AO (internal), karena itu dapurnya MTF, external gak boleh tau."

Upping diinput internal dan tidak boleh diketahui Referral.

Belum tertutup: pada sistem ini AO tidak memiliki halaman simulasi (`actors.md` bagian 3). Perlu ditetapkan siapa yang menyimpan nilai upping dan di mana — konfigurasi Product oleh Admin, atau input per simulasi pada halaman internal yang belum ada.

Catatan: workbook `UC Ext` **menerapkan** upping 0,5% pada perhitungan external (`H18 = G18 + S5`). Jadi upping berlaku pada hasil yang dilihat Referral, hanya rinciannya yang tidak ditampilkan.

### 3. Sumber master PHPM — SELESAI

> "Master PHPM kalo digabung, nanti akan eror, dalam waktu bersamaan digunakan user. Jadi dipisah berdasarkan product."

Pemisahan master PHPM adalah keterbatasan Excel saat dipakai bersamaan, bukan ketentuan bisnis.

Diverifikasi: `PHPM 1 DT Ext` dan `PHPM 1 UC Ext` identik — 295 model, nol selisih harga. Begitu pula `PHPM 2`.

**Tidak ada perubahan.** Sistem memakai satu tabel `vehicle_prices`; PostgreSQL menangani akses bersamaan. Ini juga menutup Open Item 8 pada `credit-simulation.md`: karena kedua master identik, kejanggalan draft tidak berpengaruh pada angka.

### 4. Batas usia kendaraan — KONFLIK

> "15 tahun utk DT Passenger, 10 tahun utk UC Passenger dan 12 tahun utk commercial vehicle."

Batas usia bergantung pada produk pembiayaan dan penggunaan unit, bukan satu nilai tunggal.

Konflik: terdapat tiga nilai berbeda untuk UC Passenger.

| Sumber                        | Batas usia UC Passenger |
| ----------------------------- | ----------------------- |
| Sistem saat ini               | 16                      |
| Workbook (`UC Ext` F8)        | 15                      |
| Keputusan client              | 10                      |

Nilai acuan pengujian memakai kendaraan tahun 2013 dan 2017. Pada batas 10, kendaraan 2013 gugur pada seluruh tenor, sehingga 250 nilai acuan berubah. Perlu ditetapkan apakah nilai acuan diterbitkan ulang.

### 5. Batas Harga Pasar — SELESAI

> "Harga PHPM bersifat flexible, gak dikunci, tapi simulasi nya Deviasi PHPM sebagai penambah DP."

Tidak ada batas atas. Selisih terhadap PHPM menambah persentase Net DP.

**Tidak ada perubahan.** Perilaku sistem sudah demikian.

### 6. Dasar perhitungan ACP — BELUM

Pertanyaannya: ACP dihitung atas Harga PHPM atau atas LTV. Jawaban yang diterima membahas kebijakan asuransi secara umum, belum menyebut dasar perhitungan ACP.

Dana Tunai saat ini menghitung ACP atas Harga PHPM.

### 7. Asuransi tambahan dan perluasan — SELESAI

> "Semua tambahan/Perluasan asuransi berlaku tetap untuk external."

Kebijakan tetap, bukan pilihan Referral.

**Tidak ada perubahan.** Perluasan, TJH, pengemudi, penumpang, dan garansi mesin dibaca dari konfigurasi, bukan dari input simulasi.

### 8. Perbedaan pembulatan total asuransi — SELESAI

> "Betul, karena harus mengakomodir simulasi dari kantor pusat."

Pembulatan ke bawah pada Dana Tunai dan ke atas pada Mobil Bekas memang disengaja.

**Tidak ada perubahan.** Sebelumnya diduga cacat; ternyata ketentuan.

### 9. Loading usia kendaraan — BELUM

> "Loading asuransi, usia kendaraan lebih dari 5 tahun. Non loading insurance 2026, 2025, 2024, 2023, 2022, 2021."

Loading berlaku mulai usia 6 tahun. Tabel yang ter-seed sudah sesuai untuk usia 6–10 tahun (5%, 10%, 15%, 20%, 25%).

Belum tertutup: usia 11 tahun ke atas saat ini bernilai 0%, yang bertentangan dengan "berlaku di atas 5 tahun". Rentang ini nyata bagi DT Passenger (batas 15 tahun) dan Commercial (batas 12 tahun). Perlu nilai loading untuk usia 11 sampai 15.

### 10. Deposit Angsuran — SELESAI

> "Deposit Angsuran berlaku untuk semua produk, cuma internal yg bisa input."

Dasar sama untuk kedua produk; hanya internal yang dapat mengisi.

**Tidak ada perubahan** pada simulasi external, yang selalu bernilai 0.

### 11. Type Angsuran — SELESAI

> "Type angsuran untuk semua product. ADDB dan ADDM."

Berlaku untuk seluruh produk, masing-masing memakai miliknya sendiri.

**Tidak ada perubahan.** Sistem sudah demikian; kejanggalan draft tidak direplikasi. Menutup Open Item 7 pada `credit-simulation.md`.

### 12. Kolom DP pada tabel Product — SELESAI

> "Kolom DP diproduct gak dipake dulu."

Tidak digunakan perhitungan. Ketentuan Net DP berasal dari Type Debitur.

**Tidak ada perubahan.** Kolomnya dipertahankan tanpa dibaca engine.

### 13. Admin Minimal — KONFLIK

> "Admin minimal itu dasar pemberian reward admin ke referral. Misal admin min 3,5 juta admin jual 4,5 juta, jadi rewardnya untuk referral itu 1 juta."

Admin Minimal bukan batas bawah biaya, melainkan dasar perhitungan reward Referral:

```text
Reward admin = Admin jual − Admin Minimal
```

Konflik: sistem saat ini menghitung komponen admin pada refund sebagai `upping admin × persentase refund`, bukan `admin jual − admin minimal`. Karena upping admin bernilai 0, komponen ini selalu 0. Dengan ketentuan client, Product `Reguler Passenger Referral` menghasilkan 5.350.000 − 3.750.000 = 1.600.000, dan angka itu mengubah total refund sekaligus pencairan all in pada seluruh nilai acuan.

### 14. Komponen refund — KONFLIK

> "Nilai refund tergantung brp besar upping: upping bunga%, upping provisi%, upping adminRp, dan Asuransi 25% dari asuransi Casco, semua utk reward Referral kecuali ACP dan Garansi Mesin."

Refund adalah **reward Referral**, bukan pengembalian biaya kepada debitur.

Konflik dua hal:

| Komponen        | Sistem saat ini                              | Keputusan client        |
| --------------- | -------------------------------------------- | ----------------------- |
| Dasar asuransi  | Casco + perluasan + pengemudi + penumpang    | Casco saja              |
| Persentase      | 10% (sesuai workbook `D51`)                  | 25%                     |

Nilai 25% juga muncul pada mockup antarmuka Admin, sedangkan 10% berasal dari workbook yang menjadi sumber nilai acuan. Perlu ditetapkan mana yang berlaku, dan apakah nilai acuan diterbitkan ulang.

### 15. Jumlah unit per application — SELESAI

> "Satu aplikasi untuk 1 unit."

Satu Credit Application selalu mencakup satu unit kendaraan.

Menutup Open Item 6 pada `application-tracking.md` dan Open Item 1 pada `lending.md`.

---

## Ringkasan status

| Status  | Butir                        |
| ------- | ---------------------------- |
| SELESAI | 3, 5, 7, 8, 10, 11, 12, 15   |
| KONFLIK | 4, 13, 14                    |
| BELUM   | 1, 2, 6, 9                   |

Butir berstatus KONFLIK tidak boleh diterapkan sebelum ditetapkan apakah nilai acuan pengujian diterbitkan ulang. Nilai acuan adalah gerbang wajib pada `CLAUDE.md`; menerapkan perubahan tanpa memperbaruinya akan membuat gerbang itu merah dan menghentikan seluruh pekerjaan modul simulasi.
