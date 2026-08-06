{{-- One label/value pair in a detail grid. --}}
@props([
    'label' => '',
    'note' => null,
    'tone' => 'default',
])

<div {{ $attributes->class('') }}>
    <p class="text-helper text-border-strong">{{ $label }}</p>
    <p @class([
        'text-[14px] leading-[1.5]',
        'text-ink' => $tone === 'default',
        'font-medium text-success' => $tone === 'success',
    ])>{{ $slot }}</p>
    @if ($note)
        <p class="text-helper text-border-strong">{{ $note }}</p>
    @endif
</div>