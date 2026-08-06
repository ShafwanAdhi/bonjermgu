{{--
    Landing. Marketing page for prospective Referral partners.

    Per docs/pages.md section 4 this page carries no calculator, no application
    lookup panel, and no data from the operational database. Do not add one.
--}}
<x-layouts.public title="Kebon Jeruk Multiguna — Program Mitra Referral">

    {{-- Hero: white canvas, no gradient, no backdrop. Whitespace does the framing. --}}
    <section class="band py-section text-center">
        <p class="mb-5 text-eyebrow uppercase text-muted">Program Mitra Referral</p>

        <h1 class="mx-auto mb-5 max-w-[640px] font-display text-display-lg text-ink">
            Bawa Debiturnya biarkan kami yg menghitung
        </h1>

        <p class="mx-auto mb-9 max-w-[520px] text-[16px] leading-[1.6] text-body">
            Kebon Jeruk Multiguna membantu mitra Referral membuat simulasi kredit untuk calon
            debitur dan memantau perjalanan aplikasinya — dari pooling sampai Go Live.
        </p>

        <div class="flex flex-wrap justify-center gap-sm">
            <x-ui.button :href="route('register')">Registrasi</x-ui.button>
            <x-ui.button variant="secondary" :href="route('login')">Masuk</x-ui.button>
        </div>
    </section>

    {{-- Referral types. Card heights are deliberately uneven — a uniform grid
         reads as a spec sheet. --}}
    <section class="band pb-section">
        <h2 class="mb-2 text-title-lg text-ink">Terbuka untuk berbagai jenis mitra</h2>
        <p class="mb-xl text-body-md text-muted">Siapa pun yang bertemu calon debitur setiap hari.</p>

        <div class="flex flex-wrap items-start gap-lg">
            @foreach ([
                ['Sales Authorized Dealer', 'Tawarkan skema pembiayaan langsung di showroom.', 'bg-signature-peach', 'pb-10'],
                ['Karyawan asuransi Rekanan', 'Lengkapi layanan Anda dengan pembiayaan kendaraan.', 'bg-signature-mint', 'pb-16'],
                ['Karyawan Internal & Captive', 'Bantu rekan kerja dan nasabah dapatkan dana tunai.', 'bg-signature-cream', 'pb-12'],
                ['Komunitas otomotif', 'Jadi rujukan pembiayaan di komunitas Anda.', 'bg-signature-yellow', 'pb-14'],
                ['Perorangan', 'Registrasi mandiri, akun langsung aktif.', 'bg-surface-soft border border-hairline', 'pb-10'],
            ] as [$title, $body, $surface, $pad])
                <div class="min-w-[200px] flex-1 rounded-md p-lg {{ $pad }} {{ $surface }}">
                    <p class="mb-1.5 text-label-md text-ink">{{ $title }}</p>
                    <p class="text-[14px] leading-[1.5] text-body">{{ $body }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Signature coral card — the page's voltage moment. --}}
    <section class="band pb-section">
        <div class="rounded-lg bg-signature-coral p-xxl text-on-dark">
            <h2 class="mb-xl max-w-[520px] font-display text-display-md">
                Dua produk pembiayaan. Satu alur simulasi.
            </h2>

            <div class="flex flex-wrap items-start gap-xxl">
                <div class="min-w-[260px] flex-1 border-t border-white/35 pt-5">
                    <p class="mb-2 text-title-sm">Dana Tunai</p>
                    <p class="text-[14px] leading-[1.6] text-white/85">
                        Pembiayaan multiguna dengan jaminan BPKB mobil. Simulasi menghitung
                        pencairan maksimal dan angsuran lima pilihan tenor.
                    </p>
                </div>

                <div class="min-w-[260px] flex-1 border-t border-white/35 pt-5">
                    <p class="mb-2 text-title-sm">Pembiayaan Mobil Bekas</p>
                    <p class="text-[14px] leading-[1.6] text-white/85">
                        Pembiayaan pembelian mobil bekas. Simulasi menghitung pencairan
                        all-in atau total DP yang dikehendaki.
                    </p>
                </div>

                <div class="flex min-w-[200px] items-start">
                    <x-ui.button variant="secondary-on-dark" :href="route('register')">
                        Registrasi sekarang
                    </x-ui.button>
                </div>
            </div>
        </div>
    </section>

    {{-- Three steps, on the cream callout surface. --}}
    <section class="band pb-section">
        <div class="rounded-md bg-signature-cream p-xxl">
            <h2 class="mb-xl text-title-lg text-ink">Tiga langkah menjadi mitra</h2>

            <div class="flex flex-wrap gap-xxl">
                @foreach ([
                    ['Registrasi', 'Daftar mandiri secara online. Akun langsung aktif tanpa menunggu persetujuan.'],
                    ['Isi Form Online', 'Buat simulasi kredit untuk calon debitur — hasil lima tenor dihitung otomatis.'],
                    ['Ajukan', 'Serahkan hasil simulasi kepada Account Officer dan pantau aplikasinya sampai Go Live.'],
                ] as $index => [$title, $body])
                    <div class="min-w-[220px] flex-1">
                        <p class="mb-sm font-display text-display-md text-signature-coral">{{ $index + 1 }}</p>
                        <p class="mb-1.5 text-label-md text-ink">{{ $title }}</p>
                        <p class="text-[14px] leading-[1.6] text-body">{{ $body }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Light gray CTA band before the footer. --}}
    <section class="band pb-section">
        <div class="flex flex-wrap items-center gap-lg rounded-lg bg-surface-strong p-xxl">
            <h2 class="m-0 min-w-[280px] flex-1 font-display text-display-md text-ink">
                Mulai jadi mitra Referral hari ini.
            </h2>
            <div class="flex flex-wrap gap-sm">
                <x-ui.button :href="route('register')">Registrasi</x-ui.button>
                <x-ui.button variant="secondary" :href="route('login')">Masuk</x-ui.button>
            </div>
        </div>
    </section>

</x-layouts.public>
