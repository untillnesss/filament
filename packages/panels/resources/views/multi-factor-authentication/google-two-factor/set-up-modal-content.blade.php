<div class="flex flex-col gap-3 text-sm">
    <p>Scan this QR code with your authenticator app:</p>

    <img
        alt="QR code to scan with an authenticator app"
        src="{{ $qr }}"
        class="mx-auto size-48 rounded-lg border border-gray-300 dark:border-transparent"
    />

    <p>
        Need the secret code instead?
        <button
            class="inline"
            type="button"
            x-on:click="
                window.navigator.clipboard.writeText(@js($secret))
                $tooltip('Copied code', {
                    theme: $store.theme,
                })
            "
        >
            <span class="font-mono">{{ $secret }}</span>
            <span class="font-sans text-xs text-gray-500 dark:text-gray-400">
                (click to copy)
            </span>
        </button>
    </p>

    @if ($isRecoverable)
        <div
            class="flex flex-col gap-3 rounded-lg bg-gray-50 p-4 ring-1 ring-inset ring-gray-950/5 dark:bg-white/5 dark:ring-white/10"
        >
            <p class="font-bold">
                Please save the following recovery codes in a safe place. You
                won't see them again, but you'll need them if you lose access to
                your app:
            </p>

            @include('filament-panels::multi-factor-authentication.google-two-factor.recovery-codes-modal-content', [
                'recoveryCodes' => $recoveryCodes,
            ])
        </div>
    @endif
</div>
