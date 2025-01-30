@php
    use Filament\Support\Enums\IconSize;
    use Filament\Support\View\Components\Dropdown\Header;
    use Illuminate\View\ComponentAttributeBag;
@endphp

@props([
    'color' => 'gray',
    'icon' => null,
    'iconSize' => IconSize::Medium,
    'tag' => 'div',
])

@php
    if (! ($iconSize instanceof IconSize)) {
        $iconSize = IconSize::tryFrom($iconSize) ?? $iconSize;
    }
@endphp

<{{ $tag }}
    {{
        $attributes
            ->class([
                'fi-dropdown-header',
            ])
            ->color(Header::class, $color)
    }}
>
    {{
        \Filament\Support\generate_icon_html($icon, attributes: (new ComponentAttributeBag)
            ->class([
                ($iconSize instanceof IconSize) ? "fi-size-{$iconSize->value}" : (is_string($iconSize) ? $iconSize : null),
            ]))
    }}

    <span>
        {{ $slot }}
    </span>
</{{ $tag }}>
