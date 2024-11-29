@php
    $columns = $this->getColumns();
    $pollingInterval = $this->getPollingInterval();

    $heading = $this->getHeading();
    $description = $this->getDescription();
    $hasHeading = filled($heading);
    $hasDescription = filled($description);
@endphp

<x-filament-widgets::widget
    :attributes="
        (new \Illuminate\View\ComponentAttributeBag)
            ->merge([
                'wire:poll.' . $pollingInterval => $pollingInterval,
            ])
            ->class([
                'fi-wi-stats-overview',
            ])
    "
>
    @if ($hasHeading || $hasDescription)
        <div class="fi-wi-stats-overview-header">
            @if ($hasHeading)
                <h3 class="fi-wi-stats-overview-header-heading">
                    {{ $heading }}
                </h3>
            @endif

            @if ($hasDescription)
                <p class="fi-wi-stats-overview-header-description">
                    {{ $description }}
                </p>
            @endif
        </div>
    @endif

    <div @class([
        'fi-wi-stats-overview-stats-grid',
        'fi-grid-cols-' . $columns,
    ])>
        @foreach ($this->getCachedStats() as $stat)
            {{ $stat }}
        @endforeach
    </div>
</x-filament-widgets::widget>
