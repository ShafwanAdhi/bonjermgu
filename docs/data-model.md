# Data Model

## 1. Overview

Dokumen ini mendefinisikan entitas, relasi, dan constraint.

Constraint yang dapat dinyatakan di level database **wajib** ditulis di level database. Validasi aplikasi merupakan lapisan tambahan, bukan pengganti.

Konvensi penamaan mengikuti AD-16 pada `architecture.md`: nama tabel dan kolom berbahasa Inggris, snake_case, jamak untuk nama tabel.

---

## 2. Peta Entitas

```text
users
├── admins
├── referrals ──────────┐
└── account_officers ───┤
                        │
referral_categories     │
└── referral_sub_categories
    └── institutions
                                │
                        ↓
                   applications
                   ├── application_documents ── document_requirements
                   └── application_trackings ── tracking_stages

products ── product_rates
vehicle_usages ── vehicle_brands ── vehicle_types ── vehicle_models ── vehicle_prices
insurance_casco_rates
insurance_loading_rates
insurance_extension_rates
acp_base_rates
acp_uppings
tjh_tiers
fiducia_tiers
sum_insured_schedules
age_groups
simulation_settings
users ── admin_change_logs
```

---

## 3. Akun

### users

| Kolom      | Tipe         | Ketentuan                      |
| ---------- | ------------ | ------------------------------ |
| id         | bigserial    | PK                             |
| username   | varchar(50)  | unique, not null               |
| password   | varchar(255) | hash bcrypt, not null          |
| role       | varchar(20)  | check in (admin, referral, ao) |
| is_active  | boolean      | default true                   |
| timestamps |              |                                |

Tabel ini **hanya** memuat kredensial. Tidak ada kolom yang berlaku bagi sebagian role saja.

### referrals

| Kolom           | Tipe         | Ketentuan                  |
| --------------- | ------------ | -------------------------- |
| id              | bigserial    | PK                         |
| user_id         | bigint       | FK users, unique, not null |
| full_name       | varchar(150) | not null                   |
| birth_date      | date         | not null                   |
| nik             | varchar(20)  | nullable, unique, legacy   |
| email           | varchar(150) |                            |
| phone           | varchar(20)  |                            |
| category_id     | bigint       | FK referral_categories     |
| sub_category_id | bigint       | FK referral_sub_categories |
| institution_id  | bigint       | FK institutions, nullable  |
| branch_name     | varchar(150) | nullable, teks bebas       |
| timestamps      |              |                            |

Kategori, sub-kategori, instansi, dan cabang disimpan sebagai kolom relasional terpisah, bukan sebagai satu teks gabungan. Kolom `nik` hanya dipertahankan untuk kompatibilitas data lama dan tidak dipakai pada registrasi akun baru.

### account_officers

| Kolom      | Tipe         | Ketentuan                  |
| ---------- | ------------ | -------------------------- |
| id         | bigserial    | PK                         |
| user_id    | bigint       | FK users, unique, not null |
| full_name  | varchar(150) | not null                   |
| birth_date | date         | not null                   |
| nik        | varchar(20)  | nullable, unique, legacy   |
| email      | varchar(150) |                            |
| phone      | varchar(20)  |                            |
| timestamps |              |                            |

Tidak memiliki atribut kategori, sub-kategori, instansi, maupun cabang. Kolom `nik` hanya dipertahankan untuk kompatibilitas data lama dan tidak dipakai saat Admin membuat akun AO.

### admins

| Kolom      | Tipe         | Ketentuan                  |
| ---------- | ------------ | -------------------------- |
| id         | bigserial    | PK                         |
| user_id    | bigint       | FK users, unique, not null |
| full_name  | varchar(150) | not null                   |
| timestamps |              |                            |

---

## 4. Master Referral

### referral_categories

| Kolom            | Tipe         | Ketentuan                   |
| ---------------- | ------------ | --------------------------- |
| id               | bigserial    | PK                                  |
| name             | varchar(100) | unique, not null                    |
| segment          | varchar(20)  | check in (Reguler, Captive)         |
| tier             | varchar(100) | not null, check tier <> Referral C2C |
| allows_passenger | boolean      | default true                        |
| allows_commercial | boolean      | default true                        |
| is_active        | boolean      | default true                        |

Kolom `segment` dan `tier` digunakan untuk membentuk nama Product. Sedikitnya satu penggunaan unit harus diizinkan. Ketentuannya pada `credit-simulation.md`.

