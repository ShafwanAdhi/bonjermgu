{{--
    Wraps the table in its own horizontal scroller. Wide tables must scroll
    inside this frame — the page body itself never scrolls sideways.
--}}
@props(['minWidth' => null])

<div {{ $attributes->class('overflow-x-auto rounded-lg border border-hairline') }}>
    <table class="w-full border-collapse" @if ($minWidth) style="min-width: {{ $minWidth }}" @endif>
        @isset($head)
            <thead>
                <tr>{{ $head }}</tr>
            </thead>
        @endisset
        <tbody>{{ $slot }}</tbody>
    </table>
</div>