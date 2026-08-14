{{--
    Tab rail. Each item is {label, url, active}. Tabs are real links so a tab
    panel is addressable and survives a refresh.
--}}
@props(['items' => []])

<nav {{ $attributes->class('mb-xl flex gap-lg overflow-x-auto border-b border-hairline') }} aria-label="Navigasi tab">
    @foreach ($items as $item)
        <a href="{{ $item['url'] }}"
           @if ($item['active']) aria-current="page" @endif
           @class([
               '-mb-px whitespace-nowrap border-b-2 px-0.5 pb-3 text-[14px] font-medium leading-[1.4]',
               'border-primary text-ink' => $item['active'],
               'border-transparent text-muted' => ! $item['active'],
           ])>{{ $item['label'] }}</a>
    @endforeach
</nav>
