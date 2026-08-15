@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';

$pageName = $paginator->getPageName();
$paginationId = 'pagination-'.$pageName.'-'.$paginator->currentPage();
$summary = $paginator->firstItem()
    ? $paginator->firstItem().' - '.$paginator->lastItem().' dari '.$paginator->total().' data'
    : $paginator->count().' dari '.$paginator->total().' data';
$buttonBase = 'inline-flex h-10 min-w-10 items-center justify-center gap-1.5 rounded-sm border border-transparent px-3 text-[13px] font-medium transition duration-150 ease-out focus:outline-none focus-visible:ring-2 focus-visible:ring-info-border focus-visible:ring-offset-2 focus-visible:ring-offset-canvas active:scale-[0.98] motion-reduce:transition-none motion-reduce:active:scale-100';
$pageButton = 'inline-flex h-10 w-10 items-center justify-center rounded-sm text-[13px] font-medium transition duration-150 ease-out focus:outline-none focus-visible:ring-2 focus-visible:ring-info-border focus-visible:ring-offset-2 focus-visible:ring-offset-canvas active:scale-[0.98] motion-reduce:transition-none motion-reduce:active:scale-100';
$mobileButtonBase = 'inline-flex min-h-11 w-full items-center justify-center rounded-md px-4 text-[13px] font-medium transition duration-150 ease-out focus:outline-none focus-visible:ring-2 focus-visible:ring-info-border focus-visible:ring-offset-2 focus-visible:ring-offset-canvas active:scale-[0.98] motion-reduce:transition-none motion-reduce:active:scale-100';
@endphp

@if ($paginator->hasPages())
    <nav id="{{ $paginationId }}" role="navigation" aria-label="Navigasi halaman tabel"
         class="sm:rounded-lg sm:border sm:border-hairline sm:bg-canvas sm:p-xs">
        <div class="rounded-lg border border-hairline bg-canvas p-sm sm:hidden">
            <div class="mb-sm rounded-md bg-surface-soft px-sm py-xs">
                <div class="flex items-center justify-between gap-sm">
                    <div class="min-w-0">
                        <p class="text-[11px] font-medium uppercase leading-none tracking-[0.08em] text-muted">
                            Menampilkan
                        </p>
                        <p class="mt-1 truncate text-[13px] font-medium leading-[1.35] text-ink">
                            {{ $summary }}
                        </p>
                    </div>
                    <p class="shrink-0 rounded-pill border border-divider bg-canvas px-3 py-1 text-helper font-medium text-ink">
                        Halaman {{ $paginator->currentPage() }}/{{ $paginator->lastPage() }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-sm">
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true"
                          class="{{ $mobileButtonBase }} cursor-not-allowed border border-divider bg-surface-soft text-border-strong">
                        Sebelumnya
                    </span>
                @else
                    <button type="button"
                            wire:click="previousPage('{{ $pageName }}')"
                            x-on:click="{{ $scrollIntoViewJsSnippet }}"
                            wire:loading.attr="disabled"
                            class="{{ $mobileButtonBase }} border border-hairline bg-canvas text-ink hover:border-border-strong hover:bg-surface-soft">
                        Sebelumnya
                    </button>
                @endif

                @if ($paginator->hasMorePages())
                    <button type="button"
                            wire:click="nextPage('{{ $pageName }}')"
                            x-on:click="{{ $scrollIntoViewJsSnippet }}"
                            wire:loading.attr="disabled"
                            class="{{ $mobileButtonBase }} bg-primary text-on-primary hover:bg-primary-active">
                        Berikutnya
                    </button>
                @else
                    <span aria-disabled="true"
                          class="{{ $mobileButtonBase }} cursor-not-allowed border border-divider bg-surface-soft text-border-strong">
                        Berikutnya
                    </span>
                @endif
            </div>
        </div>

        <div class="hidden items-center justify-between gap-md sm:flex">
            <div class="min-w-0 px-sm">
                <p class="text-[13px] leading-[1.4] text-muted">
                    <span class="font-medium text-ink">{{ $summary }}</span>
                    <span class="text-border-strong">/</span>
                    Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}
                </p>
            </div>

            <div class="flex shrink-0 items-center gap-xs">
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" aria-label="Halaman sebelumnya"
                          class="{{ $buttonBase }} cursor-not-allowed bg-surface-soft text-border-strong">
                        <span aria-hidden="true">&lt;</span>
                        <span>Sebelumnya</span>
                    </span>
                @else
                    <button type="button"
                            wire:click="previousPage('{{ $pageName }}')"
                            x-on:click="{{ $scrollIntoViewJsSnippet }}"
                            wire:loading.attr="disabled"
                            class="{{ $buttonBase }} text-body hover:bg-surface-soft hover:text-ink"
                            aria-label="Halaman sebelumnya">
                        <span aria-hidden="true">&lt;</span>
                        <span>Sebelumnya</span>
                    </button>
                @endif

                <div class="flex items-center rounded-md bg-surface-soft p-1">
                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span aria-hidden="true"
                                  class="inline-flex h-10 w-10 items-center justify-center text-[13px] font-medium text-muted">
                                {{ $element }}
                            </span>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                <span wire:key="paginator-{{ $pageName }}-page{{ $page }}">
                                    @if ($page == $paginator->currentPage())
                                        <span aria-current="page"
                                              class="{{ $pageButton }} bg-primary text-on-primary">
                                            {{ $page }}
                                        </span>
                                    @else
                                        <button type="button"
                                                wire:click="gotoPage({{ $page }}, '{{ $pageName }}')"
                                                x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                                wire:loading.attr="disabled"
                                                class="{{ $pageButton }} text-body hover:bg-canvas hover:text-ink"
                                                aria-label="Ke halaman {{ $page }}">
                                            {{ $page }}
                                        </button>
                                    @endif
                                </span>
                            @endforeach
                        @endif
                    @endforeach
                </div>

                @if ($paginator->hasMorePages())
                    <button type="button"
                            wire:click="nextPage('{{ $pageName }}')"
                            x-on:click="{{ $scrollIntoViewJsSnippet }}"
                            wire:loading.attr="disabled"
                        class="{{ $buttonBase }} bg-primary text-on-primary hover:bg-primary-active"
                        aria-label="Halaman berikutnya">
                        <span>Berikutnya</span>
                        <span aria-hidden="true">&gt;</span>
                    </button>
                @else
                    <span aria-disabled="true" aria-label="Halaman berikutnya"
                          class="{{ $buttonBase }} cursor-not-allowed bg-surface-soft text-border-strong">
                        <span>Berikutnya</span>
                        <span aria-hidden="true">&gt;</span>
                    </span>
                @endif
            </div>
        </div>
    </nav>
@endif
