<form
    {{
        $attributes
            ->merge([
                'id' => $getId(),
                'wire:submit' => $getLivewireSubmitHandler(),
            ], escape: false)
            ->merge($getExtraAttributes(), escape: false)
            ->class(['fi-fo-form flex flex-col', $isDense() ? 'gap-3' : 'gap-6'])
    }}
>
    {{ $getChildComponentContainer($schemaComponent::HEADER_SLOT) }}

    {{ $getChildComponentContainer() }}

    {{ $getChildComponentContainer($schemaComponent::FOOTER_SLOT) }}
</form>
