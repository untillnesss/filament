<ul @class([
    'ms-3 list-disc sm:columns-2',
    match ($size = $getSize()) {
        'xs' => 'text-xs',
        null => 'text-sm',
        default => $size,
    },
])
    @foreach ($getChildComponentContainer()->getComponents() as $component)
        <li>
            {{ $component }}
        </li>
    @endforeach
</ul>
