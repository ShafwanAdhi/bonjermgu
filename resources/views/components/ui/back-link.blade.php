@props([
    'href',
    'label' => 'Kembali',
])

<a href="{{ $href }}"
   {{ $attributes->class('group inline-flex min-h-10 items-center gap-2.5 text-body-md text-muted transition-colors hover:text-ink focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary') }}>
    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-hairline bg-canvas text-ink transition-colors group-hover:border-border-strong"
          aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
            <path d="m15 18-6-6 6-6" />
        </svg>
    </span>
    <span>{{ $label }}</span>
</a>
