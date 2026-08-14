# Session Changes 2026-08-11

Dokumen ini mencatat perubahan penting yang dikerjakan dalam sesi pengembangan 2026-08-11. Spesifikasi permanen tetap berada di dokumen domain masing-masing; file ini berfungsi sebagai ringkasan lintas fitur agar keputusan UI/UX dan perilaku aplikasi tidak hilang.

---

## 1. Prinsip UI/UX Umum

- Semua perubahan visual harus mempertimbangkan desktop dan mobile.
- Refinement mengikuti bahasa desain aplikasi yang sudah ada: warna, tipografi, radius, spacing, dan komposisi utama dipertahankan.
- Page title dan subtitle/meta harus disusun vertikal pada desktop dan mobile. Action button boleh berada di kanan pada desktop, tetapi subtitle tidak boleh sejajar horizontal dengan title.
- Animasi digunakan untuk memperjelas feedback atau memberi polish, bukan untuk mengubah desain utama.
- Scroll animation tidak digunakan pada halaman yang secara eksplisit dimatikan, seperti detail aplikasi, profil, hasil simulasi, dan page Admin yang dibuka lewat module grid.
- Button click effect digunakan pada kontrol pilihan penting, termasuk pilihan produk pembiayaan dan dasar simulasi.
- Accessibility polish lintas halaman dilakukan melalui komponen bersama: skip link ke konten utama, `aria-current` pada navigasi aktif, field label/error/helper yang terhubung ke input, live region untuk loading dan callout, role dialog pada popup/modal, label tabel, state `aria-pressed` pada segmented control, dan fallback `prefers-reduced-motion`.

---

## 2. Landing, Login, dan Registrasi

- Landing page dirapikan pada jarak antar-section, jarak navbar ke hero, alignment mobile, ukuran logo/navbar mobile, dan konsistensi card mitra.
- Section Testimoni ditambahkan dengan dua testimoni awal. Foto subjek sementara boleh memakai placeholder sampai aset resmi tersedia.
- Layout Testimoni desktop memakai grid kartu penuh lebar agar tidak menyisakan ruang kosong janggal di sisi kiri, sementara mobile tetap satu kolom tanpa overflow.
- Login dan register memakai tombol kembali bergaya flagship, bukan breadcrumb.
- Login memakai icon mata di dalam input kata sandi.
- Fitur "ingat saya" harus menyimpan sesi sesuai perilaku autentikasi Laravel.
- Login memiliki alur "Lupa kata sandi" berbahasa Indonesia. User memasukkan Nama User, sistem mengirim link reset ke email yang tersimpan di profil role, dan token reset disimpan dalam bentuk hash.
- Jika akun belum memiliki email, user diminta memasang email terlebih dahulu melalui profil atau meminta Admin memasangkannya.
- Email reset kata sandi memakai template HTML khusus dengan CTA jelas, masa berlaku token, dan fallback link yang bisa disalin.
- Register wajib memiliki placeholder yang membantu, nomor handphone wajib, dan keterangan bahwa Nama User dipakai untuk login.

---

## 3. Referral

- Dashboard Referral menampilkan ucapan ulang tahun ketika tanggal lahir user sama dengan tanggal hari ini.
- Ucapan ulang tahun dibedakan secara visual, termasuk teks rainbow dan ukuran yang lebih dinamis.
- Dekorasi ulang tahun berjalan sebagai overlay hampir seluruh layar dan bertahan sekitar tujuh detik.
- Halaman aplikasi Referral menampilkan status dokumen dan tracking terbaru yang diperbarui oleh AO.
- Halaman daftar aplikasi Referral boleh memakai scroll animation; detail aplikasi tetap tanpa scroll animation.

---

## 4. Profil

- Halaman profil Referral, AO, dan Admin memiliki alur edit profil yang konsisten.
- Ketika user klik "Ubah Profil", halaman otomatis scroll ke area Data Diri dengan offset yang dapat diatur di JavaScript profil.
- Tanggal lahir dapat diubah sendiri oleh setiap user, bukan hanya oleh Admin.
- Pada mobile, avatar/logo profil wajib tetap berbentuk lingkaran sempurna.
- Setiap profil role memiliki dua opsi keamanan: "Kirim Link Reset" untuk alur lupa password via email, dan form ganti kata sandi yang tetap meminta Kata Sandi Saat Ini.
- Tombol "Kirim Link Reset" pada profil disabled bila akun belum memiliki email.
- Profil Admin kini menyimpan alamat email agar fitur reset password konsisten dengan Referral dan Account Officer.

---

## 5. Simulasi Kredit