#### Tier `Referral C2C` — dipensiunkan

**Ketentuan bisnis, dikonfirmasi client.** `Referral C2C` bukan kategori tersendiri. Tidak ada perbedaan pricing, Product, maupun proses dengan `Referral`. Nilai canonical tunggal adalah **`Referral`**.

Konsekuensinya:

- Tidak ada Product berakhiran `Referral C2C`, dan tidak boleh ada. Kategori bertier `Referral` membentuk `Reguler Passenger Referral` dan `Reguler Commercial Referral`.
- Tidak ada fallback, tidak ada penyalinan rate antar-Product. Keduanya memang satu kategori, sehingga tidak ada dua tabel rate yang perlu dijaga tetap sama.
- Nilai lama ditolak `CHECK` di database dan oleh validasi layar Master Referral. Ini bukan workaround teknis — nilai tersebut memang tidak lagi berlaku secara bisnis.
- Workbook sumber masih menuliskan `Referral C2C` untuk SRB. Itu keadaan sebelum keputusan client dan **tidak boleh** disalin ulang saat master data diekstrak ulang.

Data lama dinormalisasi oleh `2026_08_04_000007` (baris SRB) dan `2026_08_05_000001` (seluruh baris, tanpa terkecuali).

### referral_sub_categories

| Kolom       | Tipe         | Ketentuan              |
| ----------- | ------------ | ---------------------- |
| id          | bigserial    | PK                     |
| category_id | bigint       | FK referral_categories |
| name        | varchar(100) | not null               |

`unique (category_id, name)`

### institutions

| Kolom           | Tipe         | Ketentuan                  |
| --------------- | ------------ | -------------------------- |
| id              | bigserial    | PK                         |
| sub_category_id | bigint       | FK referral_sub_categories |
| name            | varchar(150) | not null                   |

`unique (sub_category_id, name)`

Cabang tidak memiliki tabel master. Pada draft, cabang merupakan isian teks bebas dengan contoh "Tunas Toyota Pecenongan". Daftar cabang untuk kategori Captive berada pada tabel `institutions`, karena pada kategori tersebut level ketiga memang berisi nama cabang.

---

## 5. Konfigurasi Simulasi

### products

| Kolom        | Tipe         | Ketentuan           |
| ------------ | ------------ | ------------------- |
| id           | bigserial    | PK                  |
| name         | varchar(150) | unique, not null    |
| dp_rate      | numeric(6,4) | not null            |
| admin_min    | bigint       | not null            |
| admin_max    | bigint       | not null            |
| provisi_rate | numeric(6,4) | not null, default 0 |
| up_acp       | numeric(6,4) | not null, default 0 |
| up_rate      | numeric(6,4) | not null, default 0 |
| up_admin     | bigint       | not null, default 0 |
| up_provisi   | numeric(6,4) | not null, default 0 |
| is_active    | boolean      | default true        |

`check (admin_min <= admin_max)`

### product_rates

| Kolom          | Tipe           | Ketentuan                     |
| -------------- | -------------- | ----------------------------- |
| id             | bigserial      | PK                            |
| product_id     | bigint         | FK products                   |
| tenor_months   | smallint       | check in (12, 24, 36, 48, 60) |
| effective_rate | numeric(12,10) | nullable                      |

`unique (product_id, tenor_months)`

`effective_rate` bernilai NULL berarti tenor **tidak tersedia**. NULL tidak boleh diperlakukan sebagai 0. Ketentuannya pada `credit-simulation.md` bagian 15.

### vehicle_usages

| Kolom | Tipe        | Ketentuan                        |
| ----- | ----------- | -------------------------------- |
| id    | bigserial   | PK                               |
| name  | varchar(20) | check in (Passenger, Commercial) |

### vehicle_brands

| Kolom    | Tipe         | Ketentuan                   |
| -------- | ------------ | --------------------------- |
| id       | bigserial    | PK                          |
| usage_id | bigint       | FK vehicle_usages           |
| name     | varchar(100) | not null                    |
| origin   | varchar(20)  | check in (Japan, Non Japan) |

`unique (usage_id, name)`

Master kendaraan tersegmentasi berdasarkan penggunaan unit. Merk yang tersedia untuk Passenger berbeda dari Commercial.

### vehicle_types

| Kolom    | Tipe         | Ketentuan         |
| -------- | ------------ | ----------------- |
| id       | bigserial    | PK                |
| brand_id | bigint       | FK vehicle_brands |
| name     | varchar(100) | not null          |

