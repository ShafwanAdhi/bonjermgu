@use('App\Support\Format')

@php
    $actualPercent = $charts['composition']['actualPercent'];
    $pipelinePercent = $charts['composition']['pipelinePercent'];
@endphp

<x-layouts.app title="Ringkasan - Kebon Jeruk Multiguna">
    <div class="band py-xl md:py-xxl">

        <div class="mb-8 flex flex-col gap-md md:mb-9 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="font-display text-display-md text-ink">Ringkasan</h1>
                <span class="mt-2 block text-body-md text-muted">{{ Format::date(now()) }} · seluruh cabang</span>
            </div>

            <form method="GET" class="flex w-full overflow-hidden rounded-md border border-hairline bg-surface-soft p-1 md:w-auto" aria-label="Filter periode dashboard">
                @foreach ($charts['periodOptions'] as $value => $label)
                    <button
                        type="submit"
                        name="period"
                        value="{{ $value }}"
                        data-motion-action
                        @class([
                            'min-h-10 flex-1 rounded-sm px-3 text-[13px] font-medium transition-colors md:flex-none',
                            'bg-primary text-on-primary shadow-sm' => $charts['period'] === (string) $value,
                            'text-muted hover:bg-canvas hover:text-ink' => $charts['period'] !== (string) $value,
                        ])
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </form>
        </div>

        <div class="grid grid-cols-1 gap-lg lg:grid-cols-[1.2fr_1.2fr_1fr]">
            <div class="rounded-lg bg-signature-forest p-xl text-on-dark">
                <p class="mb-md text-caption text-white/75">Actual Lending</p>
                <p class="font-display text-[44px] leading-[1.1]">{{ $totals->actualUnits }} unit</p>
                <p class="mt-2.5 text-[16px] leading-[1.5] tabular-nums">
                    {{ Format::rupiah($totals->actualAmount) }}
                </p>
                <p class="mt-1.5 text-helper text-white/65">Amount Finance telah Go Live · {{ $charts['periodLabel'] }}</p>
            </div>

            <div class="rounded-lg bg-signature-cream p-xl text-ink">
                <p class="mb-md text-caption text-muted">Pipe Line</p>
                <p class="font-display text-[44px] leading-[1.1]">{{ $totals->pipelineUnits }} unit</p>
                <p class="mt-2.5 text-[16px] leading-[1.5] tabular-nums">
                    {{ Format::rupiah($totals->pipelineAmount) }}
                </p>
                <p class="mt-1.5 text-helper text-muted">Belum Go Live · posisi saat ini</p>
            </div>

            <div class="grid grid-cols-2 gap-lg lg:grid-cols-1">
                <div class="rounded-md border border-hairline bg-surface-soft px-lg py-5">
                    <p class="mb-2 text-[13px] font-medium leading-[1.35] text-muted">Akun Referral</p>
                    <p class="font-display text-[32px] leading-[1.1] text-ink">{{ $referralAccounts }}</p>
                </div>
                <div class="rounded-md border border-hairline bg-surface-soft px-lg py-5">
                    <p class="mb-2 text-[13px] font-medium leading-[1.35] text-muted">Akun Account Officer</p>
                    <p class="font-display text-[32px] leading-[1.1] text-ink">{{ $officerAccounts }}</p>
                    @if ($inactiveAccounts > 0)
                        <p class="mt-1 text-helper text-muted">{{ $inactiveAccounts }} akun nonaktif</p>
                    @endif
                </div>
            </div>
        </div>

        <section class="mt-8 md:mt-10" aria-label="Visualisasi lending admin">
            <div class="grid grid-cols-1 gap-lg xl:grid-cols-[0.9fr_1.15fr_1.1fr]">
                <article class="rounded-lg border border-hairline bg-surface p-lg md:p-xl">
                    <div class="flex flex-col gap-lg sm:flex-row sm:items-center xl:flex-col xl:items-start">
                        <div
                            class="relative mx-auto aspect-square w-44 shrink-0 rounded-full sm:mx-0"
                            style="background: conic-gradient(#12351f 0 {{ $actualPercent }}%, #f5a66f {{ $actualPercent }}% {{ $actualPercent + $pipelinePercent }}%, #eef0ea {{ $actualPercent + $pipelinePercent }}% 100%);"
                            role="img"
                            aria-label="Komposisi Actual Lending {{ $actualPercent }} persen dan Pipe Line {{ $pipelinePercent }} persen"
                        >
                            <div class="absolute inset-7 flex flex-col items-center justify-center rounded-full bg-primary text-center text-on-primary shadow-[0_10px_30px_rgba(24,29,38,0.16)]">
                                <span class="text-[12px] font-medium uppercase tracking-[0.14em] text-white/70">Actual</span>
                                <span class="font-display text-[30px] leading-none text-white">{{ $actualPercent }}%</span>
                            </div>
                        </div>

                        <div class="w-full">
                            <p class="text-caption text-muted">Komposisi Lending</p>
                            <h3 class="mt-1 font-display text-[26px] leading-[1.15] text-ink">Actual vs Pipe Line</h3>

                            <div class="mt-5 space-y-3">
                                <div class="flex items-center justify-between gap-md">
                                    <span class="flex items-center gap-2 text-body-md text-muted">
                                        <span class="h-2.5 w-2.5 rounded-full bg-primary"></span>
                                        Actual
                                    </span>
                                    <span class="text-body-md font-medium tabular-nums text-ink">{{ $actualPercent }}%</span>
                                </div>
                                <div class="flex items-center justify-between gap-md">
                                    <span class="flex items-center gap-2 text-body-md text-muted">
                                        <span class="h-2.5 w-2.5 rounded-full bg-signature-peach"></span>
                                        Pipe Line
                                    </span>
                                    <span class="text-body-md font-medium tabular-nums text-ink">{{ $pipelinePercent }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="relative rounded-lg border border-hairline bg-surface p-lg md:p-xl">
                    <div class="pr-28">
                        <div>
                            <p class="text-caption text-muted">Performa Produk</p>
                            <h3 class="mt-1 font-display text-[26px] leading-[1.15] text-ink">Kontribusi A/F</h3>
                        </div>
                        <span class="absolute right-lg top-lg rounded-sm bg-surface-soft px-2.5 py-1 text-[12px] font-medium text-muted md:right-xl md:top-xl">{{ $charts['periodLabel'] }}</span>
                    </div>

                    <div class="mt-6 space-y-5">
                        @foreach ($charts['productMix'] as $product)
                            <div>
                                <div class="mb-2 flex items-start justify-between gap-md">
                                    <div>
                                        <p class="text-body-md font-medium text-ink">{{ $product['label'] }}</p>
                                        <p class="text-helper text-muted">{{ $product['actualUnits'] }} actual · {{ $product['pipelineUnits'] }} pipeline</p>
                                    </div>
                                    <p class="whitespace-nowrap text-right text-body-md font-medium tabular-nums text-ink">{{ Format::rupiah($product['totalAmount']) }}</p>
                                </div>
                                <div class="h-3 overflow-hidden rounded-full bg-surface-soft" aria-hidden="true">
                                    <div class="flex h-full" style="width: {{ $product['totalPercent'] }}%">
                                        <span class="bg-primary" style="width: {{ $product['actualPercent'] }}%"></span>
                                        <span class="bg-signature-peach" style="width: {{ $product['pipelinePercent'] }}%"></span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </article>

                <article class="rounded-lg border border-hairline bg-surface p-lg md:p-xl">
                    <div class="flex items-start justify-between gap-md">
                        <div>
                            <p class="text-caption text-muted">Trend Actual</p>
                            <h3 class="mt-1 font-display text-[26px] leading-[1.15] text-ink">Go Live Bulanan</h3>
                        </div>
                        <span class="rounded-sm bg-surface-soft px-2.5 py-1 text-[12px] font-medium text-muted">A/F</span>
                    </div>

                    <div class="mt-6 overflow-x-auto pb-1">
                        <div class="grid min-w-[460px] grid-cols-[4.75rem_1fr] gap-3 pt-4">
                            <div class="relative h-40">
                                @foreach ($charts['monthlyTrendAxis'] as $tick)
                                    <span
                                        class="absolute right-0 translate-y-1/2 whitespace-nowrap text-[11px] font-medium leading-none text-muted"
                                        style="bottom: {{ $tick['percent'] }}%"
                                    >
                                        {{ $tick['label'] }}
                                    </span>
                                @endforeach
                            </div>
                            <div class="relative h-40 border-b border-hairline">
                                @foreach ($charts['monthlyTrendAxis'] as $tick)
                                    <span
                                        class="absolute inset-x-0 border-t border-divider"
                                        style="bottom: {{ $tick['percent'] }}%"
                                        aria-hidden="true"
                                    ></span>
                                @endforeach
                                <div class="absolute inset-x-0 bottom-0 flex h-full items-end gap-2 px-1">
                                    @foreach ($charts['monthlyTrend'] as $month)
                                        <div class="flex h-full flex-1 items-end">
                                            <div
                                                class="w-full rounded-t-sm bg-primary/85 transition-all hover:bg-primary"
                                                style="height: {{ max(4, $month['percent']) }}%"
                                                title="{{ $month['label'] }}: {{ Format::rupiah($month['amount']) }} · {{ $month['units'] }} unit"
                                                aria-label="{{ $month['label'] }} {{ Format::rupiah($month['amount']) }}"
                                            ></div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div aria-hidden="true"></div>
                            <div class="mt-2 flex gap-2 px-1">
                                @foreach ($charts['monthlyTrend'] as $month)
                                    <span class="flex-1 text-center text-[11px] font-medium text-muted">{{ $month['label'] }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </section>
    </div>
</x-layouts.app>
