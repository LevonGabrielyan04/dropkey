@props(['send'])

<div
    x-data="sendDetailsManager"
    data-raw-message='@json($send->message)'
    data-min-password-length="{{ config('send.password.min_length') }}"
    class="p-6 sm:p-10"
>
    <h2 class="mb-6 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $send->name }}</h2>

    <dl class="space-y-5">
        <div>
            <dt class="text-sm font-medium text-zinc-500">{{ __('Public ID') }}</dt>
            <dd class="mt-1 font-mono text-sm">{{ $send->public_id }}</dd>
        </div>

        <div>
            <dt class="text-sm font-medium text-zinc-500">{{ __('Expires') }}</dt>
            <dd class="mt-1 text-sm">
                {{ \Illuminate\Support\Carbon::parse($send->valid_to)->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
            </dd>
        </div>

        <div>
            <dt class="text-sm font-medium text-zinc-500">{{ __('Viewers') }}</dt>
            <dd class="mt-1 text-sm">
                {{ $send->authorizedUsers->pluck('name')->join(', ') ?: __('None') }}
            </dd>
        </div>

        <div>
            <dt class="text-sm font-medium text-zinc-500">{{ __('Message') }}</dt>
            <dd class="mt-2">
                <div
                    x-show="!isEncrypted"
                    x-cloak
                    class="rounded-lg bg-zinc-100 p-4 text-sm whitespace-pre-wrap break-words dark:bg-zinc-800"
                    x-text="displayMessage"
                ></div>

                <div x-show="isEncrypted && decryptedMessage === null" x-cloak class="space-y-4">
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('This message is password protected. Enter the password to decrypt it.') }}
                    </p>

                    <div class="space-y-2">
                        <label for="decrypt-password" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Password') }}</label>
                        <div class="relative" x-data="{ showPassword: false }">
                            <input
                                :type="showPassword ? 'text' : 'password'"
                                id="decrypt-password"
                                :value="password"
                                @input="setPassword"
                                @keydown.enter.prevent="decrypt"
                                placeholder="{{ __('Enter the send password') }}"
                                class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 pr-10 text-sm shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-zinc-700 focus:outline-none dark:hover:text-zinc-300"
                                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                            >
                                <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 01-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                        <span class="text-sm text-red-600" x-show="passwordError" x-text="passwordError" x-cloak></span>
                    </div>

                    <x-send-decryption-indicator />

                    <button
                        type="button"
                        @click="decrypt"
                        class="inline-flex items-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100"
                        :disabled="isDecrypting"
                    >
                        {{ __('Decrypt') }}
                    </button>
                    <span class="mt-1 block text-sm text-red-600" x-show="decryptionError" x-text="decryptionError" x-cloak></span>
                </div>

                <div
                    x-show="isEncrypted && decryptedMessage !== null"
                    x-cloak
                    class="rounded-lg bg-zinc-100 p-4 text-sm whitespace-pre-wrap break-words dark:bg-zinc-800"
                    x-text="displayMessage"
                ></div>
            </dd>
        </div>
    </dl>
</div>