`unique (brand_id, name)`

### vehicle_models

| Kolom   | Tipe         | Ketentuan        |
| ------- | ------------ | ---------------- |
| id      | bigserial    | PK               |
| type_id | bigint       | FK vehicle_types |
| name    | varchar(150) | not null         |

`unique (type_id, name)`

### vehicle_prices

| Kolom    | Tipe      | Ketentuan                    |
| -------- | --------- | ---------------------------- |
| id       | bigserial | PK                           |
| model_id | bigint    | FK vehicle_models            |
| year     | smallint  | not null                     |
| price    | bigint    | not null, check (price >= 0) |

`unique (model_id, year)`

Harga disimpan per tahun kendaraan. Ketiadaan baris berarti model tidak memiliki harga pada tahun tersebut, dan seluruh hasil tenor bernilai 0.

Kolom `price` inilah yang disebut **Harga PHPM**. Nilai ini tidak dibulatkan. Harga OTR merupakan hasil pembulatan yang dihitung engine, bukan kolom tersimpan.

### insurance_casco_rates

| Kolom    | Tipe           | Ketentuan                          |
| -------- | -------------- | ---------------------------------- |
| id       | bigserial      | PK                                 |
| zone     | varchar(30)    | not null                           |
| usage    | varchar(20)    | check in (Passenger, Commercial)   |
| variant  | varchar(20)    | check in (Batas Atas, Batas Bawah) |
| coverage | varchar(20)    | check in (Comprehensive, TLO)      |
| band_min | bigint         | not null                           |
| band_max | bigint         | nullable, NULL berarti tanpa batas |
| rate     | numeric(12,10) | not null                           |

`unique (zone, usage, variant, coverage, band_min)`

### insurance_loading_rates

| Kolom       | Tipe           | Ketentuan                        |
| ----------- | -------------- | -------------------------------- |
| id          | bigserial      | PK                               |
| vehicle_age | smallint       | unique, check (vehicle_age >= 0) |
| rate        | numeric(12,10) | not null                         |

### insurance_extension_rates

| Kolom | Tipe           | Ketentuan                                                                  |
| ----- | -------------- | -------------------------------------------------------------------------- |
| id    | bigserial      | PK                                                                         |
| code  | varchar(30)    | unique, check in (banjir, gempa, huru_hara, teroris, pengemudi, penumpang) |
| rate  | numeric(12,10) | not null                                                                   |

### acp_base_rates

| Kolom       | Tipe           | Ketentuan                     |
| ----------- | -------------- | ----------------------------- |
| id          | bigserial      | PK                            |
| tenor_years | smallint       | unique, check between 1 and 5 |
| rate        | numeric(12,10) | not null                      |

### acp_uppings

| Kolom        | Tipe         | Ketentuan             |
| ------------ | ------------ | --------------------- |
| id           | bigserial    | PK                    |
| age_group_id | bigint       | FK age_groups, unique |
| upping       | numeric(6,4) | not null              |

### domiciles

| Kolom      | Tipe        | Ketentuan        |
| ---------- | ----------- | ---------------- |
| id         | bigserial   | PK               |
| name       | varchar(80) | unique, not null |
| sort_order | smallint    | not null         |

Digunakan sebagai pilihan Domisili Debitur pada Credit Simulation.

### age_groups

| Kolom      | Tipe        | Ketentuan        |
| ---------- | ----------- | ---------------- |
| id         | bigserial   | PK               |
| label      | varchar(30) | unique, not null |
| sort_order | smallint    | not null         |

### tjh_tiers

| Kolom        | Tipe           | Ketentuan                   |
| ------------ | -------------- | --------------------------- |
| id           | bigserial      | PK                          |
| sequence     | smallint       | unique, not null            |
| limit_amount | bigint         | nullable, NULL berarti sisa |
| rate         | numeric(12,10) | not null                    |

### fiducia_tiers

| Kolom      | Tipe      | Ketentuan                          |
| ---------- | --------- | ---------------------------------- |
| id         | bigserial | PK                                 |
| min_amount | bigint    | not null                           |
| max_amount | bigint    | nullable, NULL berarti tanpa batas |
| fee        | bigint    | not null                           |

`unique (min_amount)`

### sum_insured_schedules

| Kolom      | Tipe         | Ketentuan                     |
| ---------- | ------------ | ----------------------------- |
| id         | bigserial    | PK                            |
| year_index | smallint     | unique, check between 1 and 5 |
| percentage | numeric(6,4) | not null                      |

