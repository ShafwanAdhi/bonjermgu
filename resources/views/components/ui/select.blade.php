{{--
    `disabled` is a prop, not a bare @disabled directive in the caller's tag.
    A Blade directive inside an <x-...> tag defeats the component-tag parser
    and the tag ends up rendered as literal text.
--}}
@props(['invalid' => false, 'disabled' => false])

<select
    @disabled($disabled)
    {{ $attributes->class([
        'h-11 w-full rounded-sm border bg-canvas px-4 text-body-md text-ink',
        'focus:border-info-border focus:ring-0',
        'disabled:bg-surface-soft disabled:text-border-strong',
        $invalid ? 'border-signature-coral' : 'border-hairline',
    ]) }}
>
    {{ $slot }}
</select>
