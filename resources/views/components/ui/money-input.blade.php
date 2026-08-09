@props(['invalid' => false])

<input
    type="text"
    inputmode="numeric"
    autocomplete="off"
    data-rupiah-input
    {{ $attributes->except(['type', 'min', 'max', 'step', 'inputmode'])->class([
        'h-11 w-full rounded-sm border bg-canvas px-4 text-body-md text-ink placeholder:text-border-strong',
        'focus:border-info-border focus:ring-0',
        $invalid ? 'border-signature-coral' : 'border-hairline',
    ]) }}
>
