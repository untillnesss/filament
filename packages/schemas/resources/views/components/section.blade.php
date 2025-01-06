@php
    $isAside = $isAside();
    $isDivided = $isDivided();
@endphp

@if (filled($label = $getLabel()))
    <div class="mb-2 flex items-center gap-x-3">
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

<x-filament::section
    :after-header="$getDecorations($schemaComponent::AFTER_HEADER_DECORATIONS)"
    :aside="$isAside"
    :collapsed="$isCollapsed()"
    :collapsible="$isCollapsible() && (! $isAside)"
    :compact="$isCompact()"
    :contained="$isContained()"
    :content-before="$isFormBefore()"
    :description="$getDescription()"
    :divided="$isDivided"
    :footer="$getDecorations($schemaComponent::FOOTER_DECORATIONS)"
    :has-content-el="false"
    :heading="$getHeading()"
    :icon="$getIcon()"
    :icon-color="$getIconColor()"
    :icon-size="$getIconSize()"
    :persist-collapsed="$shouldPersistCollapsed()"
    :secondary="$isSecondary()"
    :attributes="
        \Filament\Support\prepare_inherited_attributes($attributes)
            ->merge([
                'id' => $getId(),
            ], escape: false)
            ->merge($getExtraAttributes(), escape: false)
            ->merge($getExtraAlpineAttributes(), escape: false)
    "
>
    {{ $getChildComponentContainer()->gap(! $isDivided)->extraAttributes(['class' => 'fi-section-content']) }}
</x-filament::section>

@if ($belowContentDecorations = $getDecorations($schemaComponent::BELOW_CONTENT_DECORATIONS))
    <div class="mt-2">
        {{ $belowContentDecorations }}
    </div>
@endif
