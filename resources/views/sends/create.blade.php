<x-layouts::app :title="__('New Send')">
    <div class="flex h-full w-full flex-1 flex-col font-mono">
        <header class="border-2 border-zinc-950 bg-zinc-50 dark:border-zinc-100 dark:bg-zinc-950">
            <div class="border-b-2 border-emerald-500 bg-emerald-500 px-4 py-1 text-[10px] font-bold uppercase tracking-[0.24em] text-emerald-950">
                {{ __('Encrypted outbound channel') }}
            </div>

            <div class="flex flex-col gap-6 p-6 sm:flex-row sm:items-end sm:justify-between">
                <div class="max-w-2xl">
                    <flux:heading size="xl" class="!font-mono !text-3xl !font-black !uppercase !tracking-tight !text-zinc-950 dark:!text-zinc-50">
                        {{ __('Configure Send') }}
                    </flux:heading>

                    <p class="mt-3 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                        {{ __('Your payload is encrypted in the browser before it leaves this device. Only named viewers can open it, and only until the expiry window closes.') }}
                    </p>
                </div>

                <flux:button
                    :href="route('dashboard')"
                    icon="arrow-left"
                    wire:navigate
                    class="!rounded-none !border-2 !border-zinc-950 !bg-zinc-50 !px-4 !py-3 !text-xs !font-bold !uppercase !tracking-[0.18em] !text-zinc-950 hover:!bg-zinc-200 dark:!border-zinc-100 dark:!bg-zinc-950 dark:!text-zinc-50 dark:hover:!bg-zinc-800"
                >
                    {{ __('Registry') }}
                </flux:button>
            </div>
        </header>

        <section class="border-x-2 border-b-2 border-zinc-950 dark:border-zinc-100">
            <div class="grid grid-cols-1 border-b-2 border-zinc-950 sm:grid-cols-3 dark:border-zinc-100">
                <div class="border-b-2 border-zinc-950 px-4 py-4 sm:border-b-0 sm:border-r-2 dark:border-zinc-100">
                    <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">
                        {{ __('Encryption') }}
                    </p>
                    <p class="mt-2 text-sm leading-relaxed text-zinc-800 dark:text-zinc-200">
                        {{ __('End-to-end. Plaintext never stored on the server.') }}
                    </p>
                </div>

                <div class="border-b-2 border-zinc-950 px-4 py-4 sm:border-b-0 sm:border-r-2 dark:border-zinc-100">
                    <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">
                        {{ __('Access') }}
                    </p>
                    <p class="mt-2 text-sm leading-relaxed text-zinc-800 dark:text-zinc-200">
                        {{ __('Viewer list is enforced at open time. Share the password out of band.') }}
                    </p>
                </div>

                <div class="px-4 py-4">
                    <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">
                        {{ __('Retention') }}
                    </p>
                    <p class="mt-2 text-sm leading-relaxed text-zinc-800 dark:text-zinc-200">
                        {{ __('Ciphertext is deleted automatically when the send expires.') }}
                    </p>
                </div>
            </div>
        </section>

        <form
            method="POST"
            action="{{ route('sends.store') }}"
            x-data="viewerManager"
            data-initial-viewers='@json(old('viewers', []))'
            data-min-password-length="{{ config('send.password.min_length') }}"
            @submit.prevent="submitForm"
            class="border-x-2 border-b-2 border-zinc-950 dark:border-zinc-100"
        >
            @csrf

            <div class="border-b-2 border-zinc-950 bg-zinc-200 px-4 py-3 dark:border-zinc-100 dark:bg-zinc-800">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-600 dark:text-zinc-400">
                    {{ __('Identity') }}
                </p>
            </div>

            <div class="border-b-2 border-zinc-950 bg-white p-6 dark:border-zinc-100 dark:bg-zinc-950">
                <label for="name" class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">
                    {{ __('Send name') }}
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    maxlength="255"
                    value="{{ old('name') }}"
                    placeholder="{{ __('Enter a name for this send') }}"
                    class="mt-2 block w-full !rounded-none border-2 border-zinc-950 bg-white px-3 py-2.5 font-mono text-sm text-zinc-950 focus:border-emerald-500 focus:outline-hidden focus:ring-2 focus:ring-emerald-500 dark:border-zinc-100 dark:bg-zinc-900 dark:text-zinc-50 @error('name') border-red-600 @enderror"
                    required
                />

                @error('name')
                    <span class="mt-2 block text-sm text-red-600">{{ $message }}</span>
                @enderror
            </div>

            <div class="border-b-2 border-zinc-950 bg-zinc-200 px-4 py-3 dark:border-zinc-100 dark:bg-zinc-800">
                <div class="flex items-center justify-between gap-4">
                    <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-600 dark:text-zinc-400">
                        {{ __('Access control') }}
                    </p>
                    <span class="text-xs font-bold uppercase tracking-[0.16em] text-red-600" x-show="error" x-text="error" x-cloak></span>
                </div>
            </div>

            <div class="border-b-2 border-zinc-950 bg-white p-6 dark:border-zinc-100 dark:bg-zinc-950">
                <label for="viewer" class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">
                    {{ __('Authorized viewers') }}
                    <span class="ml-1 font-normal normal-case tracking-normal text-zinc-400">({{ __('up to 100') }})</span>
                </label>

                <input
                    type="text"
                    id="viewer"
                    :value="newViewer"
                    @input="setNewViewer"
                    @keydown.enter.prevent="addViewer"
                    @keydown.comma.prevent="addViewer"
                    placeholder="{{ __('Enter a user name and press Enter') }}"
                    class="mt-2 block w-full !rounded-none border-2 border-zinc-950 bg-white px-3 py-2.5 font-mono text-sm text-zinc-950 focus:border-emerald-500 focus:outline-hidden focus:ring-2 focus:ring-emerald-500 dark:border-zinc-100 dark:bg-zinc-900 dark:text-zinc-50 @error('viewers') border-red-600 @enderror"
                    :required="isViewerInputRequired"
                    autocomplete="username"
                />

                <template x-for="(name, index) in viewers" :key="index">
                    <input type="hidden" name="viewers[]" :value="name" />
                </template>

                <div class="mt-4 flex flex-wrap gap-2" x-show="hasViewers" x-cloak>
                    <template x-for="(name, index) in viewers" :key="index">
                        <span class="inline-flex items-center gap-2 border-2 border-zinc-950 bg-zinc-100 px-3 py-1.5 text-xs font-bold tracking-[0.12em] text-zinc-900 dark:border-zinc-100 dark:bg-zinc-800 dark:text-zinc-100">
                            <span x-text="name"></span>
                            <button
                                type="button"
                                @click="removeViewerFromEvent"
                                :data-index="index"
                                class="inline-flex cursor-pointer border-2 border-transparent p-0.5 text-zinc-600 transition-colors hover:border-red-600 hover:text-red-600 dark:text-zinc-400 dark:hover:border-red-400 dark:hover:text-red-400"
                                :aria-label="'{{ __('Remove viewer') }}'"
                            >
                                <flux:icon.x-mark variant="outline" class="size-3.5" />
                            </button>
                        </span>
                    </template>
                </div>

                @error('viewers')
                    <span class="mt-2 block text-sm text-red-600">{{ $message }}</span>
                @enderror
                @error('viewers.*')
                    <span class="mt-2 block text-sm text-red-600">{{ $message }}</span>
                @enderror
            </div>

            <div class="border-b-2 border-zinc-950 bg-zinc-200 px-4 py-3 dark:border-zinc-100 dark:bg-zinc-800">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-600 dark:text-zinc-400">
                    {{ __('Payload') }}
                </p>
            </div>

            <div class="border-b-2 border-zinc-950 bg-white p-6 dark:border-zinc-100 dark:bg-zinc-950">
                <label for="message" class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">
                    {{ __('Text to send') }}
                </label>

                <textarea
                    id="message"
                    name="message"
                    maxlength="{{ config('send.message.max_length') }}"
                    rows="6"
                    placeholder="{{ __('Type the message content here...') }}"
                    class="mt-2 block w-full !rounded-none border-2 border-zinc-950 bg-white px-3 py-2.5 font-mono text-sm text-zinc-950 focus:border-emerald-500 focus:outline-hidden focus:ring-2 focus:ring-emerald-500 dark:border-zinc-100 dark:bg-zinc-900 dark:text-zinc-50 @error('message') border-red-600 @enderror"
                    required
                >{{ old('message') }}</textarea>

                @error('message')
                    <span class="mt-2 block text-sm text-red-600">{{ $message }}</span>
                @enderror
            </div>

            <div class="grid grid-cols-1 border-b-2 border-zinc-950 sm:grid-cols-2 dark:border-zinc-100">
                <div class="border-b-2 border-zinc-950 sm:border-b-0 sm:border-r-2 dark:border-zinc-100">
                    <div class="border-b-2 border-zinc-950 bg-zinc-200 px-4 py-3 dark:border-zinc-100 dark:bg-zinc-800">
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-600 dark:text-zinc-400">
                            {{ __('Retention') }}
                        </p>
                    </div>

                    <div class="bg-white p-6 dark:bg-zinc-950">
                        <label for="expire_after" class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">
                            {{ __('Expire after') }}
                        </label>

                        <select
                            id="expire_after"
                            name="expire_after"
                            class="mt-2 block w-full !rounded-none border-2 border-zinc-950 bg-white px-3 py-2.5 font-mono text-sm text-zinc-950 focus:border-emerald-500 focus:outline-hidden focus:ring-2 focus:ring-emerald-500 dark:border-zinc-100 dark:bg-zinc-900 dark:text-zinc-50 @error('expire_after') border-red-600 @enderror"
                            required
                        >
                            @foreach(\App\Enums\TimePeriod::cases() as $duration)
                                <option value="{{ $duration->value }}" @selected(old('expire_after', \App\Enums\TimePeriod::ONE_DAY->value) === $duration->value)>
                                    {{ $duration->value }}
                                </option>
                            @endforeach
                        </select>

                        @error('expire_after')
                            <span class="mt-2 block text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div>
                    <div class="border-b-2 border-zinc-950 bg-zinc-200 px-4 py-3 dark:border-zinc-100 dark:bg-zinc-800">
                        <div class="flex items-center gap-2">
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-600 dark:text-zinc-400">
                                {{ __('Encryption key') }}
                            </p>

                            <div x-data="passwordTooltip" class="relative flex items-center">
                                <button
                                    type="button"
                                    @mouseenter="show"
                                    @mouseleave="hide"
                                    @focus="show"
                                    @blur="hide"
                                    class="inline-flex cursor-pointer border-2 border-transparent p-0.5 text-zinc-500 transition-colors hover:border-zinc-950 hover:text-zinc-950 dark:text-zinc-400 dark:hover:border-zinc-100 dark:hover:text-zinc-100"
                                    aria-label="{{ __('Password information') }}"
                                >
                                    <flux:icon.information-circle variant="outline" class="size-4" />
                                </button>

                                <div
                                    x-show="showTooltip"
                                    x-transition.opacity.duration.200ms
                                    class="pointer-events-none absolute bottom-full left-1/2 z-50 mb-2 w-56 -translate-x-1/2 border-2 border-zinc-950 bg-zinc-950 p-3 text-center font-mono text-xs text-zinc-100 dark:border-zinc-100"
                                    x-cloak
                                >
                                    {{ __('The transfer is end-to-end encrypted. Passwords must be at least :length characters.', ['length' => config('send.password.min_length')]) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 dark:bg-zinc-950">
                        <label for="password" class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">
                            {{ __('Password') }}
                        </label>

                        <div class="relative mt-2" x-data="passwordVisibility">
                            <input
                                :type="showPassword ? 'text' : 'password'"
                                id="password"
                                name="password"
                                required
                                minlength="{{ config('send.password.min_length') }}"
                                passwordrules="{{ \Illuminate\Validation\Rules\Password::min(16)->mixedCase()->numbers()->symbols()->toPasswordRulesString() }}"
                                placeholder="{{ __('Minimum :length characters', ['length' => config('send.password.min_length')]) }}"
                                class="block w-full !rounded-none border-2 border-zinc-950 bg-white px-3 py-2.5 pr-12 font-mono text-sm text-zinc-950 focus:border-emerald-500 focus:outline-hidden focus:ring-2 focus:ring-emerald-500 dark:border-zinc-100 dark:bg-zinc-900 dark:text-zinc-50"
                                @input="clearPasswordError"
                            />

                            <button
                                type="button"
                                @click="toggle"
                                class="absolute right-3 top-1/2 -translate-y-1/2 border-2 border-transparent p-1 text-zinc-600 transition-colors hover:border-zinc-950 hover:text-zinc-950 dark:text-zinc-400 dark:hover:border-zinc-100 dark:hover:text-zinc-100"
                                :aria-label="showPassword ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'"
                            >
                                <flux:icon.eye x-show="!showPassword" variant="outline" class="size-4" />
                                <flux:icon.eye-slash x-show="showPassword" variant="outline" class="size-4" />
                            </button>
                        </div>

                        <span class="mt-2 block text-sm text-red-600" x-show="passwordError" x-text="passwordError" x-cloak></span>
                    </div>
                </div>
            </div>

            <div class="border-b-2 border-emerald-500 bg-emerald-500/10 px-6 py-4 dark:border-emerald-400">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700 dark:text-emerald-400">
                    {{ __('Before you submit') }}
                </p>
                <p class="mt-2 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">
                    {{ __('Copy the generated link and password through separate channels. We cannot recover either once this send is created.') }}
                </p>
            </div>

            <div class="bg-zinc-50 p-6 dark:bg-zinc-900">
                <x-send-encryption-indicator />

                <button
                    type="submit"
                    class="inline-flex w-full cursor-pointer items-center justify-center !rounded-none border-2 border-zinc-950 bg-emerald-500 px-4 py-3 text-xs font-bold uppercase tracking-[0.18em] text-emerald-950 transition-colors hover:bg-emerald-400 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-100"
                    :disabled="isEncrypting || isCheckingPassword"
                >
                    {{ __('Generate') }}
                </button>

                <span class="mt-2 block text-sm text-red-600" x-show="encryptionError" x-text="encryptionError" x-cloak></span>
            </div>
        </form>
    </div>
</x-layouts::app>