- Form simulasi menyimpan nilai sementara agar input tetap ada ketika user berpindah halaman.
- Tombol hapus data mengembalikan form ke state default.
- Input nominal uang otomatis diformat profesional dengan prefix `Rp` dan pemisah ribuan titik, misalnya `Rp 50.000.000`.
- Jika Type Debitur adalah Badan Hukum Usaha, NIK dan tanggal lahir tidak diminta pada alur simulasi maupun validasi download hasil.
- Field "Total DP yang dikehendaki" muncul dengan animasi saat dasar simulasi berubah ke Total DP.
- Halaman hitung simulasi tidak memakai scroll animation selain feedback klik.
- Page hasil simulasi tidak memakai scroll animation.
- Watermark `bonjemgu.com` hanya muncul pada PDF yang diunduh, bukan pada halaman browser `/simulation/print`.
- Pada simulasi AO, klik pilihan tenor Rincian Perhitungan tidak lagi memicu kalkulasi ulang atau terasa seperti refresh; trace lima tenor disimpan setelah hitung.
- Card collapsible pada simulasi AO, seperti Asuransi serta Upping dan Pengurang Pencairan, kini membuka dan menutup dengan animasi height/opacity yang halus.
- Halaman Uji Konfigurasi Admin diselaraskan dengan layout simulasi AO: step cards, hasil lima tenor di panel kanan, scroll otomatis ke hasil setelah Hitung Simulasi, selector tenor ter-highlight, kolom Jejak dihapus, dan copy internal yang tidak perlu dipangkas.
- Form Uji Konfigurasi Admin menyimpan nilai input sementara di session per user agar tetap terisi saat Admin berpindah halaman. Tombol Hapus Data menghapus state sementara tersebut tanpa menyimpan hasil hitung sebagai cache permanen.
- Pada halaman Uji Konfigurasi Admin, "Rate Product Terpilih" diubah dari grid tile menjadi table ringkas agar lebih terbaca dan tidak overlap di mobile.
- Migration idempotent ditambahkan untuk memastikan seluruh `simulation_settings` wajib tersedia pada database existing, termasuk `acp_max_loan_amount` dan `ucf_non_japan_net_dp_rate`. Engine simulasi juga memakai fallback default resmi agar database lama yang kehilangan row default tidak menampilkan error teknis ke user.
- Page `configuration/fees` kini menampilkan field Net DP `UCF · Non Japan`, karena nilai tersebut dipakai oleh perhitungan Mobil Bekas.
- Laporan Lending Admin diperbaiki agar `/lending/ao` benar-benar mengelompokkan berdasarkan Account Officer dan `/lending/referrals` mengelompokkan berdasarkan Referral.

---

## 6. Account Officer

- AO dapat membuat aplikasi baru dengan pencarian Referral yang terlihat clickable melalui pola dropdown/suggestion, bukan daftar yang tampak pasif.
- Keterangan internal otomatis setelah aplikasi tersimpan dihapus dari form AO.
- Layout form input aplikasi dirapikan agar field desktop sejajar dan tidak menyisakan ruang kosong untuk dropdown saat suggestion tidak terbuka.
- Untuk Type Debitur Badan Hukum Usaha, form input aplikasi AO tidak menampilkan NIK dan tanggal lahir.
- Toggle status dokumen dan tracking pada detail aplikasi dibuat lebih simpel dan seragam.
- Dashboard AO mendapat animation polish dan halaman input aplikasi mendapat click effect.
- Pada simulasi AO, tabel Hasil Lima Tenor tidak lagi menampilkan kolom Jejak; pilihan tenor Rincian Perhitungan dipusatkan sebagai segmented control, dan setelah Hitung Simulasi halaman otomatis scroll ke Hasil Lima Tenor.
- Form simulasi AO menyimpan nilai input sementara di session per user agar tetap terisi saat user berpindah halaman. Tombol Hapus Data menghapus state sementara tersebut tanpa menyimpan hasil hitung sebagai cache permanen.
- Aplikasi kini memiliki state `Canceled` untuk status yang masih Pipe Line. AO dapat membatalkan aplikasi non-Go Live dari detail aplikasi, status tampil sinkron di daftar aplikasi, dan filter daftar aplikasi memiliki opsi Canceled.

---

## 7. Admin Dashboard

- Dashboard Admin mengganti keterangan tekstual internal dengan visualisasi data.
- Minimal dua grafik ditampilkan untuk membantu membaca komposisi dan trend Lending.
- Bagian bawah dashboard menampilkan AO dan Referral paling aktif berdasarkan total unit Actual + Pipe Line dari agregasi Lending yang sama dengan dashboard. Nama Referral ditampilkan bersama kategorinya, misalnya `Budi Aktif (Dealer Prioritas)`.
- Filter waktu dashboard berisi `1 bulan`, `3 bulan`, `12 bulan`, dan `Semua`; default adalah `Semua`.
- Scroll animation diperbolehkan khusus pada dashboard Admin.
- Navbar desktop Admin dirapikan agar avatar/logo bulat sempurna dan navigasi padat memakai pola "Lainnya" tanpa scrollbar navbar.

---

## 8. Admin Module Navigation

- Admin memakai module grid untuk halaman induk:
  - `/configuration`
  - `/master`
  - `/accounts`
  - `/lending`
