{{--
    Landing. Marketing page for prospective Referral partners.

    Per docs/pages.md section 4 this page carries no calculator, no application
    lookup panel, and no data from the operational database. Do not add one.
--}}
<x-layouts.public title="Kebon Jeruk Multiguna — Program Mitra Referral">

    {{-- Hero: white canvas, no gradient, no backdrop. Whitespace does the framing. --}}
    <section class="band pb-xl pt-xl text-center md:pb-xxl md:pt-xxl">
        <p class="mb-5 text-eyebrow uppercase text-muted" data-reveal="hero">Program Mitra Referral</p>

        <h1 class="mx-auto mb-5 max-w-[640px] font-display text-display-lg text-ink" data-reveal="hero" style="--reveal-delay: 90ms">
            Bawa Debiturnya biarkan kami yg menghitung
        </h1>

        <p class="mx-auto mb-9 max-w-[520px] text-[16px] leading-[1.6] text-body" data-reveal="hero" style="--reveal-delay: 180ms">
            Kebon Jeruk Multiguna membantu mitra Referral membuat simulasi kredit untuk calon
            debitur dan memantau perjalanan aplikasinya dari pooling sampai Go Live.
        </p>

        <div class="flex flex-wrap justify-center gap-sm" data-reveal="hero" style="--reveal-delay: 270ms">
            <x-ui.button :href="route('register')">Registrasi</x-ui.button>
            <x-ui.button variant="secondary" :href="route('login')">Masuk</x-ui.button>
        </div>
    </section>

    {{-- Referral types. --}}
    <section class="band pb-xl md:pb-xxl">
        <h2 class="mb-2 text-center text-title-lg text-ink sm:text-left" data-reveal>Terbuka untuk berbagai jenis mitra</h2>
        <p class="mb-xl text-center text-body-md text-muted sm:text-left" data-reveal style="--reveal-delay: 80ms">Siapa pun yang bertemu calon debitur setiap hari.</p>

        <div class="grid gap-lg sm:grid-cols-2 lg:grid-cols-5">
            @foreach ([
                ['Sales Authorized Dealer', 'Tawarkan skema pembiayaan langsung di showroom.', 'bg-signature-peach'],
                ['Karyawan asuransi Rekanan', 'Lengkapi layanan Anda dengan pembiayaan kendaraan.', 'bg-signature-mint'],
                ['Karyawan Internal & Captive', 'Bantu rekan kerja dan nasabah dapatkan dana tunai.', 'bg-signature-cream'],
                ['Komunitas otomotif', 'Jadi rujukan pembiayaan di komunitas Anda.', 'bg-signature-yellow'],
                ['Perorangan', 'Registrasi mandiri, akun langsung aktif.', 'bg-surface-soft border border-hairline'],
            ] as $index => [$title, $body, $surface])
                <div class="h-full rounded-md p-lg pb-10 {{ $surface }}" data-reveal="card" style="--reveal-delay: {{ $index * 70 }}ms">
                    <p class="mb-1.5 text-label-md text-ink">{{ $title }}</p>
                    <p class="text-[14px] leading-[1.5] text-body">{{ $body }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Signature coral card — the page's voltage moment. --}}
    <section class="band pb-xl md:pb-xxl">
        <div class="rounded-lg bg-signature-coral p-xxl text-on-dark" data-reveal>
            <h2 class="mb-xl max-w-[520px] font-display text-display-md" data-reveal style="--reveal-delay: 90ms">
                Dua produk pembiayaan. Satu alur simulasi.
            </h2>

            <div class="flex flex-wrap items-start gap-xxl">
                <div class="min-w-[260px] flex-1 border-t border-white/35 pt-5" data-reveal style="--reveal-delay: 160ms">
                    <p class="mb-2 text-title-sm">Dana Tunai</p>
                    <p class="text-[14px] leading-[1.6] text-white/85">
                        Pembiayaan multiguna dengan jaminan BPKB mobil. Simulasi menghitung
                        pencairan maksimal dan angsuran lima pilihan tenor.
                    </p>
                </div>

                <div class="min-w-[260px] flex-1 border-t border-white/35 pt-5" data-reveal style="--reveal-delay: 230ms">
                    <p class="mb-2 text-title-sm">Pembiayaan Mobil Bekas</p>
                    <p class="text-[14px] leading-[1.6] text-white/85">
                        Pembiayaan pembelian mobil bekas. Simulasi menghitung pencairan
                        all-in atau total DP yang dikehendaki.
                    </p>
                </div>

                <div class="flex min-w-[200px] items-start" data-reveal style="--reveal-delay: 300ms">
                    <x-ui.button variant="secondary-on-dark" :href="route('register')">
                        Registrasi sekarang
                    </x-ui.button>
                </div>
            </div>
        </div>
    </section>

    {{-- Three steps, on the cream callout surface. --}}
    <section class="band pb-xl md:pb-xxl">
        <div class="rounded-md bg-signature-cream p-xxl" data-reveal>
            <h2 class="mb-xl text-title-lg text-ink" data-reveal style="--reveal-delay: 90ms">Tiga langkah menjadi mitra</h2>

            <div class="flex flex-wrap gap-xxl">
                @foreach ([
                    ['Registrasi', 'Daftar mandiri secara online. Akun langsung aktif tanpa menunggu persetujuan.'],
                    ['Isi Form Online', 'Buat simulasi kredit untuk calon debitur — hasil lima tenor dihitung otomatis.'],
                    ['Ajukan', 'Serahkan hasil simulasi kepada Account Officer dan pantau aplikasinya sampai Go Live.'],
                ] as $index => [$title, $body])
                    <div class="min-w-[220px] flex-1" data-reveal style="--reveal-delay: {{ 160 + ($index * 80) }}ms">
                        <p class="mb-sm font-display text-display-md text-signature-coral">{{ $index + 1 }}</p>
                        <p class="mb-1.5 text-label-md text-ink">{{ $title }}</p>
                        <p class="text-[14px] leading-[1.6] text-body">{{ $body }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Testimonials. --}}
    <section class="band pb-xl md:pb-xxl">
        @php
            $testimonials = [
                [
                    'name' => 'Tri Yuliyatno',
                    'role' => 'SM-Honda Jakarta Center',
                    'quote' => 'Simulasinya sangat mudah digunakan, semoga prosesnya semudah simulasinya.',
                    'image' => asset('images/testimonials/tri.jpeg'),
                ],
                [
                    'name' => 'Gito Purnomo',
                    'role' => 'BM DSO BSD',
                    'quote' => 'Website sangat membantu, top & keren...',
                    'image' => asset('images/testimonials/gito-purnomo.jpeg'),
                ],
                [
                    'name' => 'Riki',
                    'role' => 'Sales Toyota Tunas Pecenongan',
                    'quote' => 'Website mudah digunakan, sangat membantu, mantab....',
                    'image' => asset('images/testimonials/riki.png'),
                ],
                [
                    'name' => 'Ary Prayoga',
                    'role' => null,
                    'quote' => 'Bener... ini yg di butuhin sama tim sales dealer saat ini, sangat simple & bisa trekking aplikasi.',
                    'image' => asset('images/testimonials/ari-prayoga.png'),
                ],
            ];
        @endphp

        <div class="mb-xl grid gap-md lg:grid-cols-[minmax(0,0.8fr)_minmax(280px,0.42fr)] lg:items-end">
            <div data-reveal>
                <p class="mb-2 text-eyebrow uppercase text-muted">Testimoni</p>
                <h2 class="max-w-[620px] font-display text-display-md text-ink">
                    Cerita singkat dari mitra yang mencoba simulasi.
                </h2>
            </div>


        </div>

        <div class="grid gap-lg md:grid-cols-2 xl:grid-cols-4">
            @foreach ($testimonials as $index => $testimonial)
                <figure class="flex h-full min-h-[176px] flex-col rounded-md border border-hairline bg-canvas p-lg md:min-h-[200px] xl:min-h-[220px]" data-reveal="card" style="--reveal-delay: {{ 120 + ($index * 70) }}ms">
                    <div class="mb-lg flex items-center gap-md">
                        <img src="{{ $testimonial['image'] }}"
                             alt="Foto {{ $testimonial['name'] }}"
                             class="h-14 w-14 shrink-0 rounded-full object-cover"
                             width="56"
                             height="56"
                             loading="lazy">

                        <figcaption class="min-w-0">
                            <p class="text-label-md text-ink">{{ $testimonial['name'] }}</p>
                            @if ($testimonial['role'])
                                <p class="mt-1 text-helper text-muted">{{ $testimonial['role'] }}</p>
                            @endif
                        </figcaption>
                    </div>

                    <blockquote class="mt-md text-[14px] leading-[1.6] text-body xl:mt-auto">
                        "{{ $testimonial['quote'] }}"
                    </blockquote>
                </figure>
            @endforeach
        </div>
    </section>

    {{-- Light gray CTA band before the footer. --}}
    <section class="band pb-xl md:pb-xxl">
        <div class="flex flex-wrap items-center gap-lg rounded-lg bg-surface-strong p-xxl" data-reveal>
            <h2 class="m-0 min-w-[280px] flex-1 font-display text-display-md text-ink">
                Mulai jadi mitra Referral hari ini.
            </h2>
            <div class="flex flex-wrap gap-sm" data-reveal style="--reveal-delay: 120ms">
                <x-ui.button :href="route('register')">Registrasi</x-ui.button>
                <x-ui.button variant="secondary" :href="route('login')">Masuk</x-ui.button>
            </div>
        </div>
    </section>

</x-layouts.public>
