@php
    use Filament\Support\Enums\VerticalAlignment;

    $verticalAlignment = $getVerticalAlignment();

    if (! $verticalAlignment instanceof VerticalAlignment) {
        $verticalAlignment = filled($verticalAlignment) ? (VerticalAlignment::tryFrom($verticalAlignment) ?? $verticalAlignment) : null;
    }
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

<div
    @if ($isSticky())
        x-data="{
            isSticky: false,

            evaluatePageScrollPosition: function () {
                this.isSticky =
                    document.body.scrollHeight >=
                    window.scrollY + window.innerHeight * 2
            },
        }"
        x-init="evaluatePageScrollPosition"
        x-on:scroll.window="evaluatePageScrollPosition"
        x-bind:class="{
            'fi-sticky sticky bottom-0 -mx-4 transform bg-white p-4 shadow-lg ring-1 ring-gray-950/5 transition dark:bg-gray-900 dark:ring-white/10 md:bottom-4 md:rounded-xl': isSticky,
        }"
    @endif
    {{
        $attributes
            ->merge([
                'id' => $getId(),
            ], escape: false)
            ->merge($getExtraAttributes(), escape: false)
            ->class([
                'fi-sc-actions flex h-full flex-col',
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
