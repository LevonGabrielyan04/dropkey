@props([
    'optionsRoute' => 'passkey.login-options',
    'submitRoute' => 'passkey.login',
    'label' => __('Sign in with a passkey'),
    'loadingLabel' => __('Authenticating...'),
    'separator' => __('Or continue with email'),
])

<div
    x-data="passkeyVerify"
    data-options-route="{{ route($optionsRoute) }}"
    data-submit-route="{{ route($submitRoute) }}"
>
    <template x-if="supported">
        <div>
            <div class="grid gap-2">
                <flux:button
                    variant="outline"
                    icon="finger-print"
                    class="w-full !rounded-none !border-2 !border-zinc-950 !px-4 !py-3 !text-xs !font-bold !uppercase !tracking-[0.18em] !text-zinc-950 hover:!bg-zinc-200 dark:!border-zinc-100 dark:!text-zinc-50 dark:hover:!bg-zinc-800"
                    x-on:click="verify"
                    x-bind:disabled="loading"
                >
                    <span x-show="!loading">{{ $label }}</span>
                    <span x-show="loading" x-cloak>{{ $loadingLabel }}</span>
                </flux:button>
                <p x-show="error" x-text="error" x-cloak
                   class="text-center text-sm text-red-600 dark:text-red-400"></p>
            </div>

            <div class="relative my-6">
                <div class="border-t-2 border-zinc-950 dark:border-zinc-100"></div>
                <div class="relative -mt-3 flex justify-center">
                    <span class="bg-white px-3 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                        {{ $separator }}
                    </span>
                </div>
            </div>
        </div>
    </template>
</div>