- Setiap module grid membuka route subpage sendiri, bukan mengganti konten inline di bawah module.
- Desktop maksimal tiga module per baris; mobile maksimal dua.
- Semua tile module memiliki ukuran sama, icon berbeda, dan tidak menampilkan label bantuan seperti "buka modul" atau "buka".
- Page module/leaf Admin tidak memakai entrance animation.
- Page Master Kendaraan dan Master Referral memakai layout workspace + inspector untuk mengurangi kepadatan form: daftar/struktur utama berada di kiri pada desktop, editor aktif berada di kanan, sedangkan mobile menumpuk alur lalu form.
- Pada page Master Kendaraan dan Master Referral, animasi hanya dipakai untuk feedback klik, collapse/expand card, panel aktif, dan scroll otomatis ke panel/form yang dibuka. Scroll reveal dan entrance animation tetap tidak dipakai.
- Master Referral memakai kategori collapsible agar daftar sub-kategori dan instansi besar tidak langsung memenuhi layar. Daftar instansi panjang dibatasi dengan scroll internal agar card tetap nyaman dipindai.

---

## 9. Admin Accounts

- Admin memiliki page profil sendiri di `/accounts/profile`, diakses dari module grid "Akun Anda".
- Page akun Referral dan AO tidak menampilkan keterangan internal yang tidak membantu user.
- Edit profil Referral, edit profil AO, dan buat akun AO masing-masing menjadi page sendiri.
- Tombol kembali pada page edit/create mengarah ke daftar asalnya, bukan selalu ke `/accounts`.
- Tombol "Buat Akun AO" berada di bawah tabel AO.
- Form buat/edit akun memiliki placeholder.
- Setelah Admin membuat akun AO, password awal ditampilkan sebagai popup sederhana dengan tombol icon salin dan feedback tersalin.

---

## 10. Admin Configuration Audit

- Subpage konfigurasi harus tampil sebagai layar kerja yang ringkas: title page cukup muncul sekali, tanpa judul section yang mengulang title halaman.
- Copy aturan internal seperti dampak perubahan ke simulasi berikutnya atau perbedaan penyimpanan persen tidak ditampilkan sebagai keterangan global di setiap module.
- Hint tetap boleh muncul jika langsung membantu pengisian field, misalnya placeholder atau note singkat untuk aturan input yang rawan salah.
- Pada mobile/tablet page `configuration/products`, memilih item di "Daftar Product" otomatis membawa user ke form editor, dan selected state product harus terlihat jelas.
- Page `configuration/insurance` memakai matriks Casco/TLO responsif: desktop menampilkan header bertingkat tanpa horizontal scroll, sedangkan mobile/tablet mengelompokkan rentang harga, Casco, dan TLO per band dalam section tanpa card bertumpuk. Control surface Casco/TLO mengurutkan Wilayah, Varian, Hapus Matriks, Wilayah baru, lalu Tambah Wilayah. Loading Usia Kendaraan memakai row compact, TJH memakai layout table-like di desktop dan field berlabel per lapisan di mobile, sedangkan Perluasan dan ACP memakai grid yang tetap nyaman disentuh di layar kecil.
- Page `configuration/fees` memakai row responsif untuk Fiducia Fee dan Sum Insured, serta grid input yang full-width pada mobile untuk Net DP dan Refund.
- `admin_change_logs` memiliki kolom `audit_module`.
- Setiap module konfigurasi/master menulis `audit_module` agar ringkasan "perubahan terakhir" tidak saling bercampur ketika beberapa module memakai tabel yang sama.
- `configuration.fees` dan `configuration.defaults` sama-sama dapat menulis ke `simulation_settings`, tetapi ringkasan perubahan terakhirnya harus tetap terpisah.
- Kode audit aman ketika migration belum berjalan, tetapi setelah kolom tersedia query ringkasan wajib memfilter module.

---

## 11. Admin Lending

- Page Lending induk memakai module grid untuk menuju Lending Per AO dan Lending Per Referral.
- Filter periode date range diganti menjadi filter bulan Go Live.
- Filter produk dan kategori Referral tetap tersedia.
- Tombol "Terapkan" dihapus. Perubahan filter memperbarui tabel secara asynchronous dengan Livewire.
- Query string tetap disinkronkan agar URL laporan dapat dibagikan.
- Layout filter mobile dirapikan agar simetris dan tidak terasa penuh.

---

## 12. Testing dan Database

- Isolasi database test PostgreSQL diperbaiki agar parallel test tidak saling menyebabkan schema collision atau duplicate table.
- Full test suite tidak boleh langsung dijalankan ketika sedang investigasi hang. Test harus dipecah per file/group dengan hard timeout.
- Command panjang yang menjalankan server atau Playwright wajib memiliki timeout tegas dan tidak boleh dibiarkan tanpa progress.
- Operasi destruktif dilarang terhadap database non-testing.

## 13. Email

- Konfigurasi email didokumentasikan di `docs/email-configuration.md`.
- `.env.example` memakai `MAIL_MAILER=log` untuk lokal dan menyediakan variabel SMTP production yang aman diisi lewat `.env`.
- SMTP timeout diset melalui `MAIL_TIMEOUT` agar pengiriman reset password tidak menggantung terlalu lama jika mail server bermasalah.