### simulation_settings

| Kolom | Tipe         | Ketentuan        |
| ----- | ------------ | ---------------- |
| id    | bigserial    | PK               |
| key   | varchar(60)  | unique, not null |
| value | varchar(100) | not null         |

Menampung nilai tunggal seperti batas usia maksimal unit, biaya garansi mesin, nilai default simulasi, persentase refund, wilayah asuransi aktif, dan varian rate yang berlaku.

Tabel ini bukan tempat pembuangan. Parameter yang memiliki struktur harus memiliki tabel sendiri.

### admin_change_logs

| Kolom         | Tipe         | Ketentuan                                      |
| ------------- | ------------ | ---------------------------------------------- |
| id            | bigserial    | PK                                             |
| actor_id      | bigint       | FK users, not null                             |
| actor_name    | varchar(150) | snapshot nama pelaku                           |
| subject_type  | varchar(180) | class model                                    |
| subject_table | varchar(80)  | nama tabel untuk ringkasan halaman             |
| subject_id    | bigint       | ID record saat perubahan                       |
| action        | varchar(20)  | check in (created, updated, deleted)            |
| before_values | jsonb        | nullable, snapshot sebelum perubahan           |
| after_values  | jsonb        | nullable, snapshot setelah perubahan           |
| created_at    | timestamptz  | waktu perubahan                                |

Audit bersifat append-only dari antarmuka Admin. Baris dibuat dalam transaksi yang sama dengan perubahan konfigurasi atau master data sehingga rollback tidak meninggalkan audit palsu.

---

## 6. Application

### applications

| Kolom              | Tipe         | Ketentuan                                                                      |
| ------------------ | ------------ | ------------------------------------------------------------------------------ |
| id                 | bigserial    | PK                                                                             |
| code               | varchar(6)   | unique, not null                                                               |
| account_officer_id | bigint       | FK account_officers, not null                                                  |
| referral_id        | bigint       | FK referrals, not null                                                         |
| financing_product  | varchar(3)   | check in (DTN, UCF), not null                                                  |
| debtor_name        | varchar(150) | not null                                                                       |
| debtor_nik         | varchar(20)  | not null                                                                       |
| debtor_birth_date  | date         | not null                                                                       |
| debtor_type        | varchar(40)  | check in (Perorangan Non Wiraswasta, Perorangan Wiraswasta, Badan Hukum Usaha) |
| spouse_income_type | varchar(80)  | nullable                                                                       |
| amount_finance     | bigint       | nullable, check (amount_finance >= 0)                                          |
| unit_count         | smallint     | not null, default 1, check (unit_count >= 1)                                   |
| go_live_date       | date         | nullable                                                                       |
| timestamps         |              |                                                                                |

Ketentuan:

- `financing_product` menyatakan produk pembiayaan yang diajukan. Nilai terbatas pada produk yang berada dalam scope. Produk LMF dan NCF belum berlaku.
- `financing_product` dapat diubah selama application belum Go Live, dan terkunci setelahnya.
- `spouse_income_type` wajib NULL apabila `debtor_type` adalah Badan Hukum Usaha.
- `amount_finance` diinput AO. Tidak diambil dari hasil simulasi.
- `go_live_date` diisi observer, bukan controller. Ketentuannya pada AD-12.
- Tidak ada kolom data debitur selain tiga kolom di atas.

### document_requirements

| Kolom      | Tipe         | Ketentuan                                                     |
| ---------- | ------------ | ------------------------------------------------------------- |
| code       | varchar(20)  | PK                                                            |
| name       | varchar(150) | not null                                                      |
| subject    | varchar(20)  | check in (Pemohon, Pasangan, Komisaris, Direksi, Badan Usaha) |
| group_name | varchar(30)  | check in (Perorangan, Badan Hukum Usaha, Pasangan)            |
| sort_order | smallint     | not null                                                      |

Primary key berupa kode, bukan angka berurut. Kode bersifat stabil dan tidak boleh berubah, karena status dokumen merujuk padanya.

`unique (group_name, sort_order)`

### application_documents

| Kolom            | Tipe        | Ketentuan                          |
| ---------------- | ----------- | ---------------------------------- |
| id               | bigserial   | PK                                 |
| application_id   | bigint      | FK applications, on delete cascade |
| requirement_code | varchar(20) | FK document_requirements           |
| status           | varchar(10) | check in (Belum, Lengkap)          |
| updated_by       | bigint      | FK users, nullable                 |
| timestamps       |             |                                    |

