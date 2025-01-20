@php
    $isAside = $isAside();
    $isDivided = $isDivided();
@endphp

@if (filled($label = $getLabel()))
    <div class="mb-2 flex items-center gap-x-3">
        {{ $getChildComponentContainer($schemaComponent::BEFORE_LABEL_CONTAINER) }}

        <div
            class="text-sm font-medium leading-6 text-gray-950 dark:text-white"
        >
            {{ $label }}
        </div>

        {{ $getChildComponentContainer($schemaComponent::AFTER_LABEL_CONTAINER) }}
    </div>
@endif

@if ($aboveContentContainer = $getChildComponentContainer($schemaComponent::ABOVE_CONTENT_CONTAINER))
    <div class="mb-2">
        {{ $aboveContentContainer }}
    </div>
@endif

<x-filament::section
    :after-header="$getChildComponentContainer($schemaComponent::AFTER_HEADER_CONTAINER)"
    :aside="$isAside"
    :collapsed="$isCollapsed()"
    :collapsible="$isCollapsible() && (! $isAside)"
    :compact="$isCompact()"
    :contained="$isContained()"
    :content-before="$isFormBefore()"
    :description="$getDescription()"
    :divided="$isDivided"
    :footer="$getChildComponentContainer($schemaComponent::FOOTER_CONTAINER)"
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

@if ($belowContentContainer = $getChildComponentContainer($schemaComponent::BELOW_CONTENT_CONTAINER))
    <div class="mt-2">
        {{ $belowContentContainer }}
    </div>
@endif
