@props(['align' => 'left'])

<th scope="col" {{ $attributes->class([
    'whitespace-nowrap border-b border-hairline bg-surface-soft px-5 py-3.5',
    'text-[12px] font-medium uppercase leading-[1.35] tracking-[0.08em] text-muted',
    $align === 'right' ? 'text-right' : 'text-left',
]) }}>{{ $slot }}</th>