`unique (application_id, requirement_code)`

Baris hanya dibuat untuk requirement yang berlaku. Tidak ada status yang menyatakan tidak berlaku.

### tracking_stages

| Kolom    | Tipe         | Ketentuan                  |
| -------- | ------------ | -------------------------- |
| stage_no | smallint     | PK, check between 1 and 11 |
| name     | varchar(150) | not null                   |

Isi tabel bersifat tetap. Tidak dapat ditambah maupun dihapus melalui antarmuka.

### application_trackings

| Kolom          | Tipe        | Ketentuan                          |
| -------------- | ----------- | ---------------------------------- |
| id             | bigserial   | PK                                 |
| application_id | bigint      | FK applications, on delete cascade |
| stage_no       | smallint    | FK tracking_stages                 |
| status         | varchar(10) | check in (Belum, Selesai)          |
| updated_by     | bigint      | FK users, nullable                 |
| timestamps     |             |                                    |

`unique (application_id, stage_no)`

Sebelas baris dibuat sekaligus saat application dibuat, seluruhnya berstatus Belum.

---

## 7. Aturan yang Tidak Dapat Dinyatakan sebagai Constraint

Aturan berikut ditegakkan di domain layer dan wajib memiliki pengujian.

| No  | Aturan                                                                                    |
| --- | ----------------------------------------------------------------------------------------- |
| 1   | `go_live_date` terisi jika dan hanya jika tahap 11 berstatus Selesai                      |
| 2   | Himpunan `application_documents` selalu sama dengan hasil resolver atas dua field penentu |
| 3   | Setiap application memiliki tepat 11 baris `application_trackings`                        |
| 4   | Setiap `users` memiliki tepat satu baris profil sesuai `role`                             |
| 5   | Perubahan `debtor_type` memicu rekonsiliasi dokumen dalam satu transaksi                  |

---

## 8. Indeks

| Tabel                 | Indeks                                      | Alasan                     |
| --------------------- | ------------------------------------------- | -------------------------- |
| applications          | `code` unique                               | pencarian dan keunikan     |
| applications          | `account_officer_id`                        | daftar milik AO            |
| applications          | `referral_id`                               | daftar milik Referral      |
| applications          | `go_live_date`                              | pemotongan periode Lending |
| applications          | `financing_product`                         | penyaringan laporan        |
| application_documents | `(application_id, requirement_code)` unique | keunikan dan pencarian     |
| application_trackings | `(application_id, stage_no)` unique         | keunikan dan pencarian     |
| vehicle_prices        | `(model_id, year)` unique                   | pencarian harga            |

---

## 9. Seeder

| Seeder                    | Isi                                                                |
| ------------------------- | ------------------------------------------------------------------ |
| ProductSeeder             | 17 product beserta rate lima tenor                                 |
| InsuranceSeeder           | casco, loading, perluasan, ACP, TJH                                |
| FeeSeeder                 | fiducia, sum insured schedule                                      |
| DocumentRequirementSeeder | 26 requirement                                                     |
| TrackingStageSeeder       | 11 tahapan                                                         |
| ReferralMasterSeeder      | 7 kategori, 42 sub-kategori, 190 instansi, domisili, kelompok usia |
| SimulationSettingSeeder   | nilai tunggal dan default simulasi                                 |
| VehicleSeeder             | 27 merk, 4.880 model, 26.791 baris harga                           |

Nilai awal konfigurasi berasal dari `credit-simulation-configuration.md` dan `document-requirement.md`.

Muatan awal master kendaraan dan master referral berasal dari draft perhitungan. Aturan ekstraksinya didokumentasikan pada `master-data-extraction.md`.

Seeder harus idempoten. Menjalankannya dua kali tidak menghasilkan duplikat.

---

## 10. Related Documentation

| Document                             | Purpose                           |
| ------------------------------------ | --------------------------------- |
| `architecture.md`                    | Keputusan teknis                  |
| `business.md`                        | Business context dan system scope |
| `actors.md`                          | Actors dan access                 |
| `credit-simulation-configuration.md` | Nilai awal konfigurasi            |
| `document-requirement.md`            | Katalog dan aturan keberlakuan    |
| `application-tracking.md`            | Struktur application              |
| `lending.md`                         | Agregasi Lending                  |
