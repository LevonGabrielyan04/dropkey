@props(['send'])

<section class="border-x-2 border-b-2 border-zinc-950 dark:border-zinc-100">
    <div class="grid grid-cols-1 border-b-2 border-zinc-950 sm:grid-cols-3 dark:border-zinc-100">
        <div class="border-b-2 border-zinc-950 px-4 py-4 sm:border-b-0 sm:border-r-2 dark:border-zinc-100">
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">
                {{ __('Public ID') }}
            </p>
            <p class="mt-2 break-all font-mono text-sm tabular-nums text-zinc-950 dark:text-zinc-50">
                {{ $send->public_id }}
            </p>
        </div>

        <div class="border-b-2 border-zinc-950 px-4 py-4 sm:border-b-0 sm:border-r-2 dark:border-zinc-100">
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">
                {{ __('Expires') }}
            </p>
            <p class="mt-2 text-sm tabular-nums text-zinc-950 dark:text-zinc-50">
                <span
                    x-data="localDatetime"
                    data-utc-datetime="{{ \Illuminate\Support\Carbon::parse($send->valid_to)->utc()->toIso8601String() }}"
                    x-text="formatted"
                ></span>
            </p>
        </div>

        <div class="px-4 py-4">
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">
                {{ __('Authorized viewers') }}
            </p>
            <p class="mt-2 text-sm text-zinc-800 dark:text-zinc-200">
                {{ $send->authorizedUsers->pluck('name')->join(', ') ?: __('None') }}
            </p>
        </div>
    </div>

    <div
        x-data="sendDetailsManager"
        data-raw-message='@json($send->message)'
        data-min-password-length="{{ config('send.password.min_length') }}"
        class="border-b-2 border-zinc-950 dark:border-zinc-100"
    >
        <div class="border-b-2 border-zinc-950 bg-zinc-200 px-4 py-3 dark:border-zinc-100 dark:bg-zinc-800">
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-600 dark:text-zinc-400">
                {{ __('Payload') }}
            </p>
        </div>

        <div class="bg-white p-6 dark:bg-zinc-950">
            <div
                x-show="!isEncrypted"
                x-cloak
                class="border-2 border-zinc-950 bg-zinc-50 p-4 font-mono text-sm whitespace-pre-wrap break-words text-zinc-900 dark:border-zinc-100 dark:bg-zinc-900 dark:text-zinc-100"
                x-text="displayMessage"
            ></div>

            <div x-show="isEncrypted && decryptedMessage === null" x-cloak class="space-y-5">
                <div class="border-2 border-emerald-500 bg-emerald-500/10 px-4 py-3">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700 dark:text-emerald-400">
                        {{ __('Encrypted at rest') }}
                    </p>
                    <p class="mt-2 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">
                        {{ __('This message is password protected. Enter the password to decrypt it.') }}
                    </p>
                </div>

                <div class="space-y-2">
                    <label for="decrypt-password" class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">
                        {{ __('Decryption key') }}
                    </label>

                    <div class="relative" x-data="{ showPassword: false }">
                        <input
                            :type="showPassword ? 'text' : 'password'"
                            id="decrypt-password"
                            :value="password"
                            @input="setPassword"
                            @keydown.enter.prevent="decrypt"
                            placeholder="{{ __('Enter the send password') }}"
                            class="block w-full !rounded-none border-2 border-zinc-950 bg-white px-3 py-2.5 pr-12 font-mono text-sm text-zinc-950 focus:border-emerald-500 focus:outline-hidden focus:ring-2 focus:ring-emerald-500 dark:border-zinc-100 dark:bg-zinc-900 dark:text-zinc-50"
                        />

                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute right-3 top-1/2 -translate-y-1/2 border-2 border-transparent p-1 text-zinc-600 transition-colors hover:border-zinc-950 hover:text-zinc-950 dark:text-zinc-400 dark:hover:border-zinc-100 dark:hover:text-zinc-100"
                            :aria-label="showPassword ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'"
                        >
                            <flux:icon.eye x-show="!showPassword" variant="outline" class="size-4" />
                            <flux:icon.eye-slash x-show="showPassword" variant="outline" class="size-4" />
                        </button>
                    </div>

                    <span class="block text-sm text-red-600" x-show="passwordError" x-text="passwordError" x-cloak></span>
                </div>

                <x-send-decryption-indicator />

                <button
                    type="button"
                    @click="decrypt"
                    class="inline-flex cursor-pointer items-center !rounded-none border-2 border-zinc-950 bg-emerald-500 px-5 py-2.5 text-xs font-bold uppercase tracking-[0.18em] text-emerald-950 transition-colors hover:bg-emerald-400 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-100"
                    :disabled="isDecrypting"
                >
                    {{ __('Decrypt') }}
                </button>

                <span class="block text-sm text-red-600" x-show="decryptionError" x-text="decryptionError" x-cloak></span>
            </div>

            <div
                x-show="isEncrypted && decryptedMessage !== null"
                x-cloak
                class="border-2 border-emerald-500 bg-zinc-50 p-4 font-mono text-sm whitespace-pre-wrap break-words text-zinc-900 dark:bg-zinc-900 dark:text-zinc-100"
                x-text="displayMessage"
            ></div>
        </div>
    </div>

    <div class="bg-zinc-100 px-6 py-4 dark:bg-zinc-900">
        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">
            {{ __('Retention policy') }}
        </p>
        <p class="mt-2 max-w-3xl text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">
            {{ __('After expiry, ciphertext is permanently removed from storage. Revoke early by deleting the send from your registry.') }}
        </p>
    </div>
</section>
