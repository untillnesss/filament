@if (filled($id = $getId()) || filled($extraAttributes = $getExtraAttributes()))
    <div
        {{
            $attributes
                ->merge([
                    'id' => $getId(),
                ], escape: false)
                ->merge($getExtraAttributes(), escape: false)
        }}
    >
@endif
@if (filled($key = $getLivewireKey()))
    @livewire($getComponent(), $getComponentProperties(), key($key))
@else
    @livewire($getComponent(), $getComponentProperties())
@endif
@if (filled($id) || filled($extraAttributes))
    </div>
@endif
