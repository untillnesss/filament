@php
    use Filament\Support\Enums\VerticalAlignment;

    $verticalAlignment = $getVerticalAlignment();

    if (! $verticalAlignment instanceof VerticalAlignment) {
        $verticalAlignment = filled($verticalAlignment) ? (VerticalAlignment::tryFrom($verticalAlignment) ?? $verticalAlignment) : null;
    }
@endphp

@if (filled($label = $getLabel()))
    <div class="flex items-center gap-x-3 mb-2">
        {{ $getDecorations($schemaComponent::BEFORE_LABEL_DECORATIONS) }}

        <div
            class="text-sm font-medium leading-6 text-gray-950 dark:text-white"
        >
            {{ $label }}
        </div>

        {{ $getDecorations($schemaComponent::AFTER_LABEL_DECORATIONS) }}
    </div>
@endif

@if ($aboveContentDecorations = $getDecorations($schemaComponent::ABOVE_CONTENT_DECORATIONS))
    <div class="mb-2">
        {{ $aboveContentDecorations }}
    </div>
@endif

<div
    {{
        $attributes
            ->merge([
                'id' => $getId(),
            ], escape: false)
            ->merge($getExtraAttributes(), escape: false)
            ->class([
                'fi-fo-actions flex h-full flex-col',
                match ($verticalAlignment) {
                    VerticalAlignment::Start => 'justify-start',
                    VerticalAlignment::Center => 'justify-center',
                    VerticalAlignment::End => 'justify-end',
                    default => $verticalAlignment,
                },
            ])
    }}
>
    <x-filament::actions
        :actions="$getChildComponentContainer()->getComponents()"
        :alignment="$getAlignment()"
        :full-width="$isFullWidth()"
    />
</div>

@if ($belowContentDecorations = $getDecorations($schemaComponent::BELOW_CONTENT_DECORATIONS))
    <div class="mt-2">
        {{ $belowContentDecorations }}
    </div>
@endif
