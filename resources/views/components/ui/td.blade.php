@props([
    'align' => 'left',
    'mono' => false,
    'numeric' => false,
])

<td {{ $attributes->class([
    'border-b border-divider px-5 py-3.5 text-body-md',
    $mono ? 'font-mono text-[13px] font-medium text-ink' : 'text-body',
    $numeric ? 'tabular-nums' : '',
    $align === 'right' ? 'text-right' : 'text-left',
]) }}>{{ $slot }}</td>