@props([
    'label' => null,
    'required' => false,
    'helper' => null,
    'error' => null,
    'for' => null,
])

<div
    data-ui-field
    @if ($error) data-ui-field-invalid="true" @endif
    @if ($for) data-ui-field-id="{{ $for }}" @endif
    {{ $attributes->except('for')->class('flex flex-col gap-1.5') }}
>
    @if ($label)
        <label @if ($for) for="{{ $for }}" @endif data-ui-field-label class="text-caption text-body">
            {{ $label }}@if ($required)<span class="text-signature-coral"> *</span>@endif
        </label>
    @endif

    {{ $slot }}

    @if ($error)
        <span data-ui-field-message role="alert" class="text-helper text-signature-coral">{{ $error }}</span>
    @elseif ($helper)
        <span data-ui-field-message class="text-helper text-muted">{{ $helper }}</span>
    @endif
</div>
