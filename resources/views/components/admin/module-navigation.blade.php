@props([
    'items' => [],
    'columns' => 'three',
])

<nav {{ $attributes->class([
        'mb-xl grid grid-cols-1 gap-sm min-[360px]:grid-cols-2 md:gap-md lg:grid-cols-3',
    ]) }}
     aria-label="Navigasi modul">
    @foreach ($items as $item)
        @php
            $active = (bool) ($item['active'] ?? false);
            $route = $item['route'] ?? '';
        @endphp

        <a href="{{ $item['url'] }}"
           data-motion-action
           @if ($active) aria-current="page" @endif
           @class([
               'group flex h-[172px] flex-col rounded-lg border p-md text-left transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary min-[360px]:h-[176px] lg:h-[156px]',
               'border-primary bg-canvas shadow-[0_12px_32px_rgba(24,29,38,0.08)]' => $active,
               'border-hairline bg-canvas hover:border-border-strong hover:bg-surface-soft' => ! $active,
           ])>
            <span class="mb-md flex items-center justify-between gap-sm">
                <span @class([
                    'flex h-10 w-10 shrink-0 items-center justify-center rounded-md transition-colors',
                    'bg-primary text-on-primary' => $active,
                    'bg-surface-soft text-muted group-hover:bg-canvas group-hover:text-ink' => ! $active,
                ])>
                    <x-ui.nav-icon :route="$route" class="h-[18px] w-[18px]" />
                </span>
            </span>

            <span class="block text-[14px] font-medium leading-[1.35] text-ink">
                {{ $item['label'] }}
            </span>

            @if (! empty($item['description']))
                <span class="mt-1 block text-[12px] leading-[1.45] text-muted">
                    {{ $item['description'] }}
                </span>
            @endif
        </a>
    @endforeach
</nav>
