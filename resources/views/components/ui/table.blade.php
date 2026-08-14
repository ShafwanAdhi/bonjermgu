{{--
    Wraps the table in its own horizontal scroller. Wide tables must scroll
    inside this frame — the page body itself never scrolls sideways.
--}}
@props([
    'minWidth' => null,
    'label' => 'Tabel data',
])

<div
    role="region"
    aria-label="{{ $label }}"
    tabindex="0"
    {{ $attributes->class('overflow-x-auto rounded-lg border border-hairline focus-visible:ring-2 focus-visible:ring-info-border focus-visible:ring-offset-2') }}
>
    <table class="w-full border-collapse" @if ($minWidth) style="min-width: {{ $minWidth }}" @endif>
        @isset($head)
            <thead>
                <tr>{{ $head }}</tr>
            </thead>
        @endisset
        <tbody>{{ $slot }}</tbody>
    </table>
</div>
