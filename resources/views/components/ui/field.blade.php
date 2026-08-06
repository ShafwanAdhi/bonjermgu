@props([
    'label' => null,
    'required' => false,
    'helper' => null,
    'error' => null,
])

<div {{ $attributes->class('flex flex-col gap-1.5') }}>
    @if ($label)
        <span class="text-caption text-body">
            {{ $label }}@if ($required)<span class="text-signature-coral"> *</span>@endif
        </span>
    @endif

    {{ $slot }}

    @if ($error)
        <span class="text-helper text-signature-coral">{{ $error }}</span>
    @elseif ($helper)
        <span class="text-helper text-muted">{{ $helper }}</span>
    @endif
</div>