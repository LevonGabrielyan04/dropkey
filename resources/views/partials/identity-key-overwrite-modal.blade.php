<flux:modal
    name="identity-key-overwrite"
    :closable="false"
    :dismissible="false"
    class="max-w-md !rounded-none !border-2 !border-zinc-950 dark:!border-zinc-100"
    data-test="identity-key-overwrite-modal"
>
    <div class="space-y-6 font-mono">
        <div class="space-y-3 border-b-2 border-zinc-950 pb-4 dark:border-zinc-100">
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-red-600 dark:text-red-400">
                {{ __('Destructive action') }}
            </p>

            <flux:heading size="lg" class="!font-mono !font-black !uppercase !tracking-tight">
                {{ __('Replace encryption key?') }}
            </flux:heading>

            <flux:text class="!font-mono !text-sm">
                {{ __('Replacing your encryption key will permanently remove access to your old messages. This cannot be undone.') }}
            </flux:text>

            <flux:callout
                variant="warning"
                icon="exclamation-triangle"
                class="!rounded-none !border-2 !border-zinc-950 dark:!border-zinc-100"
            >
                <flux:text class="!font-mono !text-sm">
                    {{ __('Your previous decryption key was not found on this device.') }}
                </flux:text>

                <flux:text class="!mt-2 !font-mono !text-sm">
                    {{ __('To restore your messages, sign in on the same device and browser where you originally encrypted them.') }}
                </flux:text>
            </flux:callout>
        </div>

        <div class="flex justify-end gap-2">
            <flux:button
                type="button"
                variant="filled"
                class="!rounded-none !border-2 !border-zinc-950 !font-bold !uppercase !tracking-[0.16em] dark:!border-zinc-100"
                data-test="identity-key-overwrite-cancel"
                x-on:click="cancelIdentityKeyOverwrite()"
            >
                {{ __('Cancel') }}
            </flux:button>

            <flux:button
                type="button"
                variant="danger"
                class="!rounded-none !border-2 !border-red-700 !font-bold !uppercase !tracking-[0.16em]"
                data-test="identity-key-overwrite-confirm"
                x-on:click="confirmIdentityKeyOverwrite()"
            >
                {{ __('Replace key') }}
            </flux:button>
        </div>
    </div>
</flux:modal>
