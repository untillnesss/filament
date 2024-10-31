<div class="flex flex-col gap-3 text-sm">
    <ul class="ms-3 list-disc font-mono text-xs sm:columns-2">
        @foreach ($recoveryCodes as $recoveryCode)
            <li>
                <button
                    type="button"
                    x-on:click="
                        window.navigator.clipboard.writeText(@js($recoveryCode))
                        $tooltip('Copied recovery code', {
                            theme: $store.theme,
                        })
                    "
                >
                    {{ $recoveryCode }}
                </button>
            </li>
        @endforeach
    </ul>

    <p>
        You can also

        <x-filament::link
            tag="button"
            :x-on:click="
                '
                    window.navigator.clipboard.writeText(' . \Illuminate\Support\Js::from(implode(PHP_EOL, $recoveryCodes)) . ')
                    $tooltip(\'Copied all recovery codes to clipboard\', {
                        theme: $store.theme,
                    })
                '
            "
        >
            copy
        </x-filament::link>

        or

        <x-filament::link
            :href="'data:application/octet-stream,' . urlencode(implode(PHP_EOL, $recoveryCodes))"
            download
        >
            download
        </x-filament::link>

        all the recovery codes at once.
    </p>
</div>
