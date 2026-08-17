{{--
    `disabled` is a prop, not a bare @disabled directive in the caller's tag.
    A Blade directive inside an <x-...> tag defeats the component-tag parser
    and the tag ends up rendered as literal text.
--}}
@props(['invalid' => false, 'disabled' => false])

@php($locked = (bool) $disabled)

<span class="relative block">
    <select
        @disabled($disabled)
        @if ($invalid) aria-invalid="true" @endif
        {{ $attributes->class([
            'h-11 w-full rounded-sm border bg-canvas px-4 text-body-md text-ink',
            'focus:border-info-border focus:ring-0',
            'disabled:bg-surface-soft disabled:text-border-strong',
            'appearance-none bg-none pr-10' => $locked,
            $invalid ? 'border-signature-coral' : 'border-hairline',
        ]) }}
    >
        {{ $slot }}
    </select>

    @if ($locked)
        <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-border-strong"
             xmlns="http://www.w3.org/2000/svg"
             viewBox="0 0 24 24"
             fill="none"
             stroke="currentColor"
             stroke-width="2"
             stroke-linecap="round"
             stroke-linejoin="round"
             aria-hidden="true">
            <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
        </svg>
    @endif
</span>
