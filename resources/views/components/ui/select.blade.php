{{--
    `disabled` is a prop, not a bare @disabled directive in the caller's tag.
    A Blade directive inside an <x-...> tag defeats the component-tag parser
    and the tag ends up rendered as literal text.
--}}
@props(['invalid' => false, 'disabled' => false])

@php
    $class = (string) $attributes->get('class');
    $wrapperClass = str_contains($class, 'w-auto')
        ? 'relative inline-block'
        : 'relative block w-full';
@endphp

<span class="{{ $wrapperClass }}">
    <select
        @disabled($disabled)
        {{ $attributes->class([
            'h-11 w-full appearance-none rounded-md border bg-canvas py-2.5 pl-4 pr-10 text-body-md text-ink shadow-[0_1px_0_rgba(13,18,24,0.03)] transition-colors',
            'hover:border-border-strong focus:border-info-border focus:ring-0',
            'disabled:bg-surface-soft disabled:text-border-strong',
            $invalid ? 'border-signature-coral' : 'border-hairline',
        ]) }}
    >
        {{ $slot }}
    </select>

    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-border-strong">
        <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4" aria-hidden="true">
            <path d="M6 8l4 4 4-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </span>
</span>
